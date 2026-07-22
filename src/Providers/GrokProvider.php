<?php

namespace ImranDevBd\AiHub\Providers;

use ImranDevBd\AiHub\Data\AiResponse;
use ImranDevBd\AiHub\Data\EmbeddingResponse;

/**
 * Grok (xAI) — OpenAI-compatible Chat Completions API.
 */
class GrokProvider extends OpenAIProvider
{
    public function name(): string
    {
        return 'grok';
    }

    public function complete(array $payload): AiResponse
    {
        $payload['model'] = $payload['model'] ?? config('ai-hub.defaults.grok');

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
        );
    }

    public function embed(array $payload): EmbeddingResponse
    {
        $payload['model'] = $payload['model'] ?? 'grok-embedding';

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
}
