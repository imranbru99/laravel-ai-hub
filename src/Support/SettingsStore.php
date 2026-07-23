<?php

namespace ImranDevBd\AiHub\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class SettingsStore
{
    public const CACHE_KEY = 'ai-hub.settings';

    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 60, function () {
            if ($this->usesDatabase() && Schema::hasTable('ai_hub_settings')) {
                $row = \ImranDevBd\AiHub\Models\AiHubSetting::query()->first();

                return is_array($row?->payload) ? array_replace_recursive($this->defaults(), $row->payload) : $this->defaults();
            }

            $path = $this->filePath();
            if (File::exists($path)) {
                $json = json_decode(File::get($path), true);

                return is_array($json) ? array_replace_recursive($this->defaults(), $json) : $this->defaults();
            }

            return $this->defaults();
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return data_get($this->all(), $key, $default);
    }

    public function save(array $payload): array
    {
        $merged = array_replace_recursive($this->all(), $payload);

        // Never store empty api keys over existing ones unless explicitly cleared
        foreach (ProviderCatalog::keys() as $provider) {
            $newKey = data_get($payload, "providers.{$provider}.api_key");
            if ($newKey === null || $newKey === '' || $newKey === '********') {
                data_set($merged, "providers.{$provider}.api_key", data_get($this->all(), "providers.{$provider}.api_key"));
            }
        }

        if ($this->usesDatabase() && Schema::hasTable('ai_hub_settings')) {
            \ImranDevBd\AiHub\Models\AiHubSetting::query()->updateOrCreate(
                ['id' => 1],
                ['payload' => $merged]
            );
        } else {
            File::ensureDirectoryExists(dirname($this->filePath()));
            File::put($this->filePath(), json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        Cache::forget(self::CACHE_KEY);

        return $merged;
    }

    public function setProvider(string $provider, ?string $apiKey = null, ?string $model = null, bool $makeDefault = false): array
    {
        $payload = [];

        if ($apiKey !== null && $apiKey !== '') {
            data_set($payload, "providers.{$provider}.api_key", $apiKey);
        }

        if ($model !== null && $model !== '') {
            data_set($payload, "providers.{$provider}.model", $model);
            data_set($payload, "defaults.{$provider}", $model);
        }

        if ($makeDefault) {
            $payload['default'] = $provider;
        }

        return $this->save($payload);
    }

    public function applyToConfig(): void
    {
        $settings = $this->all();

        if (! empty($settings['default'])) {
            config(['ai-hub.default' => $settings['default']]);
        }

        if (! empty($settings['priority']) && is_array($settings['priority'])) {
            config(['ai-hub.priority' => array_values($settings['priority'])]);
        }

        config(['ai-hub.failover_enabled' => (bool) ($settings['failover_enabled'] ?? true)]);

        foreach ($settings['defaults'] ?? [] as $provider => $model) {
            if ($model) {
                config(["ai-hub.defaults.{$provider}" => $model]);
            }
        }

        foreach ($settings['providers'] ?? [] as $provider => $data) {
            if (! empty($data['api_key'])) {
                config(["ai-hub.providers.{$provider}.api_key" => $data['api_key']]);
            }
            if (! empty($data['base_url'])) {
                config(["ai-hub.providers.{$provider}.base_url" => $data['base_url']]);
            }
            if (! empty($data['model'])) {
                config(["ai-hub.defaults.{$provider}" => $data['model']]);
            }
            if (array_key_exists('enabled', $data)) {
                config(["ai-hub.providers.{$provider}.enabled" => (bool) $data['enabled']]);
            }
        }
    }

    public function priority(): array
    {
        $priority = $this->get('priority', config('ai-hub.priority', ProviderCatalog::keys()));

        return array_values(array_unique(array_filter((array) $priority)));
    }

    public function popularModels(): array
    {
        return config('ai-hub.popular_models', []);
    }

    protected function defaults(): array
    {
        $providers = [];
        foreach (ProviderCatalog::keys() as $provider) {
            $providers[$provider] = [
                'api_key' => null,
                'model' => config("ai-hub.defaults.{$provider}"),
                'enabled' => true,
            ];
        }

        return [
            'default' => config('ai-hub.default', 'openai'),
            'failover_enabled' => true,
            'priority' => config('ai-hub.priority', ProviderCatalog::keys()),
            'defaults' => config('ai-hub.defaults', []),
            'providers' => $providers,
        ];
    }

    public function masked(): array
    {
        $all = $this->all();

        foreach ($all['providers'] ?? [] as $provider => $data) {
            $key = (string) ($data['api_key'] ?? '');
            $all['providers'][$provider]['api_key'] = $key === ''
                ? ''
                : '********'.substr($key, -4);
            $all['providers'][$provider]['has_key'] = $key !== '';
            $all['providers'][$provider]['enabled'] = (bool) ($data['enabled'] ?? true);
            if (empty($all['providers'][$provider]['model'])) {
                $all['providers'][$provider]['model'] = config("ai-hub.defaults.{$provider}");
            }
        }

        if (empty($all['priority']) || ! is_array($all['priority'])) {
            $all['priority'] = ProviderCatalog::keys();
        }

        // Ensure new providers appear in priority for upgraded installs
        foreach (ProviderCatalog::keys() as $key) {
            if (! in_array($key, $all['priority'], true)) {
                $all['priority'][] = $key;
            }
        }

        $all['failover_enabled'] = (bool) ($all['failover_enabled'] ?? true);

        return $all;
    }

    protected function filePath(): string
    {
        return storage_path('app/ai-hub/settings.json');
    }

    protected function usesDatabase(): bool
    {
        return (bool) config('ai-hub.settings.database', true);
    }
}
