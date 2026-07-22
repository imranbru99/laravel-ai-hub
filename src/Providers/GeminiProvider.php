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

        if (isset($payload['temperature']) || isset($payload['max_tokens'])) {
            $body['generationConfig'] = array_filter([
                'temperature' => $payload['temperature'] ?? null,
                'maxOutputTokens' => $payload['max_tokens'] ?? null,
            ], fn ($v) => $v !== null);
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

        $content = (string) data_get($json, 'candidates.0.content.parts.0.text', '');
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
        );
    }

    public function stream(array $payload): \Generator
    {
        // Gemini streaming via streamGenerateContent — yield full chunks as text deltas
        $model = $payload['model'] ?? config('ai-hub.defaults.gemini');
        $prompt = (string) ($payload['prompt'] ?? '');
        $messages = $payload['messages'] ?? null;

        $contents = $messages
            ? $this->mapMessages($messages)
            : [['role' => 'user', 'parts' => [['text' => $prompt]]]];

        $url = sprintf(
            '%s/models/%s:streamGenerateContent?alt=sse&key=%s',
            $this->baseUrl(),
            urlencode((string) $model),
            urlencode($this->apiKey())
        );

        $response = $this->http()
            ->withOptions(['stream' => true])
            ->post($url, ['contents' => $contents]);

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
                // Gemini: fold system into user preamble
                $mapped[] = [
                    'role' => 'user',
                    'parts' => [['text' => (string) ($message['content'] ?? '')]],
                ];
                continue;
            }
            $mapped[] = [
                'role' => $role,
                'parts' => [['text' => (string) ($message['content'] ?? '')]],
            ];
        }

        return $mapped;
    }
}
