<?php

namespace ImranDevBd\AiHub\Providers;

use ImranDevBd\AiHub\Data\AiResponse;
use ImranDevBd\AiHub\Data\EmbeddingResponse;

class GeminiProvider extends AbstractProvider
{
    public function name(): string
    {
        return 'gemini';
    }

    public function complete(array $payload): AiResponse
    {
        $model = $payload['model'] ?? config('ai-hub.defaults.gemini');
        $prompt = (string) ($payload['prompt'] ?? '');
        $messages = $payload['messages'] ?? null;

        $contents = $messages
            ? $this->mapMessages($messages)
            : [['role' => 'user', 'parts' => [['text' => $prompt]]]];

        $body = [
            'contents' => $contents,
        ];

        $generation = array_filter([
            'temperature' => $payload['temperature'] ?? null,
            'maxOutputTokens' => $payload['max_tokens'] ?? null,
        ], fn ($v) => $v !== null);

        if ($generation !== []) {
            $body['generationConfig'] = $generation;
        }

        if (! empty($payload['tools'])) {
            $body['tools'] = [['functionDeclarations' => $this->mapTools($payload['tools'])]];
        }

        $url = sprintf(
            '%s/models/%s:generateContent?key=%s',
            $this->baseUrl(),
            urlencode((string) $model),
            urlencode($this->apiKey())
        );

        $response = $this->http()->post($url, $body);
        $response->throw();
        $json = $response->json();

        $parts = collect(data_get($json, 'candidates.0.content.parts', []));
        $content = $parts->pluck('text')->filter()->implode('');
        $toolCalls = $parts
            ->filter(fn ($part) => isset($part['functionCall']))
            ->map(fn ($part) => [
                'id' => null,
                'type' => 'function',
                'function' => [
                    'name' => data_get($part, 'functionCall.name', ''),
                    'arguments' => json_encode(data_get($part, 'functionCall.args', new \stdClass), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ])
            ->values()
            ->all();

        $promptTokens = (int) data_get($json, 'usageMetadata.promptTokenCount', $this->estimateTokens($prompt));
        $completionTokens = (int) data_get($json, 'usageMetadata.candidatesTokenCount', $this->estimateTokens($content));
        $totalTokens = (int) data_get($json, 'usageMetadata.totalTokenCount', $promptTokens + $completionTokens);

        return new AiResponse(
            content: $content,
            provider: $this->name(),
            model: (string) $model,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            totalTokens: $totalTokens,
            raw: is_array($json) ? $json : [],
            toolCalls: $toolCalls,
        );
    }

    public function stream(array $payload): \Generator
    {
        $model = $payload['model'] ?? config('ai-hub.defaults.gemini');
        $prompt = (string) ($payload['prompt'] ?? '');
        $messages = $payload['messages'] ?? null;

        $contents = $messages
            ? $this->mapMessages($messages)
            : [['role' => 'user', 'parts' => [['text' => $prompt]]]];

        $body = ['contents' => $contents];
        if (! empty($payload['tools'])) {
            $body['tools'] = [['functionDeclarations' => $this->mapTools($payload['tools'])]];
        }

        $url = sprintf(
            '%s/models/%s:streamGenerateContent?alt=sse&key=%s',
            $this->baseUrl(),
            urlencode((string) $model),
            urlencode($this->apiKey())
        );

        $response = $this->http()
            ->withOptions(['stream' => true])
            ->post($url, $body);

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
                $text = data_get($json, 'candidates.0.content.parts.0.text');
                if (is_string($text) && $text !== '') {
                    yield $text;
                }
            }
        }
    }

    public function embed(array $payload): EmbeddingResponse
    {
        $model = $payload['model'] ?? 'text-embedding-004';
        $input = (string) ($payload['input'] ?? $payload['prompt'] ?? '');

        $url = sprintf(
            '%s/models/%s:embedContent?key=%s',
            $this->baseUrl(),
            urlencode((string) $model),
            urlencode($this->apiKey())
        );

        $response = $this->http()->post($url, [
            'content' => [
                'parts' => [['text' => $input]],
            ],
        ]);
        $response->throw();
        $json = $response->json();

        $vector = data_get($json, 'embedding.values', []);

        return new EmbeddingResponse(
            embeddings: [is_array($vector) ? $vector : []],
            provider: $this->name(),
            model: (string) $model,
            promptTokens: $this->estimateTokens($input),
            totalTokens: $this->estimateTokens($input),
            raw: is_array($json) ? $json : [],
        );
    }

    protected function mapMessages(array $messages): array
    {
        $mapped = [];
        foreach ($messages as $message) {
            $role = ($message['role'] ?? 'user') === 'assistant' ? 'model' : 'user';
            if (($message['role'] ?? '') === 'system') {
                $mapped[] = [
                    'role' => 'user',
                    'parts' => $this->mapParts($message['content'] ?? ''),
                ];
                continue;
            }
            $mapped[] = [
                'role' => $role,
                'parts' => $this->mapParts($message['content'] ?? ''),
            ];
        }

        return $mapped;
    }

    protected function mapParts(mixed $content): array
    {
        if (! is_array($content)) {
            return [['text' => (string) $content]];
        }

        $parts = [];
        foreach ($content as $part) {
            if (! is_array($part)) {
                $parts[] = ['text' => (string) $part];
                continue;
            }
            if (($part['type'] ?? '') === 'image_url') {
                $url = (string) data_get($part, 'image_url.url', $part['url'] ?? '');
                $parts[] = $this->imagePart($url);
                continue;
            }
            $parts[] = ['text' => (string) ($part['text'] ?? '')];
        }

        return $parts === [] ? [['text' => '']] : $parts;
    }

    protected function imagePart(string $url): array
    {
        if (preg_match('#^data:(image/[^;]+);base64,(.+)$#', $url, $m)) {
            return [
                'inline_data' => [
                    'mime_type' => $m[1],
                    'data' => $m[2],
                ],
            ];
        }

        return [
            'file_data' => [
                'mime_type' => 'image/jpeg',
                'file_uri' => $url,
            ],
        ];
    }

    protected function mapTools(array $tools): array
    {
        $mapped = [];
        foreach ($tools as $tool) {
            $fn = $tool['function'] ?? $tool;
            $mapped[] = array_filter([
                'name' => $fn['name'] ?? null,
                'description' => $fn['description'] ?? null,
                'parameters' => $fn['parameters'] ?? null,
            ]);
        }

        return $mapped;
    }
}
