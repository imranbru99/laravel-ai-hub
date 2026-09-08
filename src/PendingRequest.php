<?php

namespace ImranDevBd\AiHub;

use ImranDevBd\AiHub\Contracts\AIProviderContract;
use ImranDevBd\AiHub\Data\AiResponse;
use ImranDevBd\AiHub\Data\EmbeddingResponse;
use ImranDevBd\AiHub\Exceptions\AiHubException;
use ImranDevBd\AiHub\Jobs\TrackAiUsageJob;
use ImranDevBd\AiHub\Support\BudgetGuard;
use ImranDevBd\AiHub\Support\CostCalculator;
use ImranDevBd\AiHub\Support\JsonRecovery;
use ImranDevBd\AiHub\Support\ModelCapabilities;
use ImranDevBd\AiHub\Support\RetryHandler;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
    protected ?array $responseSchema = null;
    protected ?string $responseSchemaName = null;
    protected ?string $responseSchemaDescription = null;
    protected array $meta = [];
    protected ?string $jobTrace = null;
    protected ?string $apiKeyOverride = null;
    protected ?string $baseUrlOverride = null;
    protected bool $providerLocked = false;
    protected ?bool $failover = null;
    protected array $tools = [];
    protected mixed $toolChoice = null;
    protected array $images = [];
    protected ?int $cacheTtl = null;
    protected mixed $thinking = null;
    protected ?string $reasoningEffort = null;

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
        $this->getManager()->forget($this->providerName);

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

    /**
     * @param  array<int, array<string, mixed>>  $tools  OpenAI-style tool definitions
     */
    public function tools(array $tools): self
    {
        $this->tools = $tools;

        return $this;
    }

    public function toolChoice(string|array $choice): self
    {
        $this->toolChoice = $choice;

        return $this;
    }

    public function image(string $urlOrBase64): self
    {
        $this->images[] = $urlOrBase64;

        return $this;
    }

    /**
     * @param  array<int, string>  $urls
     */
    public function images(array $urls): self
    {
        foreach ($urls as $url) {
            $this->image((string) $url);
        }

        return $this;
    }

    public function cache(?int $ttl = 3600): self
    {
        $this->cacheTtl = $ttl ?? 3600;

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

    /**
     * Enforce strict JSON output adhering to a JSON schema.
     *
     * @param array<string, mixed> $schema JSON schema definition (e.g. ['type' => 'object', 'properties' => [...], 'required' => [...]])
     * @param string|null $name Schema name (used by OpenAI response_format.json_schema.name)
     * @param string|null $description Optional schema description
     */
    public function asJsonSchema(array $schema, ?string $name = 'response', ?string $description = null): self
    {
        $this->forceJsonObject = true;
        $this->responseSchema = $schema;
        $this->responseSchemaName = $name ?: 'response';
        $this->responseSchemaDescription = $description;

        return $this;
    }

    /**
     * Alias for asJsonSchema().
     *
     * @param array<string, mixed> $schema
     */
    public function structured(array $schema, ?string $name = 'response'): self
    {
        return $this->asJsonSchema($schema, $name);
    }

    /**
     * Enable or configure hybrid thinking / reasoning budget (for Gemini 3.8/3.7, Claude 3.7).
     *
     * @param int|bool|null $budget Budget in tokens (e.g. 2048, 4096), or true for default, or 0/false to disable.
     */
    public function thinking(int|bool|null $budget = true): self
    {
        $this->thinking = $budget ?? true;

        return $this;
    }

    /**
     * Explicitly disable hybrid thinking on reasoning models (e.g. Gemini 3.8/3.7 Flash, Claude 3.7 Sonnet).
     */
    public function withoutThinking(): self
    {
        $this->thinking = 0;

        return $this;
    }

    /**
     * Configure reasoning effort level for OpenAI o1/o3/o4 and GPT-5 models ('low', 'medium', 'high').
     */
    public function reasoningEffort(string $effort): self
    {
        $this->reasoningEffort = strtolower(trim($effort));

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

    /**
     * Generate completion (alias of send()).
     */
    public function generate(): AiResponse
    {
        return $this->send();
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
                $this->guardBudget($providerName);
                $cached = $this->cachedResponse($model, $payload);
                if ($cached) {
                    return $cached;
                }

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
                    toolCalls: $response->toolCalls,
                );

                $this->remember($result, $model, $payload);
                $this->log($result);

                $manager = $this->getManager();
                if ($manager instanceof \ImranDevBd\AiHub\Testing\AIHubFake) {
                    $manager->record($this, $result);
                }

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

                if ($e instanceof AiHubException && str_contains($e->getMessage(), 'cap of')) {
                    throw $e;
                }

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
     * @return array<int, string>
     */
    protected function resolveChain(): array
    {
        if ($this->getManager() instanceof \ImranDevBd\AiHub\Testing\AIHubFake) {
            return [$this->providerName];
        }

        $failover = $this->failover ?? (bool) config('ai-hub.failover_enabled', true);

        if (! $failover || $this->apiKeyOverride) {
            return [$this->providerName];
        }

        if ($this->providerLocked && $this->failover === false) {
            return [$this->providerName];
        }

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
        $this->guardBudget($this->providerName);

        $provider = $this->resolveProvider();
        $model = $this->model ?: match ($this->providerName) {
            'openai' => 'text-embedding-3-small',
            'gemini' => 'text-embedding-004',
            'azure' => 'text-embedding-3-small',
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

            if ($this->getManager() instanceof \ImranDevBd\AiHub\Testing\AIHubFake) {
                $this->getManager()->record($this, $result);
            }

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
        $this->guardBudget($this->providerName);

        $provider = $this->resolveProvider();
        $model = $this->model ?: $this->defaultModel();
        $payload = $this->buildPayload($model);
        $started = microtime(true);
        $buffer = '';

        $cached = $this->cachedResponse($model, $payload);
        if ($cached) {
            yield $cached->content;

            return;
        }

        try {
            foreach ($provider->stream($payload) as $chunk) {
                $buffer .= $chunk;
                yield $chunk;
            }

            $calculator = CostCalculator::fromConfig();
            $promptTokens = $this->estimate($this->prompt ?? '');
            $completionTokens = $this->estimate($buffer);
            $result = new AiResponse(
                content: $buffer,
                provider: $provider->name(),
                model: $model,
                promptTokens: $promptTokens,
                completionTokens: $completionTokens,
                totalTokens: $promptTokens + $completionTokens,
                costUsd: $calculator->calculate($provider->name(), $model, $promptTokens, $completionTokens),
                latencyMs: round((microtime(true) - $started) * 1000, 2),
                success: true,
                meta: array_merge($this->meta, ['type' => 'stream']),
            );

            $this->remember($result, $model, $payload);
            $this->log($result);

            $manager = $this->getManager();
            if ($manager instanceof \ImranDevBd\AiHub\Testing\AIHubFake) {
                $manager->record($this, $result);
            }
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

    /**
     * Consume the streaming generator with a closure callback.
     */
    public function streamRaw(callable $onChunk): void
    {
        foreach ($this->stream() as $chunk) {
            $onChunk($chunk);
        }
    }

    /**
     * Return a Server-Sent Events (SSE) StreamedResponse for immediate browser / HTTP streaming.
     *
     * @param callable(string): void|null $onChunk Optional chunk interceptor callback
     * @param array<string, string> $headers Additional HTTP response headers
     */
    public function streamResponse(?callable $onChunk = null, array $headers = []): StreamedResponse
    {
        $defaultHeaders = [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ];

        return new StreamedResponse(function () use ($onChunk) {
            $isTesting = function_exists('app') && app()->runningUnitTests();
            $flushBuffers = function () use ($isTesting) {
                if ($isTesting) {
                    return;
                }
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                if (function_exists('flush')) {
                    @flush();
                }
            };

            try {
                foreach ($this->stream() as $chunk) {
                    if ($onChunk !== null) {
                        $onChunk($chunk);
                    }

                    $data = json_encode(['chunk' => $chunk, 'done' => false], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    echo "data: {$data}\n\n";
                    $flushBuffers();
                }

                echo "data: [DONE]\n\n";
                $flushBuffers();
            } catch (Throwable $e) {
                $errorData = json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                echo "data: {$errorData}\n\n";
                $flushBuffers();
            }
        }, 200, array_merge($defaultHeaders, $headers));
    }

    /*
    |--------------------------------------------------------------------------
    | Inspection Getters (Testing, Middleware & Debugging)
    |--------------------------------------------------------------------------
    */

    public function getProvider(): string
    {
        return $this->providerName;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function getPrompt(): ?string
    {
        return $this->prompt;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    public function getMaxTokens(): ?int
    {
        return $this->maxTokens;
    }

    public function getTools(): array
    {
        return $this->tools;
    }

    public function getToolChoice(): mixed
    {
        return $this->toolChoice;
    }

    public function getImages(): array
    {
        return $this->images;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function getResponseSchema(): ?array
    {
        return $this->responseSchema;
    }

    public function isRecoverJson(): bool
    {
        return $this->recoverJson;
    }

    public function isForceJsonObject(): bool
    {
        return $this->forceJsonObject;
    }

    public function hasJobTrace(): ?string
    {
        return $this->jobTrace;
    }

    protected function buildPayload(string $model): array
    {
        $payload = [
            'model' => $model,
            'prompt' => $this->prompt,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        ];

        $messages = $this->messages;
        if ($messages === [] && $this->prompt !== null) {
            $messages = [
                ['role' => 'user', 'content' => $this->prompt],
            ];
        }

        if ($this->images !== []) {
            $messages = $this->attachImages($messages);
        }

        if ($messages !== []) {
            $payload['messages'] = $messages;
        }

        if ($this->tools !== []) {
            $payload['tools'] = $this->tools;
        }

        if ($this->toolChoice !== null) {
            $payload['tool_choice'] = $this->toolChoice;
        }

        if ($this->responseSchema !== null) {
            $payload['response_schema'] = $this->responseSchema;
            $payload['response_schema_name'] = $this->responseSchemaName ?? 'response';
            $payload['response_schema_description'] = $this->responseSchemaDescription;

            if (in_array($this->providerName, ['openai', 'azure', 'openrouter', 'together', 'fireworks'], true)) {
                $payload['response_format'] = [
                    'type' => 'json_schema',
                    'json_schema' => array_filter([
                        'name' => $this->responseSchemaName ?? 'response',
                        'description' => $this->responseSchemaDescription,
                        'schema' => $this->responseSchema,
                        'strict' => true,
                    ], fn ($v) => $v !== null),
                ];
            }
        } elseif ($this->forceJsonObject && in_array($this->providerName, ['openai', 'azure', 'openrouter', 'together', 'fireworks'], true)) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        if ($this->thinking !== null) {
            $payload['thinking'] = $this->thinking;
        }

        if ($this->reasoningEffort !== null) {
            $payload['reasoning_effort'] = $this->reasoningEffort;
        }

        return ModelCapabilities::stripUnsupported(array_filter($payload, fn ($v) => $v !== null));
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @return array<int, array<string, mixed>>
     */
    protected function attachImages(array $messages): array
    {
        if ($messages === []) {
            $messages[] = ['role' => 'user', 'content' => $this->prompt ?? ''];
        }

        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') !== 'user') {
                continue;
            }

            $content = $messages[$i]['content'] ?? '';
            $parts = is_array($content) ? $content : [['type' => 'text', 'text' => (string) $content]];
            foreach ($this->images as $image) {
                $parts[] = $this->imagePart((string) $image);
            }
            $messages[$i]['content'] = $parts;
            break;
        }

        return $messages;
    }

    /**
     * @return array{type: string, image_url: array{url: string}}
     */
    protected function imagePart(string $image): array
    {
        $url = $image;
        if (! str_starts_with($image, 'http') && ! str_starts_with($image, 'data:') && preg_match('/^[A-Za-z0-9+\/=]{80,}$/', $image)) {
            $url = 'data:image/jpeg;base64,'.$image;
        }

        return [
            'type' => 'image_url',
            'image_url' => ['url' => $url],
        ];
    }

    public function getManager(): AIHubManager
    {
        if (function_exists('app') && app()->bound('ai-hub')) {
            $bound = app('ai-hub');
            if ($bound instanceof AIHubManager) {
                return $bound;
            }
        }

        if (function_exists('app') && app()->bound(AIHubManager::class)) {
            $bound = app(AIHubManager::class);
            if ($bound instanceof AIHubManager) {
                return $bound;
            }
        }

        return $this->manager;
    }

    protected function resolveProvider(): AIProviderContract
    {
        $overrides = array_filter([
            'api_key' => $this->apiKeyOverride,
            'base_url' => $this->baseUrlOverride,
        ], fn ($v) => $v !== null && $v !== '');

        return $this->getManager()->resolve($this->providerName, $overrides);
    }

    protected function defaultModel(): string
    {
        return (string) config('ai-hub.defaults.'.$this->providerName, 'gpt-4o-mini');
    }

    protected function estimate(string $text): int
    {
        return max(0, (int) ceil(strlen($text) / 4));
    }

    protected function guardBudget(string $provider): void
    {
        $warnings = app(BudgetGuard::class)->assert($provider, $this->jobTrace);
        if ($warnings !== []) {
            $this->meta['budget_warnings'] = $warnings;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function cachedResponse(string $model, array $payload): ?AiResponse
    {
        if (! $this->cacheTtl) {
            return null;
        }

        $hit = Cache::get($this->cacheKey($model, $payload));
        if (! is_array($hit)) {
            return null;
        }

        $result = AiResponse::fromArray(array_merge($hit, [
            'cost_usd' => 0,
            'meta' => array_merge($hit['meta'] ?? [], $this->meta, [
                'type' => 'cache_hit',
                'cache' => true,
            ]),
        ]));

        $this->log($result);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function remember(AiResponse $response, string $model, array $payload): void
    {
        if (! $this->cacheTtl || ! $response->success) {
            return;
        }

        Cache::put($this->cacheKey($model, $payload), $response->toArray(), $this->cacheTtl);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function cacheKey(string $model, array $payload): string
    {
        return 'ai-hub.cache.'.sha1(json_encode([
            $this->providerName,
            $model,
            $payload,
        ]));
    }

    protected function log(AiResponse $response): void
    {
        if (! config('ai-hub.logging.enabled', true)) {
            return;
        }

        $payload = array_merge($response->toArray(), [
            'job' => $this->jobTrace,
            'request_meta' => $response->meta ?: $this->meta,
        ]);

        $async = config('ai-hub.logging.async', 'after_response');

        if ($async === 'queue' || ($async === true && ! config('ai-hub.logging.after_response', true))) {
            TrackAiUsageJob::dispatch($payload)->onQueue(config('ai-hub.logging.queue', 'default'));

            return;
        }

        if ($async === 'after_response' || config('ai-hub.logging.after_response', true)) {
            TrackAiUsageJob::dispatchAfterResponse($payload);

            return;
        }

        TrackAiUsageJob::dispatchSync($payload);
    }
}
