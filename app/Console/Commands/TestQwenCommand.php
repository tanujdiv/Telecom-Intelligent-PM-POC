<?php

namespace App\Console\Commands;

use App\Services\OllamaQwenService;
use Illuminate\Console\Command;

class TestQwenCommand extends Command
{
    protected $signature = 'qwen:test {prompt=Reply in one short line that Qwen via Ollama is ready.}';
    protected $description = 'Test connection and response from Qwen running on Ollama';

    public function handle(OllamaQwenService $qwen): int
    {
        $prompt = (string) $this->argument('prompt');
        $this->info('Checking Ollama and sending prompt to Qwen...');

        $result = $qwen->chat([
            ['role' => 'system', 'content' => 'You are a concise telecom project management assistant.'],
            ['role' => 'user', 'content' => $prompt],
        ]);

        if (!$result['ok']) {
            $this->error($result['error'] ?? 'Unknown error');
            return self::FAILURE;
        }

        $this->info('Model: ' . $result['model']);
        $this->line('Response: ' . $result['content']);

        return self::SUCCESS;
    }
}