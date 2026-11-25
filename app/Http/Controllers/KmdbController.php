<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Project;
use App\Services\KmdbService;
use App\Services\OllamaQwenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KmdbController extends Controller
{
    public function index(Request $request, KmdbService $kmdb, OllamaQwenService $qwen): View
    {
        $documents = Document::with('project', 'chunks')->latest()->get();
        $projects = Project::orderBy('name')->get();

        $askResult = null;
        if ($request->filled('question')) {
            $projectId = $request->filled('project_id') ? (int) $request->input('project_id') : null;
            $askResult = $kmdb->askWithContext((string) $request->input('question'), $qwen, $projectId);
        }

        return view('kmdb.index', [
            'documents' => $documents,
            'projects' => $projects,
            'askResult' => $askResult,
            'question' => (string) $request->input('question', ''),
        ]);
    }

    public function store(Request $request, KmdbService $kmdb): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => ['nullable', 'exists:projects,id'],
            'doc_code' => ['nullable', 'string', 'max:40', 'unique:documents,doc_code'],
            'doc_type' => ['required', 'string', 'max:20'],
            'version' => ['required', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:20'],
            'content_text' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'max:5120'],
        ]);

        if (empty($validated['content_text']) && !$request->hasFile('file')) {
            return back()->withErrors(['content_text' => 'Please enter SOP/MOP/document text or upload a text/markdown file.'])->withInput();
        }

        $kmdb->createAndIndexDocument($validated, $request->file('file'));

        return redirect()->route('kmdb.index')->with('status', 'Document indexed into KMDB successfully.');
    }
}