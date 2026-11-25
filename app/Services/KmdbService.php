<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentChunk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class KmdbService
{
    /**
     * Store a document, extract text, and split into searchable chunks.
     */
    public function createAndIndexDocument(array $data, ?UploadedFile $file = null): Document
    {
        $filePath = null;
        $extractedText = (string) ($data['content_text'] ?? '');

        if ($file) {
            $filePath = $file->store('kmdb', 'local');
            $fromFile = $this->extractTextFromUploadedFile($file);
            if ($fromFile !== '') {
                $extractedText = trim($extractedText . "\n\n" . $fromFile);
            }
        }

        $docCode = $data['doc_code'] ?? strtoupper($data['doc_type'] ?? 'DOC') . '-' . Str::upper(Str::random(6));

        $document = Document::create([
            'project_id' => $data['project_id'] ?? null,
            'doc_code' => $docCode,
            'doc_type' => strtoupper((string) ($data['doc_type'] ?? 'SOP')),
            'version' => (string) ($data['version'] ?? 'v1.0'),
            'title' => (string) $data['title'],
            'status' => (string) ($data['status'] ?? 'approved'),
            'file_path' => $filePath,
            'content_text' => $extractedText,
            'uploaded_by' => $data['uploaded_by'] ?? null,
        ]);

        $this->rebuildChunks($document, $extractedText);

        return $document->load('chunks');
    }

    /**
     * Extract text from plain/markdown/text files safely on lightweight setups.
     */
    protected function extractTextFromUploadedFile(UploadedFile $file): string
    {
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (in_array($ext, ['txt', 'md', 'csv', 'log', 'json'], true)) {
            $raw = file_get_contents($file->getRealPath());
            return is_string($raw) ? trim($raw) : '';
        }

        // For binary formats like PDF/DOCX in POC, return empty unless text is pasted/parsed
        return '';
    }

    /**
     * Split document text into ~600-character chunks for fast retrieval on 8GB RAM.
     */
    public function rebuildChunks(Document $document, string $text): void
    {
        $document->chunks()->delete();

        $clean = trim(preg_replace('/\r\n?/', "\n", $text) ?? '');
        if ($clean === '') {
            return;
        }

        $paragraphs = preg_split('/\n\s*\n/', $clean) ?: [$clean];
        $chunkSize = 600;
        $buffer = '';
        $index = 0;

        foreach ($paragraphs as $para) {
            $para = trim($para);
            if ($para === '') {
                continue;
            }

            if (mb_strlen($buffer . "\n\n" . $para) > $chunkSize && $buffer !== '') {
                DocumentChunk::create([
                    'document_id' => $document->id,
                    'chunk_index' => $index++,
                    'section_title' => $document->doc_code . ' (' . $document->version . ') - Part ' . $index,
                    'chunk_text' => trim($buffer),
                ]);
                $buffer = $para;
            } else {
                $buffer = $buffer === '' ? $para : ($buffer . "\n\n" . $para);
            }
        }

        if (trim($buffer) !== '') {
            DocumentChunk::create([
                'document_id' => $document->id,
                'chunk_index' => $index++,
                'section_title' => $document->doc_code . ' (' . $document->version . ') - Part ' . ($index + 1),
                'chunk_text' => trim($buffer),
            ]);
        }
    }

    /**
     * Search relevant chunks using keywords / LIKE fallback so it works reliably on all MySQL setups.
     *
     * @return Collection<int, DocumentChunk>
     */
    public function searchRelevantChunks(string $query, ?int $projectId = null, int $limit = 4): Collection
    {
        $words = collect(preg_split('/[^\p{L}\p{N}_-]+/u', mb_strtolower($query)) ?: [])
            ->filter(fn($w) => mb_strlen($w) >= 3)
            ->reject(fn($w) => in_array($w, ['the', 'and', 'for', 'with', 'what', 'how', 'are', 'from', 'into', 'kya', 'hai', 'aur', 'mein'], true))
            ->unique()
            ->values();

        $q = DocumentChunk::query()
            ->with('document')
            ->whereHas('document', function ($docQuery) use ($projectId) {
                $docQuery->where('status', '!=', 'archived');
                if ($projectId) {
                    $docQuery->where(function ($sub) use ($projectId) {
                        $sub->where('project_id', $projectId)->orWhereNull('project_id');
                    });
                }
            });

        if ($words->isNotEmpty()) {
            $q->where(function ($chunkQuery) use ($words) {
                foreach ($words as $word) {
                    $chunkQuery->orWhere('chunk_text', 'LIKE', '%' . $word . '%')
                        ->orWhere('section_title', 'LIKE', '%' . $word . '%');
                }
            });
        }

        $results = $q->limit($limit)->get();

        if ($results->isEmpty()) {
            return DocumentChunk::with('document')->limit($limit)->get();
        }

        return $results;
    }

    /**
     * Ask Qwen using retrieved KMDB chunks as grounded context.
     *
     * @return array{answer: string, model: string, ok: bool, sources: array<int, array<string, mixed>>, error?: string}
     */
    public function askWithContext(string $question, OllamaQwenService $qwen, ?int $projectId = null): array
    {
        $chunks = $this->searchRelevantChunks($question, $projectId, 4);

        $contextParts = [];
        $sources = [];

        foreach ($chunks as $chunk) {
            $doc = $chunk->document;
            $code = $doc?->doc_code ?? 'DOC';
            $ver = $doc?->version ?? 'v1.0';
            $title = $doc?->title ?? 'Document';

            $contextParts[] = "\n{$chunk->chunk_text}";
            $sources[] = [
                'doc_code' => $code,
                'version' => $ver,
                'title' => $title,
                'section' => $chunk->section_title,
            ];
        }

        $contextBlock = implode("\n\n---\n\n", $contextParts);

        $systemPrompt = "You are a helpful Telecom Project Management assistant. Answer using ONLY the provided KMDB context when available. Mention the document code and version if used. If the context does not contain the answer, say clearly what is missing.";

        $userPrompt = "KMDB Context:\n{$contextBlock}\n\nUser Question:\n{$question}";

        $res = $qwen->chat([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ]);

        return [
            'ok' => $res['ok'],
            'model' => $res['model'],
            'answer' => $res['ok'] ? $res['content'] : '',
            'sources' => $sources,
            'error' => $res['error'] ?? null,
        ];
    }
}