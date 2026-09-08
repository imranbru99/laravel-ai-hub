<?php

namespace ImranDevBd\AiHub\Testing;

use Closure;
use ImranDevBd\AiHub\AIHubManager;
use ImranDevBd\AiHub\Contracts\AIProviderContract;
use ImranDevBd\AiHub\Data\AiResponse;
use ImranDevBd\AiHub\Data\EmbeddingResponse;
use ImranDevBd\AiHub\Exceptions\AiHubException;
use ImranDevBd\AiHub\PendingRequest;
use PHPUnit\Framework\Assert as PHPUnit;

class AIHubFake extends AIHubManager
{
    /**
     * @var array<int, array{request: PendingRequest, response: AiResponse|EmbeddingResponse}>
     */
    protected array $recorded = [];

    /**
     * @var array<int|string, mixed>
     */
    protected array $responses = [];

    /**
     * @param array<int|string, mixed>|Closure|AiResponse|string $responses
     */
    public function __construct(array|Closure|AiResponse|string $responses = [])
    {
        if ($responses instanceof Closure || $responses instanceof AiResponse || is_string($responses)) {
            $this->responses = [$responses];
        } else {
            $this->responses = $responses;
        }
    }

    /**
     * Resolve provider into an in-memory mock provider.
     */
    public function resolve(?string $name = null, array $overrides = []): AIProviderContract
    {
        $providerName = $name ?: (string) config('ai-hub.default', 'openai');

        return new class($this, $providerName) implements AIProviderContract {
            public function __construct(
                protected AIHubFake $fake,
                protected string $providerName,
            ) {}

            public function name(): string
            {
                return $this->providerName;
            }

            public function complete(array $payload): AiResponse
            {
                return $this->fake->handleRequest($this->providerName, $payload);
            }

            public function stream(array $payload): \Generator
            {
                $response = $this->fake->handleRequest($this->providerName, $payload);
                $content = $response->content;

                // Break content into small chunks to simulate real streaming
                $chunks = str_split($content, max(1, (int) ceil(strlen($content) / 5)));
                foreach ($chunks as $chunk) {
                    yield $chunk;
                }
            }

            public function embed(array $payload): EmbeddingResponse
            {
                return $this->fake->handleEmbeddingRequest($this->providerName, $payload);
            }
        };
    }

    /**
     * Intercept and resolve the response for a completion request.
     */
    public function handleRequest(string $provider, array $payload): AiResponse
    {
        $response = $this->findNextResponse($provider, $payload);

        if (is_string($response)) {
            $response = new AiResponse(
                content: $response,
                provider: $provider,
                model: (string) ($payload['model'] ?? 'fake-model'),
                promptTokens: 10,
                completionTokens: 20,
                totalTokens: 30,
                costUsd: 0.00005,
                latencyMs: 12.0,
                success: true,
            );
        } elseif (is_array($response)) {
            $response = AiResponse::fromArray(array_merge([
                'provider' => $provider,
                'model' => (string) ($payload['model'] ?? 'fake-model'),
                'success' => true,
            ], $response));
        } elseif ($response instanceof Closure) {
            $result = $response($payload, $provider);
            if (is_string($result)) {
                $response = new AiResponse(
                    content: $result,
                    provider: $provider,
                    model: (string) ($payload['model'] ?? 'fake-model'),
                );
            } elseif ($result instanceof AiResponse) {
                $response = $result;
            } else {
                $response = AiResponse::fromArray((array) $result);
            }
        } elseif (! ($response instanceof AiResponse)) {
            $response = new AiResponse(
                content: 'Fake AI response',
                provider: $provider,
                model: (string) ($payload['model'] ?? 'fake-model'),
                promptTokens: 10,
                completionTokens: 20,
                totalTokens: 30,
                costUsd: 0.00005,
                latencyMs: 12.0,
                success: true,
            );
        }

        return $response;
    }

    /**
     * Intercept and resolve the response for an embedding request.
     */
    public function handleEmbeddingRequest(string $provider, array $payload): EmbeddingResponse
    {
        return new EmbeddingResponse(
            embeddings: [[0.0123, -0.0456, 0.0789, 0.1011]],
            provider: $provider,
            model: (string) ($payload['model'] ?? 'fake-embed-model'),
            promptTokens: 8,
            totalTokens: 8,
            costUsd: 0.00001,
            latencyMs: 5.0,
            success: true,
        );
    }

    protected function findNextResponse(string $provider, array $payload): mixed
    {
        // 1. Check for specific provider or model key
        $model = (string) ($payload['model'] ?? '');
        if (array_key_exists($model, $this->responses)) {
            return $this->responses[$model];
        }

        if (array_key_exists($provider, $this->responses)) {
            return $this->responses[$provider];
        }

        // 2. FIFO response queue
        if (array_key_exists(0, $this->responses)) {
            return array_shift($this->responses);
        }

        // 3. Fallback
        return 'Fake AI response';
    }

    /**
     * Record a completed request for assertion.
     */
    public function record(PendingRequest $request, AiResponse|EmbeddingResponse $response): void
    {
        $this->recorded[] = [
            'request' => $request,
            'response' => $response,
        ];
    }

    /**
     * Assert that a request matching the callback was sent.
     *
     * @param (Closure(PendingRequest): bool)|string|null $callback
     */
    public function assertSent(Closure|string|null $callback = null): void
    {
        if ($callback === null) {
            PHPUnit::assertTrue(
                count($this->recorded) > 0,
                'The expected AI request was not sent.'
            );

            return;
        }

        if (is_string($callback)) {
            $promptOrProvider = $callback;
            $callback = function (PendingRequest $req) use ($promptOrProvider) {
                return $req->getProvider() === $promptOrProvider
                    || $req->getModel() === $promptOrProvider
                    || str_contains((string) $req->getPrompt(), $promptOrProvider);
            };
        }

        $matched = array_filter($this->recorded, fn ($item) => $callback($item['request'], $item['response']));

        PHPUnit::assertTrue(
            count($matched) > 0,
            'The expected AI request was not sent.'
        );
    }

    /**
     * Assert that a request matching the callback was not sent.
     *
     * @param (Closure(PendingRequest): bool)|string $callback
     */
    public function assertNotSent(Closure|string $callback): void
    {
        if (is_string($callback)) {
            $target = $callback;
            $callback = function (PendingRequest $req) use ($target) {
                return $req->getProvider() === $target
                    || $req->getModel() === $target
                    || str_contains((string) $req->getPrompt(), $target);
            };
        }

        $matched = array_filter($this->recorded, fn ($item) => $callback($item['request'], $item['response']));

        PHPUnit::assertCount(
            0,
            $matched,
            'An unexpected AI request was sent.'
        );
    }

    /**
     * Assert the total number of requests sent.
     */
    public function assertSentCount(int $count): void
    {
        PHPUnit::assertCount(
            $count,
            $this->recorded,
            "Expected {$count} AI requests to be sent, but ".count($this->recorded).' were sent.'
        );
    }

    /**
     * Assert that no requests were sent.
     */
    public function assertNothingSent(): void
    {
        $this->assertSentCount(0);
    }

    /**
     * Retrieve all recorded request instances.
     *
     * @return array<int, array{request: PendingRequest, response: AiResponse|EmbeddingResponse}>
     */
    public function recorded(): array
    {
        return $this->recorded;
    }
}
