<?php

namespace ImranDevBd\AiHub\Providers;

use ImranDevBd\AiHub\Data\AiResponse;
use ImranDevBd\AiHub\Data\EmbeddingResponse;
use ImranDevBd\AiHub\Exceptions\AiHubException;

class ClaudeProvider extends AbstractProvider
{
    public function name(): string
    {
        return 'claude';
    }

    public function complete(array $payload): AiResponse
    {
        $model = $payload['model'] ?? config('ai-hub.defaults.claude');
        $messages = $payload['messages'] ?? [
            ['role' => 'user', 'content' => (string) ($payload['prompt'] ?? '')],
        ];

        [$system, $chatMessages] = $this->splitSystem($messages);

        $body = array_filter([
            'model' => $model,
            'max_tokens' => $payload['max_tokens'] ?? 4096,
            'temperature' => $payload['temperature'] ?? null,
            'system' => $system,
            'messages' => $chatMessages,
        ], fn ($v) => $v !== null && $v !== '');

        $response = $this->client()->post($this->baseUrl().'/messages', $body);
        $response->throw();
        $json = $response->json();

        $content = collect(data_get($json, 'content', []))
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');

        $promptTokens = (int) data_get($json, 'usage.input_tokens', 0);
        $completionTokens = (int) data_get($json, 'usage.output_tokens', 0);

        return new AiResponse(
            content: $content,
            provider: $this->name(),
            model: (string) $model,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            totalTokens: $promptTokens + $completionTokens,
            raw: is_array($json) ? $json : [],
        );
    }

    public function stream(array $payload): \Generator
    {
        $model = $payload['model'] ?? config('ai-hub.defaults.claude');
        $messages = $payload['messages'] ?? [
            ['role' => 'user', 'content' => (string) ($payload['prompt'] ?? '')],
        ];
        [$system, $chatMessages] = $this->splitSystem($messages);

        $body = array_filter([
            'model' => $model,
            'max_tokens' => $payload['max_tokens'] ?? 4096,
            'system' => $system,
            'messages' => $chatMessages,
            'stream' => true,
        ], fn ($v) => $v !== null && $v !== '');

        $response = $this->client()
            ->withOptions(['stream' => true])
            ->post($this->baseUrl().'/messages', $body);

        $response->throw();
        $bodyStream = $response->toPsrResponse()->getBody();
        $buffer = '';

        while (! $bodyStream->eof()) {
            $buffer .= $bodyStream->read(1024);
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if ($line === '' || ! str_starts_with($line, 'data:')) {
                    continue;
                }

                $data = trim(substr($line, 5));
                $json = json_decode($data, true);
                if (($json['type'] ?? '') === 'content_block_delta') {
                    $text = data_get($json, 'delta.text');
                    if (is_string($text) && $text !== '') {
                        yield $text;
                    }
                }
            }
        }
    }

    public function embed(array $payload): EmbeddingResponse
    {
        throw new AiHubException('Claude does not provide a public embeddings API via AI Hub yet.');
    }

    protected function client()
    {
        return $this->http()->withHeaders([
            'x-api-key' => $this->apiKey(),
            'anthropic-version' => $this->config['version'] ?? '2023-06-01',
        ]);
    }

    protected function splitSystem(array $messages): array
    {
        $system = '';
        $chat = [];

        foreach ($messages as $message) {
            if (($message['role'] ?? '') === 'system') {
                $system .= ($system === '' ? '' : "\n").(string) ($message['content'] ?? '');
                continue;
            }
            $chat[] = [
                'role' => ($message['role'] ?? 'user') === 'assistant' ? 'assistant' : 'user',
                'content' => (string) ($message['content'] ?? ''),
            ];
        }

        if ($chat === []) {
            $chat[] = ['role' => 'user', 'content' => ''];
        }

        return [$system, $chat];
    }
}
