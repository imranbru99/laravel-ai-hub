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
            'tools' => isset($payload['tools']) ? $this->mapTools($payload['tools']) : null,
            'tool_choice' => isset($payload['tool_choice']) ? $this->mapToolChoice($payload['tool_choice']) : null,
        ], fn ($v) => $v !== null && $v !== '');

        $response = $this->client()->post($this->baseUrl().'/messages', $body);
        $response->throw();
        $json = $response->json();

        $blocks = collect(data_get($json, 'content', []));
        $content = $blocks->where('type', 'text')->pluck('text')->implode('');
        $toolCalls = $blocks
            ->where('type', 'tool_use')
            ->map(fn ($block) => [
                'id' => $block['id'] ?? null,
                'type' => 'function',
                'function' => [
                    'name' => $block['name'] ?? '',
                    'arguments' => json_encode($block['input'] ?? new \stdClass, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ])
            ->values()
            ->all();

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
            toolCalls: $toolCalls,
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
            'tools' => isset($payload['tools']) ? $this->mapTools($payload['tools']) : null,
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
                $system .= ($system === '' ? '' : "\n").$this->plainText($message['content'] ?? '');
                continue;
            }
            $chat[] = [
                'role' => ($message['role'] ?? 'user') === 'assistant' ? 'assistant' : 'user',
                'content' => $this->mapContent($message['content'] ?? ''),
            ];
        }

        if ($chat === []) {
            $chat[] = ['role' => 'user', 'content' => ''];
        }

        return [$system, $chat];
    }

    protected function mapContent(mixed $content): mixed
    {
        if (! is_array($content)) {
            return (string) $content;
        }

        $blocks = [];
        foreach ($content as $part) {
            if (! is_array($part)) {
                $blocks[] = ['type' => 'text', 'text' => (string) $part];
                continue;
            }
            $type = $part['type'] ?? 'text';
            if ($type === 'image_url') {
                $url = (string) data_get($part, 'image_url.url', $part['url'] ?? '');
                $blocks[] = $this->imageBlock($url);
                continue;
            }
            $blocks[] = ['type' => 'text', 'text' => (string) ($part['text'] ?? '')];
        }

        return $blocks === [] ? '' : $blocks;
    }

    protected function imageBlock(string $url): array
    {
        if (preg_match('#^data:(image/[^;]+);base64,(.+)$#', $url, $m)) {
            return [
                'type' => 'image',
                'source' => [
                    'type' => 'base64',
                    'media_type' => $m[1],
                    'data' => $m[2],
                ],
            ];
        }

        return [
            'type' => 'image',
            'source' => [
                'type' => 'url',
                'url' => $url,
            ],
        ];
    }

    protected function mapTools(array $tools): array
    {
        $mapped = [];
        foreach ($tools as $tool) {
            $fn = $tool['function'] ?? $tool;
            $mapped[] = [
                'name' => (string) ($fn['name'] ?? ''),
                'description' => (string) ($fn['description'] ?? ''),
                'input_schema' => $fn['parameters'] ?? ['type' => 'object', 'properties' => new \stdClass],
            ];
        }

        return $mapped;
    }

    protected function mapToolChoice(mixed $choice): mixed
    {
        if ($choice === 'auto' || $choice === 'none') {
            return ['type' => $choice === 'none' ? 'none' : 'auto'];
        }
        if ($choice === 'required' || $choice === 'any') {
            return ['type' => 'any'];
        }
        if (is_array($choice) && isset($choice['function']['name'])) {
            return ['type' => 'tool', 'name' => $choice['function']['name']];
        }

        return $choice;
    }

    protected function plainText(mixed $content): string
    {
        if (is_string($content)) {
            return $content;
        }
        if (! is_array($content)) {
            return '';
        }

        $bits = [];
        foreach ($content as $part) {
            if (is_string($part)) {
                $bits[] = $part;
            } elseif (is_array($part) && isset($part['text'])) {
                $bits[] = (string) $part['text'];
            }
        }

        return implode("\n", $bits);
    }
}
