<?php

namespace ImranDevBd\AiHub\Providers;

use ImranDevBd\AiHub\Data\AiResponse;
use ImranDevBd\AiHub\Data\EmbeddingResponse;

class OpenAIProvider extends AbstractProvider
{
    public function name(): string
    {
        return 'openai';
    }

    public function complete(array $payload): AiResponse
    {
        $model = $payload['model'] ?? config('ai-hub.defaults.openai');
        $messages = $payload['messages'] ?? [
            ['role' => 'user', 'content' => (string) ($payload['prompt'] ?? '')],
        ];

        $body = array_filter([
            'model' => $model,
            'messages' => $messages,
            'temperature' => $payload['temperature'] ?? null,
            'max_tokens' => $payload['max_tokens'] ?? null,
            'response_format' => $payload['response_format'] ?? null,
        ], fn ($v) => $v !== null);

        $response = $this->client()->post($this->baseUrl().'/chat/completions', $body);
        $response->throw();
        $json = $response->json();

        $content = (string) data_get($json, 'choices.0.message.content', '');
        $promptTokens = (int) data_get($json, 'usage.prompt_tokens', 0);
        $completionTokens = (int) data_get($json, 'usage.completion_tokens', 0);

        return new AiResponse(
            content: $content,
            provider: $this->name(),
            model: (string) $model,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            totalTokens: (int) data_get($json, 'usage.total_tokens', $promptTokens + $completionTokens),
            raw: is_array($json) ? $json : [],
        );
    }

    public function stream(array $payload): \Generator
    {
        $model = $payload['model'] ?? config('ai-hub.defaults.openai');
        $messages = $payload['messages'] ?? [
            ['role' => 'user', 'content' => (string) ($payload['prompt'] ?? '')],
        ];

        $body = [
            'model' => $model,
            'messages' => $messages,
            'stream' => true,
        ];

        $response = $this->client()
            ->withOptions(['stream' => true])
            ->post($this->baseUrl().'/chat/completions', $body);

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
                if ($data === '[DONE]') {
                    return;
                }

                $json = json_decode($data, true);
                $delta = data_get($json, 'choices.0.delta.content');
                if (is_string($delta) && $delta !== '') {
                    yield $delta;
                }
            }
        }
    }

    public function embed(array $payload): EmbeddingResponse
    {
        $model = $payload['model'] ?? 'text-embedding-3-small';
        $input = $payload['input'] ?? $payload['prompt'] ?? '';

        $response = $this->client()->post($this->baseUrl().'/embeddings', [
            'model' => $model,
            'input' => $input,
        ]);
        $response->throw();
        $json = $response->json();

        $vectors = collect(data_get($json, 'data', []))
            ->pluck('embedding')
            ->values()
            ->all();

        return new EmbeddingResponse(
            embeddings: $vectors,
            provider: $this->name(),
            model: (string) $model,
            promptTokens: (int) data_get($json, 'usage.prompt_tokens', 0),
            totalTokens: (int) data_get($json, 'usage.total_tokens', 0),
            raw: is_array($json) ? $json : [],
        );
    }

    protected function client()
    {
        $http = $this->http()->withToken($this->apiKey());

        if (! empty($this->config['organization'])) {
            $http = $http->withHeaders([
                'OpenAI-Organization' => $this->config['organization'],
            ]);
        }

        return $http;
    }
}
