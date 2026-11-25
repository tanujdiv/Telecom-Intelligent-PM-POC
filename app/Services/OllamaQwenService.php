<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class OllamaQwenService
{
    protected string $baseUrl;
    protected string $model;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('agent.ollama.base_url', 'http://127.0.0.1:11434'), '/');
        $this->model = (string) config('agent.ollama.model', 'qwen2.5:3b');
        $this->timeout = (int) config('agent.ollama.timeout', 120);
    }

    /**
     * Send chat messages to Qwen running via Ollama.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<string, mixed>  $options
     * @return array{ok: bool, model: string, content: string, error?: string, raw?: array<string, mixed>}
     */
    public function chat(array $messages, array $options = []): array
    {
        $model = (string) ($options['model'] ?? $this->model);

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'stream' => false,
            'options' => array_merge([
                'temperature' => 0.3,
                'num_ctx' => 2048, // 8GB RAM / i3 friendly context window
            ], $options['options'] ?? []),
        ];

        try {
            $response = Http::timeout($this->timeout)
                ->acceptJson()
                ->post($this->baseUrl . '/api/chat', $payload);

            if ($response->failed()) {
                return [
                    'ok' => false,
                    'model' => $model,
                    'content' => '',
                    'error' => 'Ollama API error (HTTP ' . $response->status() . '): ' . $response->body(),
                ];
            }

            $data = $response->json();
            $text = (string) data_get($data, 'message.content', '');

            return [
                'ok' => true,
                'model' => $model,
                'content' => trim($text),
                'raw' => is_array($data) ? $data : [],
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'model' => $model,
                'content' => '',
                'error' => 'Unable to reach Ollama at ' . $this->baseUrl . ': ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Quick health check to verify Ollama is reachable.
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl . '/api/tags');
            return $response->successful();
        } catch (Throwable) {
            return false;
        }
    }
}