<?php

namespace ImranDevBd\AiHub\Data;

class EmbeddingResponse
{
    /**
     * @param  array<int, float>|array<int, array<int, float>>  $embeddings
     */
    public function __construct(
        public readonly array $embeddings,
        public readonly string $provider,
        public readonly string $model,
        public readonly int $promptTokens = 0,
        public readonly int $totalTokens = 0,
        public readonly float $costUsd = 0.0,
        public readonly float $latencyMs = 0.0,
        public readonly int $attempts = 1,
        public readonly bool $success = true,
        public readonly ?string $error = null,
        public readonly array $raw = [],
    ) {}

    public function first(): array
    {
        $first = $this->embeddings[0] ?? [];

        return is_array($first) && array_is_list($first) && isset($first[0]) && is_float($first[0] ?? null)
            ? $first
            : (is_array($first) ? $first : []);
    }

    public function toArray(): array
    {
        return [
            'embeddings' => $this->embeddings,
            'provider' => $this->provider,
            'model' => $this->model,
            'prompt_tokens' => $this->promptTokens,
            'total_tokens' => $this->totalTokens,
            'cost_usd' => $this->costUsd,
            'latency_ms' => $this->latencyMs,
            'attempts' => $this->attempts,
            'success' => $this->success,
            'error' => $this->error,
        ];
    }
}
