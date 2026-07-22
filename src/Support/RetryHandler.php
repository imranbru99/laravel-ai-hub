<?php

namespace ImranDevBd\AiHub\Support;

use Closure;
use ImranDevBd\AiHub\Exceptions\AiHubException;
use Illuminate\Http\Client\RequestException;
use Throwable;

class RetryHandler
{
    public function __construct(
        protected bool $enabled = true,
        protected int $maxAttempts = 3,
        protected int $baseDelayMs = 500,
        protected float $multiplier = 2.0,
        protected array $retryOn = [429, 500, 502, 503, 504],
    ) {}

    public static function fromConfig(?array $config = null): self
    {
        $config ??= config('ai-hub.retry', []);

        return new self(
            enabled: (bool) ($config['enabled'] ?? true),
            maxAttempts: (int) ($config['max_attempts'] ?? 3),
            baseDelayMs: (int) ($config['base_delay_ms'] ?? 500),
            multiplier: (float) ($config['multiplier'] ?? 2.0),
            retryOn: $config['retry_on'] ?? [429, 500, 502, 503, 504],
        );
    }

    /**
     * @template T
     * @param  Closure(): T  $callback
     * @return array{0: T, 1: int}
     */
    public function run(Closure $callback): array
    {
        $attempts = 0;
        $lastException = null;
        $max = $this->enabled ? max(1, $this->maxAttempts) : 1;

        while ($attempts < $max) {
            $attempts++;

            try {
                return [$callback(), $attempts];
            } catch (Throwable $e) {
                $lastException = $e;

                if ($attempts >= $max || ! $this->shouldRetry($e)) {
                    break;
                }

                usleep($this->delayMs($attempts) * 1000);
            }
        }

        throw $lastException instanceof Throwable
            ? $lastException
            : new AiHubException('AI request failed after retries.');
    }

    protected function shouldRetry(Throwable $e): bool
    {
        if ($e instanceof RequestException) {
            $status = $e->response?->status();

            return $status !== null && in_array($status, $this->retryOn, true);
        }

        $message = strtolower($e->getMessage());

        return str_contains($message, 'timeout')
            || str_contains($message, 'rate limit')
            || str_contains($message, '429')
            || str_contains($message, 'temporarily');
    }

    protected function delayMs(int $attempt): int
    {
        $delay = (int) round($this->baseDelayMs * ($this->multiplier ** max(0, $attempt - 1)));

        // jitter ±20%
        $jitter = (int) round($delay * (mt_rand(80, 120) / 100));

        return max(50, $jitter);
    }
}
