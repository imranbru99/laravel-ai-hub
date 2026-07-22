<?php

namespace ImranDevBd\AiHub\Providers;

use ImranDevBd\AiHub\Contracts\AIProviderContract;
use ImranDevBd\AiHub\Exceptions\AiHubException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

abstract class AbstractProvider implements AIProviderContract
{
    public function __construct(
        protected array $config = [],
    ) {}

    abstract public function name(): string;

    protected function apiKey(): string
    {
        $key = (string) ($this->config['api_key'] ?? '');

        if ($key === '') {
            throw new AiHubException(strtoupper($this->name()).' API key is missing. Set it in config/ai-hub.php or .env');
        }

        return $key;
    }

    protected function baseUrl(): string
    {
        return rtrim((string) ($this->config['base_url'] ?? ''), '/');
    }

    protected function timeout(): int
    {
        return (int) ($this->config['timeout'] ?? 60);
    }

    protected function http(): PendingRequest
    {
        return Http::timeout($this->timeout())
            ->acceptJson()
            ->asJson();
    }

    protected function estimateTokens(string $text): int
    {
        // rough heuristic ~4 chars/token
        return max(1, (int) ceil(strlen($text) / 4));
    }
}
