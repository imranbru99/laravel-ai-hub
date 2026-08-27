<?php

namespace ImranDevBd\AiHub\Providers;

use ImranDevBd\AiHub\Data\AiResponse;
use ImranDevBd\AiHub\Data\EmbeddingResponse;
use ImranDevBd\AiHub\Support\ModelCapabilities;
use Illuminate\Http\Client\Response;

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

        $body = $this->chatBody($payload, $messages, false);
        $response = $this->sendChat($body);
        $json = $response->json();

        $content = (string) data_get($json, 'choices.0.message.content', '');
        $promptTokens = (int) data_get($json, 'usage.prompt_tokens', 0);
        $completionTokens = (int) data_get($json, 'usage.completion_tokens', 0);
        $toolCalls = data_get($json, 'choices.0.message.tool_calls', []) ?: [];

        return new AiResponse(
            content: $content,
            provider: $this->name(),
            model: (string) $model,
            promptTokens: $promptTokens,
            completionTokens: $completionTokens,
            totalTokens: (int) data_get($json, 'usage.total_tokens', $promptTokens + $completionTokens),
            raw: is_array($json) ? $json : [],
            toolCalls: is_array($toolCalls) ? $toolCalls : [],
        );
    }

    public function stream(array $payload): \Generator
    {
        $model = $payload['model'] ?? config('ai-hub.defaults.openai');
        $messages = $payload['messages'] ?? [
            ['role' => 'user', 'content' => (string) ($payload['prompt'] ?? '')],
        ];

        $body = $this->chatBody($payload, $messages, true);
        $response = $this->sendChat($body);

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

        $response = $this->client()->post($this->embeddingsUrl((string) $model), [
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

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, array<string, mixed>>  $messages
     * @return array<string, mixed>
     */
    protected function chatBody(array $payload, array $messages, bool $stream): array
    {
        $body = array_filter([
            'model' => $payload['model'] ?? null,
            'messages' => $messages,
            'temperature' => $payload['temperature'] ?? null,
            'max_tokens' => $payload['max_tokens'] ?? null,
            'response_format' => $payload['response_format'] ?? null,
            'tools' => $payload['tools'] ?? null,
            'tool_choice' => $payload['tool_choice'] ?? null,
            'stream' => $stream ? true : null,
        ], fn ($v) => $v !== null);

        return ModelCapabilities::sanitizeChatBody($body);
    }

    /**
     * POST chat/completions, retrying once if the model rejects sampling fields.
     */
    protected function sendChat(array $body): Response
    {
        $url = $this->chatUrl((string) ($body['model'] ?? ''));
        $response = $this->postChat($url, $body);

        if ($this->shouldRetryWithoutSampling($response, $body)) {
            $response = $this->postChat($url, ModelCapabilities::dropSampling($body, (string) $response->body()));
        }

        $response->throw();

        return $response;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    protected function postChat(string $url, array $body): Response
    {
        $http = $this->client();
        if (! empty($body['stream'])) {
            $http = $http->withOptions(['stream' => true]);
        }

        return $http->post($url, $body);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    protected function shouldRetryWithoutSampling(Response $response, array $body): bool
    {
        if ($response->status() !== 400) {
            return false;
        }

        $error = (string) $response->body();
        if (! ModelCapabilities::looksLikeUnsupportedSampling($error)) {
            return false;
        }

        return ModelCapabilities::dropSampling($body, $error) !== $body;
    }

    protected function chatUrl(string $model): string
    {
        return $this->baseUrl().'/chat/completions';
    }

    protected function embeddingsUrl(string $model): string
    {
        return $this->baseUrl().'/embeddings';
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
