<?php

namespace ImranDevBd\AiHub;

use ImranDevBd\AiHub\Contracts\AIProviderContract;
use ImranDevBd\AiHub\Data\AiResponse;
use ImranDevBd\AiHub\Data\EmbeddingResponse;
use ImranDevBd\AiHub\Exceptions\AiHubException;
use ImranDevBd\AiHub\Jobs\TrackAiUsageJob;
use ImranDevBd\AiHub\Support\CostCalculator;
use ImranDevBd\AiHub\Support\JsonRecovery;
use ImranDevBd\AiHub\Support\RetryHandler;
use Throwable;

class PendingRequest
{
    protected string $providerName;
    protected ?string $model = null;
    protected ?string $prompt = null;
    protected array $messages = [];
    protected ?float $temperature = null;
    protected ?int $maxTokens = null;
    protected bool $recoverJson = false;
    protected bool $forceJsonObject = false;
    protected array $meta = [];
    protected ?string $jobTrace = null;
    protected ?string $apiKeyOverride = null;
    protected ?string $baseUrlOverride = null;
    protected bool $providerLocked = false;
    protected ?bool $failover = null;

    public function __construct(
        protected AIHubManager $manager,
        ?string $provider = null,
    ) {
        $this->providerName = $provider ?: (string) config('ai-hub.default', 'openai');
        if ($provider !== null) {
            $this->providerLocked = true;
        }
    }

    public function provider(string $provider): self
    {
        $this->providerName = $provider;
        $this->providerLocked = true;

        return $this;
    }

    public function failover(bool $enabled = true): self
    {
        $this->failover = $enabled;

        return $this;
    }

    public function withoutFailover(): self
    {
        return $this->failover(false);
    }

    public function model(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Override API key for this request only (does not save to settings).
     */
    public function apiKey(string $apiKey): self
    {
        $this->apiKeyOverride = $apiKey;
        $this->manager->forget($this->providerName);

        return $this;
    }

    /**
     * Alias of apiKey() for readability.
     */
    public function key(string $apiKey): self
    {
        return $this->apiKey($apiKey);
    }

    public function baseUrl(string $baseUrl): self
    {
        $this->baseUrlOverride = $baseUrl;
        $this->manager->forget($this->providerName);

        return $this;
    }

    /**
     * Shortcut: set provider + model (+ optional key) in one call.
     */
    public function using(string $provider, ?string $model = null, ?string $apiKey = null): self
    {
        $this->provider($provider);

        if ($model) {
            $this->model($model);
        }

        if ($apiKey) {
            $this->apiKey($apiKey);
        }

        return $this;
    }

    public function prompt(string $prompt): self
    {
        $this->prompt = $prompt;

        return $this;
    }

    public function messages(array $messages): self
    {
        $this->messages = $messages;

        return $this;
    }

    public function system(string $system): self
    {
        array_unshift($this->messages, ['role' => 'system', 'content' => $system]);

        return $this;
    }

    public function temperature(float $temperature): self
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function maxTokens(int $maxTokens): self
    {
        $this->maxTokens = $maxTokens;

        return $this;
    }

    public function recoverJson(bool $enabled = true): self
    {
        $this->recoverJson = $enabled;

        return $this;
    }

    public function asJsonObject(bool $enabled = true): self
    {
        $this->forceJsonObject = $enabled;

        return $this;
    }

    public function meta(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }

    public function forJob(string $job): self
    {
        $this->jobTrace = $job;
        $this->meta['job'] = $job;

        return $this;
    }

    public function send(): AiResponse
    {
        $chain = $this->resolveChain();
        $started = microtime(true);
        $tried = [];
        $lastException = null;

        foreach ($chain as $index => $providerName) {
            $this->providerName = $providerName;
            $provider = $this->resolveProvider();
            $model = $this->model ?: (string) config('ai-hub.defaults.'.$providerName, $this->defaultModel());
            $payload = $this->buildPayload($model);
            $retry = RetryHandler::fromConfig();
            $calculator = CostCalculator::fromConfig();
            $attempts = 1;
            $jsonRecovered = false;

            try {
                [$response, $attempts] = $retry->run(fn () => $provider->complete($payload));

                $content = $response->content;

                if ($this->recoverJson) {
                    [$decoded, $recovered] = JsonRecovery::make()->decode($content, true);
                    if ($decoded !== null) {
                        $content = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $content;
                        $jsonRecovered = $recovered;
                    }
                }

                $cost = $calculator->calculate(
                    $provider->name(),
                    $model,
                    $response->promptTokens,
                    $response->completionTokens
                );

                $tried[] = $providerName;

                $result = new AiResponse(
                    content: $content,
                    provider: $provider->name(),
                    model: $model,
                    promptTokens: $response->promptTokens,
                    completionTokens: $response->completionTokens,
                    totalTokens: $response->totalTokens,
                    costUsd: $cost,
                    latencyMs: round((microtime(true) - $started) * 1000, 2),
                    attempts: $attempts,
                    jsonRecovered: $jsonRecovered,
                    success: true,
                    raw: $response->raw,
                    meta: array_merge($this->meta, [
                        'priority_rank' => $index + 1,
                        'failover_tried' => $tried,
                    ]),
                );

                $this->log($result);

                return $result;
            } catch (Throwable $e) {
                $tried[] = $providerName;
                $lastException = $e;

                $this->log(new AiResponse(
                    content: '',
                    provider: $providerName,
                    model: $model,
                    latencyMs: round((microtime(true) - $started) * 1000, 2),
                    attempts: $attempts,
                    success: false,
                    error: $e->getMessage(),
                    meta: array_merge($this->meta, [
                        'priority_rank' => $index + 1,
                        'failover_tried' => $tried,
                        'failover_continue' => $index < count($chain) - 1,
                    ]),
                ));

                // try next provider in priority chain
                continue;
            }
        }

        throw new AiHubException(
            $lastException?->getMessage() ?: 'All AI providers in priority chain failed.',
            (int) ($lastException?->getCode() ?? 0),
            $lastException
        );
    }

    /**
     * Build ordered provider chain based on priority settings.
     *
     * @return array<int, string>
     */
    protected function resolveChain(): array
    {
        $failover = $this->failover ?? (bool) config('ai-hub.failover_enabled', true);

        if (! $failover || $this->apiKeyOverride) {
            return [$this->providerName];
        }

        // Explicit provider() without failover → single
        if ($this->providerLocked && $this->failover === false) {
            return [$this->providerName];
        }

        // Explicit provider with failover: try that first, then rest of priority
        $priority = array_values(array_unique(array_filter(
            (array) config('ai-hub.priority', \ImranDevBd\AiHub\Support\ProviderCatalog::keys())
        )));

        if ($priority === []) {
            return [$this->providerName];
        }

        $enabled = [];
        foreach ($priority as $name) {
            if (config("ai-hub.providers.{$name}.enabled", true) === false) {
                continue;
            }
            $enabled[] = $name;
        }

        if ($enabled === []) {
            return [$this->providerName];
        }

        if ($this->providerLocked) {
            $rest = array_values(array_filter($enabled, fn ($p) => $p !== $this->providerName));

            return array_values(array_unique(array_merge([$this->providerName], $rest)));
        }

        return $enabled;
    }

    public function embed(string|array|null $input = null): EmbeddingResponse
    {
        $provider = $this->resolveProvider();
        $model = $this->model ?: match ($this->providerName) {
            'openai' => 'text-embedding-3-small',
            'gemini' => 'text-embedding-004',
            default => $this->defaultModel(),
        };

        $payload = [
            'model' => $model,
            'input' => $input ?? $this->prompt ?? '',
            'prompt' => $input ?? $this->prompt ?? '',
        ];

        $retry = RetryHandler::fromConfig();
        $calculator = CostCalculator::fromConfig();
        $started = microtime(true);

        try {
            [$response, $attempts] = $retry->run(fn () => $provider->embed($payload));
            $cost = $calculator->calculate($provider->name(), $model, $response->promptTokens, 0);

            $result = new EmbeddingResponse(
                embeddings: $response->embeddings,
                provider: $provider->name(),
                model: $model,
                promptTokens: $response->promptTokens,
                totalTokens: $response->totalTokens,
                costUsd: $cost,
                latencyMs: round((microtime(true) - $started) * 1000, 2),
                attempts: $attempts,
                success: true,
                raw: $response->raw,
            );

            $this->log(new AiResponse(
                content: '[embedding]',
                provider: $result->provider,
                model: $result->model,
                promptTokens: $result->promptTokens,
                totalTokens: $result->totalTokens,
                costUsd: $result->costUsd,
                latencyMs: $result->latencyMs,
                attempts: $result->attempts,
                success: true,
                meta: array_merge($this->meta, ['type' => 'embedding']),
            ));

            return $result;
        } catch (Throwable $e) {
            $this->log(new AiResponse(
                content: '',
                provider: $this->providerName,
                model: $model,
                latencyMs: round((microtime(true) - $started) * 1000, 2),
                success: false,
                error: $e->getMessage(),
                meta: array_merge($this->meta, ['type' => 'embedding']),
            ));

            throw new AiHubException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @return \Generator<int, string>
     */
    public function stream(): \Generator
    {
        $provider = $this->resolveProvider();
        $model = $this->model ?: $this->defaultModel();
        $payload = $this->buildPayload($model);
        $started = microtime(true);
        $buffer = '';

        try {
            foreach ($provider->stream($payload) as $chunk) {
                $buffer .= $chunk;
                yield $chunk;
            }

            $this->log(new AiResponse(
                content: $buffer,
                provider: $provider->name(),
                model: $model,
                promptTokens: $this->estimate($this->prompt ?? ''),
                completionTokens: $this->estimate($buffer),
                totalTokens: $this->estimate(($this->prompt ?? '').$buffer),
                latencyMs: round((microtime(true) - $started) * 1000, 2),
                success: true,
                meta: array_merge($this->meta, ['type' => 'stream']),
            ));
        } catch (Throwable $e) {
            $this->log(new AiResponse(
                content: $buffer,
                provider: $this->providerName,
                model: $model,
                latencyMs: round((microtime(true) - $started) * 1000, 2),
                success: false,
                error: $e->getMessage(),
                meta: array_merge($this->meta, ['type' => 'stream']),
            ));

            throw new AiHubException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    protected function buildPayload(string $model): array
    {
        $payload = [
            'model' => $model,
            'prompt' => $this->prompt,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        ];

        if ($this->messages !== []) {
            $payload['messages'] = $this->messages;
        } elseif ($this->prompt !== null) {
            $payload['messages'] = [
                ['role' => 'user', 'content' => $this->prompt],
            ];
        }

        if ($this->forceJsonObject && $this->providerName === 'openai') {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        return array_filter($payload, fn ($v) => $v !== null);
    }

    protected function resolveProvider(): \ImranDevBd\AiHub\Contracts\AIProviderContract
    {
        $overrides = array_filter([
            'api_key' => $this->apiKeyOverride,
            'base_url' => $this->baseUrlOverride,
        ], fn ($v) => $v !== null && $v !== '');

        return $this->manager->resolve($this->providerName, $overrides);
    }

    protected function defaultModel(): string
    {
        return (string) config('ai-hub.defaults.'.$this->providerName, 'gpt-4o-mini');
    }

    protected function estimate(string $text): int
    {
        return max(0, (int) ceil(strlen($text) / 4));
    }

    protected function log(AiResponse $response): void
    {
        if (! config('ai-hub.logging.enabled', true)) {
            return;
        }

        $payload = array_merge($response->toArray(), [
            'job' => $this->jobTrace,
            'request_meta' => $this->meta,
        ]);

        $async = config('ai-hub.logging.async', 'after_response');

        // Mode 1: Dedicated Queue Worker (e.g. redis/database queue workers)
        if ($async === 'queue' || ($async === true && ! config('ai-hub.logging.after_response', true))) {
            TrackAiUsageJob::dispatch($payload)->onQueue(config('ai-hub.logging.queue', 'default'));

            return;
        }

        // Mode 2: After Response Execution (Default — zero queue workers required, zero latency for user)
        if ($async === 'after_response' || config('ai-hub.logging.after_response', true)) {
            TrackAiUsageJob::dispatchAfterResponse($payload);

            return;
        }

        // Mode 3: Direct Synchronous write
        TrackAiUsageJob::dispatchSync($payload);
    }
}
