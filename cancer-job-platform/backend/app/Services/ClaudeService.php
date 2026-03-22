<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeService
{
    private string $apiKey;
    private string $model   = 'claude-sonnet-4-20250514';
    private int    $maxTokens = 1024;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.key');
    }

    /**
     * Stream a chat response token-by-token via SSE.
     *
     * @param callable(string): void $onToken  called with each text delta
     * @param callable(): void       $onDone   called when stream ends
     */
    public function streamChat(
        string   $system,
        array    $messages,
        callable $onToken,
        callable $onDone,
        int      $thinkingBudget = 0,   // 0 = off, >0 = extended thinking tokens
    ): void {
        $body = [
            'model'      => $this->model,
            'max_tokens' => $this->maxTokens + $thinkingBudget,
            'system'     => $system,
            'messages'   => $messages,
            'stream'     => true,
        ];

        if ($thinkingBudget > 0) {
            $body['thinking'] = [
                'type'         => 'enabled',
                'budget_tokens' => $thinkingBudget,
            ];
        }

        $response = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->withOptions(['stream' => true])
          ->post('https://api.anthropic.com/v1/messages', $body);

        $body = $response->getBody();

        while (!$body->eof()) {
            $line = $this->readLine($body);
            if (!str_starts_with($line, 'data:')) continue;

            $data = json_decode(substr($line, 5), true);
            if (!$data) continue;

            $type = $data['type'] ?? '';

            if ($type === 'content_block_delta') {
                $delta = $data['delta'] ?? [];
                // Only emit text deltas (not thinking blocks)
                if (($delta['type'] ?? '') === 'text_delta') {
                    $onToken($delta['text'] ?? '');
                }
            }

            if ($type === 'message_stop') {
                $onDone();
                break;
            }

            if ($type === 'error') {
                Log::error('claude.stream_error', $data);
                $onToken("\n\n[오류가 발생했어요. 잠시 후 다시 시도해 주세요.]");
                $onDone();
                break;
            }
        }
    }

    /**
     * Non-streaming call — returns full text (for background jobs, summaries).
     */
    public function complete(string $system, array $messages, int $maxTokens = 512): string
    {
        $response = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model'      => $this->model,
            'max_tokens' => $maxTokens,
            'system'     => $system,
            'messages'   => $messages,
        ]);

        return $response->json('content.0.text', '');
    }

    private function readLine($stream): string
    {
        $line = '';
        while (!$stream->eof()) {
            $char = $stream->read(1);
            if ($char === "\n") break;
            $line .= $char;
        }
        return rtrim($line);
    }
}
