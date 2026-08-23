<?php

namespace ImranDevBd\AiHub\Data;

class AiResponse
{
    public function __construct(
        public readonly string $content,
        public readonly string $provider,
        public readonly string $model,
        public readonly int $promptTokens = 0,
        public readonly int $completionTokens = 0,
        public readonly int $totalTokens = 0,
        public readonly float $costUsd = 0.0,
        public readonly float $latencyMs = 0.0,
        public readonly int $attempts = 1,
        public readonly bool $jsonRecovered = false,
        public readonly bool $success = true,
        public readonly ?string $error = null,
        public readonly array $raw = [],
        public readonly array $meta = [],
        public readonly array $toolCalls = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            content: (string) ($data['content'] ?? ''),
            provider: (string) ($data['provider'] ?? 'unknown'),
            model: (string) ($data['model'] ?? 'unknown'),
            promptTokens: (int) ($data['prompt_tokens'] ?? 0),
            completionTokens: (int) ($data['completion_tokens'] ?? 0),
            totalTokens: (int) ($data['total_tokens'] ?? 0),
            costUsd: (float) ($data['cost_usd'] ?? 0),
            latencyMs: (float) ($data['latency_ms'] ?? 0),
            attempts: (int) ($data['attempts'] ?? 1),
            jsonRecovered: (bool) ($data['json_recovered'] ?? false),
            success: (bool) ($data['success'] ?? true),
            error: $data['error'] ?? null,
            raw: is_array($data['raw'] ?? null) ? $data['raw'] : [],
            meta: is_array($data['meta'] ?? null) ? $data['meta'] : [],
            toolCalls: is_array($data['tool_calls'] ?? null) ? $data['tool_calls'] : [],
        );
    }

    public function json(?bool $assoc = true): mixed
    {
        $decoded = json_decode($this->content, $assoc ?? true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $decoded;
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'provider' => $this->provider,
            'model' => $this->model,
            'prompt_tokens' => $this->promptTokens,
            'completion_tokens' => $this->completionTokens,
            'total_tokens' => $this->totalTokens,
            'cost_usd' => $this->costUsd,
            'latency_ms' => $this->latencyMs,
            'attempts' => $this->attempts,
            'json_recovered' => $this->jsonRecovered,
            'success' => $this->success,
            'error' => $this->error,
            'meta' => $this->meta,
            'tool_calls' => $this->toolCalls,
        ];
    }
}
