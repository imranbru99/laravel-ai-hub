<?php

namespace ImranDevBd\AiHub;

use ImranDevBd\AiHub\Contracts\AIProviderContract;
use ImranDevBd\AiHub\Exceptions\AiHubException;
use ImranDevBd\AiHub\Providers\ClaudeProvider;
use ImranDevBd\AiHub\Providers\GeminiProvider;
use ImranDevBd\AiHub\Providers\OpenAICompatibleProvider;
use ImranDevBd\AiHub\Providers\OpenAIProvider;
use ImranDevBd\AiHub\Support\Analytics;
use ImranDevBd\AiHub\Support\ProviderCatalog;
use ImranDevBd\AiHub\Support\SettingsStore;

class AIHubManager
{
    protected array $drivers = [];

    public function provider(?string $name = null): PendingRequest
    {
        return new PendingRequest($this, $name);
    }

    public function openai(?string $model = null, ?string $apiKey = null): PendingRequest
    {
        return $this->provider('openai')->using('openai', $model, $apiKey);
    }

    public function gemini(?string $model = null, ?string $apiKey = null): PendingRequest
    {
        return $this->provider('gemini')->using('gemini', $model, $apiKey);
    }

    public function claude(?string $model = null, ?string $apiKey = null): PendingRequest
    {
        return $this->provider('claude')->using('claude', $model, $apiKey);
    }

    public function grok(?string $model = null, ?string $apiKey = null): PendingRequest
    {
        return $this->provider('grok')->using('grok', $model, $apiKey);
    }

    public function deepseek(?string $model = null, ?string $apiKey = null): PendingRequest
    {
        return $this->provider('deepseek')->using('deepseek', $model, $apiKey);
    }

    public function mistral(?string $model = null, ?string $apiKey = null): PendingRequest
    {
        return $this->provider('mistral')->using('mistral', $model, $apiKey);
    }

    public function groq(?string $model = null, ?string $apiKey = null): PendingRequest
    {
        return $this->provider('groq')->using('groq', $model, $apiKey);
    }

    public function ollama(?string $model = null, ?string $apiKey = null): PendingRequest
    {
        return $this->provider('ollama')->using('ollama', $model, $apiKey);
    }

    public function openrouter(?string $model = null, ?string $apiKey = null): PendingRequest
    {
        return $this->provider('openrouter')->using('openrouter', $model, $apiKey);
    }

    public function model(string $model): PendingRequest
    {
        return $this->provider()->model($model);
    }

    public function prompt(string $prompt): PendingRequest
    {
        return $this->provider()->prompt($prompt);
    }

    public function apiKey(string $apiKey): PendingRequest
    {
        return $this->provider()->apiKey($apiKey);
    }

    public function configure(string $provider, ?string $apiKey = null, ?string $model = null, bool $makeDefault = true): array
    {
        $saved = app(SettingsStore::class)->setProvider($provider, $apiKey, $model, $makeDefault);
        app(SettingsStore::class)->applyToConfig();
        $this->forget($provider);
        $this->forget();

        return $saved;
    }

    public function analytics(): Analytics
    {
        return app(Analytics::class);
    }

    public function settings(): SettingsStore
    {
        return app(SettingsStore::class);
    }

    public function resolve(?string $name = null, array $overrides = []): AIProviderContract
    {
        $name = $name ?: (string) config('ai-hub.default', 'openai');
        $cacheKey = $name.md5(json_encode($overrides));

        if ($overrides === [] && isset($this->drivers[$name])) {
            return $this->drivers[$name];
        }

        if ($overrides !== [] && isset($this->drivers[$cacheKey])) {
            return $this->drivers[$cacheKey];
        }

        $config = config('ai-hub.providers.'.$name);

        if (! is_array($config)) {
            throw new AiHubException("AI Hub provider [{$name}] is not configured.");
        }

        $config = array_merge($config, $overrides);
        $driver = $config['driver'] ?? $name;

        $instance = match ($driver) {
            'openai' => new OpenAIProvider($config),
            'gemini' => new GeminiProvider($config),
            'claude' => new ClaudeProvider($config),
            'grok', 'deepseek', 'mistral', 'groq', 'ollama', 'openrouter', 'openai-compatible' => new OpenAICompatibleProvider($config, $name),
            default => throw new AiHubException("Unsupported AI Hub driver [{$driver}]. Supported: ".implode(', ', ProviderCatalog::keys())),
        };

        if ($overrides === []) {
            $this->drivers[$name] = $instance;
        } else {
            $this->drivers[$cacheKey] = $instance;
        }

        return $instance;
    }

    public function forget(?string $name = null): void
    {
        if ($name === null) {
            $this->drivers = [];

            return;
        }

        unset($this->drivers[$name]);

        foreach (array_keys($this->drivers) as $key) {
            if (str_starts_with((string) $key, $name)) {
                unset($this->drivers[$key]);
            }
        }
    }

    public function extend(string $name, AIProviderContract $provider): void
    {
        $this->drivers[$name] = $provider;
    }
}
