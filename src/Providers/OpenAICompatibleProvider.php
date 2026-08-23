<?php

namespace ImranDevBd\AiHub\Providers;

use ImranDevBd\AiHub\Data\AiResponse;
use ImranDevBd\AiHub\Data\EmbeddingResponse;

/**
 * Thin OpenAI-compatible wrapper used by DeepSeek, Mistral, Groq, Ollama, OpenRouter, Grok, Together, Fireworks, Perplexity.
 */
class OpenAICompatibleProvider extends OpenAIProvider
{
    public function __construct(
        protected array $config = [],
        protected string $driverName = 'openai',
    ) {
        parent::__construct($config);
    }

    public function name(): string
    {
        return $this->driverName;
    }

    public function complete(array $payload): AiResponse
    {
        $payload['model'] = $payload['model'] ?? config('ai-hub.defaults.'.$this->driverName);

        $response = parent::complete($payload);

        return new AiResponse(
            content: $response->content,
            provider: $this->name(),
            model: $response->model,
            promptTokens: $response->promptTokens,
            completionTokens: $response->completionTokens,
            totalTokens: $response->totalTokens,
            costUsd: $response->costUsd,
            latencyMs: $response->latencyMs,
            attempts: $response->attempts,
            jsonRecovered: $response->jsonRecovered,
            success: $response->success,
            error: $response->error,
            raw: $response->raw,
            meta: $response->meta,
            toolCalls: $response->toolCalls,
        );
    }

    public function embed(array $payload): EmbeddingResponse
    {
        $response = parent::embed($payload);

        return new EmbeddingResponse(
            embeddings: $response->embeddings,
            provider: $this->name(),
            model: $response->model,
            promptTokens: $response->promptTokens,
            totalTokens: $response->totalTokens,
            costUsd: $response->costUsd,
            latencyMs: $response->latencyMs,
            attempts: $response->attempts,
            success: $response->success,
            error: $response->error,
            raw: $response->raw,
        );
    }

    protected function apiKey(): string
    {
        // Ollama often needs no real key
        if ($this->driverName === 'ollama') {
            $key = (string) ($this->config['api_key'] ?? 'ollama');

            return $key !== '' ? $key : 'ollama';
        }

        return parent::apiKey();
    }
}
