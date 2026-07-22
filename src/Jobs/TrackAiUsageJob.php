<?php

namespace ImranDevBd\AiHub\Jobs;

use ImranDevBd\AiHub\Models\AiRequestLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class TrackAiUsageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $payload,
    ) {}

    public function handle(): void
    {
        try {
            $content = (string) ($this->payload['content'] ?? '');
            $meta = $this->payload['request_meta'] ?? ($this->payload['meta'] ?? []);

            AiRequestLog::query()->create([
                'provider' => $this->payload['provider'] ?? 'unknown',
                'model' => $this->payload['model'] ?? 'unknown',
                'type' => $meta['type'] ?? 'complete',
                'job' => $this->payload['job'] ?? ($meta['job'] ?? null),
                'success' => (bool) ($this->payload['success'] ?? false),
                'json_recovered' => (bool) ($this->payload['json_recovered'] ?? false),
                'prompt_tokens' => (int) ($this->payload['prompt_tokens'] ?? 0),
                'completion_tokens' => (int) ($this->payload['completion_tokens'] ?? 0),
                'total_tokens' => (int) ($this->payload['total_tokens'] ?? 0),
                'cost_usd' => (float) ($this->payload['cost_usd'] ?? 0),
                'latency_ms' => (float) ($this->payload['latency_ms'] ?? 0),
                'attempts' => (int) ($this->payload['attempts'] ?? 1),
                'error' => $this->payload['error'] ?? null,
                'content_preview' => mb_substr($content, 0, 500),
                'meta' => is_array($meta) ? $meta : [],
            ]);
        } catch (Throwable) {
            // never break the app because of logging
        }
    }
}
