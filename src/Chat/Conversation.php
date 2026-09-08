<?php

namespace ImranDevBd\AiHub\Chat;

use ImranDevBd\AiHub\AIHubManager;
use ImranDevBd\AiHub\Data\AiResponse;
use ImranDevBd\AiHub\PendingRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Conversation
{
    /**
     * @var array<int, array{role: string, content: string|array, meta?: array}>
     */
    protected array $messages = [];

    protected ?string $provider = null;
    protected ?string $model = null;
    protected ?string $systemPrompt = null;
    protected ?float $temperature = null;
    protected ?int $maxTokens = null;
    protected ?string $apiKey = null;
    protected ?AiResponse $latestResponse = null;
    protected int $totalPromptTokens = 0;
    protected int $totalCompletionTokens = 0;
    protected float $totalCostUsd = 0.0;

    public function __construct(
        protected AIHubManager $manager,
        ?string $provider = null,
        ?string $model = null,
    ) {
        $this->provider = $provider;
        $this->model = $model;
    }

    public function provider(string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    public function model(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function apiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

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

    public function system(string $prompt): self
    {
        $this->systemPrompt = $prompt;

        return $this;
    }

    public function addMessage(string $role, string|array $content, array $meta = []): self
    {
        $entry = [
            'role' => $role,
            'content' => $content,
        ];

        if ($meta !== []) {
            $entry['meta'] = $meta;
        }

        $this->messages[] = $entry;

        return $this;
    }

    /**
     * Send a user message, record it, generate AI response, record assistant message, and return response.
     */
    public function reply(string $userMessage): AiResponse
    {
        $this->addMessage('user', $userMessage);

        $request = $this->buildPendingRequest();
        $response = $request->generate();

        $this->latestResponse = $response;
        $this->totalPromptTokens += $response->promptTokens;
        $this->totalCompletionTokens += $response->completionTokens;
        $this->totalCostUsd += $response->costUsd;

        if ($response->content !== '') {
            $this->addMessage('assistant', $response->content, [
                'model' => $response->model,
                'provider' => $response->provider,
                'cost_usd' => $response->costUsd,
            ]);
        }

        return $response;
    }

    /**
     * Stream reply chunks via generator, updating conversation state once finished.
     *
     * @return \Generator<int, string>
     */
    public function streamReply(string $userMessage): \Generator
    {
        $this->addMessage('user', $userMessage);

        $request = $this->buildPendingRequest();
        $buffer = '';

        foreach ($request->stream() as $chunk) {
            $buffer .= $chunk;
            yield $chunk;
        }

        if ($buffer !== '') {
            $this->addMessage('assistant', $buffer);
        }
    }

    /**
     * Return a Server-Sent Events (SSE) StreamedResponse for real-time browser consumption.
     */
    public function streamResponse(string $userMessage, ?callable $onChunk = null, array $headers = []): StreamedResponse
    {
        $this->addMessage('user', $userMessage);
        $request = $this->buildPendingRequest();

        $fullBuffer = '';
        $wrappedChunk = function (string $chunk) use ($onChunk, &$fullBuffer) {
            $fullBuffer .= $chunk;
            if ($onChunk !== null) {
                $onChunk($chunk);
            }
        };

        $response = $request->streamResponse($wrappedChunk, $headers);

        // Note: when StreamedResponse executes callback, user receives SSE
        return $response;
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

    /**
     * Build PendingRequest populated with system prompt and history.
     */
    public function buildPendingRequest(): PendingRequest
    {
        $manager = $this->getManager();
        $req = $this->provider !== null
            ? $manager->provider($this->provider)
            : $manager->provider();

        if ($this->model !== null) {
            $req->model($this->model);
        }

        if ($this->apiKey !== null) {
            $req->apiKey($this->apiKey);
        }

        if ($this->temperature !== null) {
            $req->temperature($this->temperature);
        }

        if ($this->maxTokens !== null) {
            $req->maxTokens($this->maxTokens);
        }

        $allMessages = [];
        if ($this->systemPrompt !== null && $this->systemPrompt !== '') {
            $allMessages[] = ['role' => 'system', 'content' => $this->systemPrompt];
        }

        foreach ($this->messages as $msg) {
            $allMessages[] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }

        $req->messages($allMessages);

        return $req;
    }

    /**
     * @return array<int, array{role: string, content: string|array, meta?: array}>
     */
    public function messages(): array
    {
        return $this->messages;
    }

    /**
     * Alias of messages().
     *
     * @return array<int, array{role: string, content: string|array, meta?: array}>
     */
    public function history(): array
    {
        return $this->messages();
    }

    public function latestResponse(): ?AiResponse
    {
        return $this->latestResponse;
    }

    public function totalTokens(): int
    {
        return $this->totalPromptTokens + $this->totalCompletionTokens;
    }

    public function totalPromptTokens(): int
    {
        return $this->totalPromptTokens;
    }

    public function totalCompletionTokens(): int
    {
        return $this->totalCompletionTokens;
    }

    public function totalCost(): float
    {
        return $this->totalCostUsd;
    }

    public function clear(): self
    {
        $this->messages = [];
        $this->latestResponse = null;
        $this->totalPromptTokens = 0;
        $this->totalCompletionTokens = 0;
        $this->totalCostUsd = 0.0;

        return $this;
    }

    /**
     * Export conversation state as serializable array (perfect for caching or saving to DB).
     */
    public function export(): array
    {
        return [
            'provider' => $this->provider,
            'model' => $this->model,
            'system' => $this->systemPrompt,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
            'messages' => $this->messages,
            'total_prompt_tokens' => $this->totalPromptTokens,
            'total_completion_tokens' => $this->totalCompletionTokens,
            'total_cost_usd' => $this->totalCostUsd,
        ];
    }

    /**
     * Hydrate conversation state from exported array.
     */
    public function load(array $data): self
    {
        $this->provider = $data['provider'] ?? $this->provider;
        $this->model = $data['model'] ?? $this->model;
        $this->systemPrompt = $data['system'] ?? $this->systemPrompt;
        $this->temperature = isset($data['temperature']) ? (float) $data['temperature'] : $this->temperature;
        $this->maxTokens = isset($data['max_tokens']) ? (int) $data['max_tokens'] : $this->maxTokens;
        $this->messages = is_array($data['messages'] ?? null) ? $data['messages'] : [];
        $this->totalPromptTokens = (int) ($data['total_prompt_tokens'] ?? 0);
        $this->totalCompletionTokens = (int) ($data['total_completion_tokens'] ?? 0);
        $this->totalCostUsd = (float) ($data['total_cost_usd'] ?? 0.0);

        return $this;
    }
}
