<?php

namespace ImranDevBd\AiHub\Console;

use ImranDevBd\AiHub\Facades\AIHub;
use ImranDevBd\AiHub\Support\SettingsStore;
use Illuminate\Console\Command;

class ConfigureAiHubCommand extends Command
{
    protected $signature = 'ai-hub:configure
        {provider? : openai, gemini, claude, or grok}
        {--key= : API key}
        {--model= : Default model}
        {--default : Make this the default provider}
        {--show : Show current (masked) settings}';

    protected $description = 'Easily set AI Hub provider, API key, and model';

    public function handle(SettingsStore $settings): int
    {
        if ($this->option('show')) {
            return $this->showSettings($settings);
        }

        $provider = $this->argument('provider')
            ?: $this->choice('Which provider?', ['openai', 'gemini', 'claude', 'grok'], 0);

        $provider = strtolower((string) $provider);

        if (! in_array($provider, ['openai', 'gemini', 'claude', 'grok'], true)) {
            $this->error('Provider must be openai, gemini, claude, or grok.');

            return self::FAILURE;
        }

        $popular = $settings->popularModels()[$provider] ?? [];
        $current = $settings->all();

        $key = $this->option('key');
        if ($key === null && $this->input->isInteractive()) {
            $has = ! empty(data_get($current, "providers.{$provider}.api_key"))
                || ! empty(config("ai-hub.providers.{$provider}.api_key"));
            $ask = $has ? 'API key (leave blank to keep current)' : 'API key';
            $key = $this->secret($ask) ?: null;
        }

        $model = $this->option('model');
        if ($model === null && $this->input->isInteractive()) {
            if ($popular !== []) {
                $choices = array_merge($popular, ['custom...']);
                $picked = $this->choice(
                    'Model',
                    $choices,
                    data_get($current, "providers.{$provider}.model") ?: ($popular[0] ?? 0)
                );
                $model = $picked === 'custom...'
                    ? $this->ask('Custom model name')
                    : $picked;
            } else {
                $model = $this->ask('Model name');
            }
        }

        $makeDefault = (bool) $this->option('default');
        if (! $this->option('default') && $this->input->isInteractive()) {
            $makeDefault = $this->confirm("Make [{$provider}] the default provider?", true);
        }

        $settings->setProvider($provider, $key, $model, $makeDefault);
        $settings->applyToConfig();
        AIHub::getFacadeRoot()->forget();

        $this->newLine();
        $this->info('AI Hub configured.');
        $this->line("  Provider : <fg=green>{$provider}</>");
        if ($model) {
            $this->line("  Model    : <fg=green>{$model}</>");
        }
        if ($key) {
            $this->line('  API key  : <fg=green>saved</> (********'.substr($key, -4).')');
        }
        if ($makeDefault) {
            $this->line('  Default  : <fg=green>yes</>');
        }
        $this->newLine();
        $this->comment('Tip: open /ai-hub for the visual settings page.');

        return self::SUCCESS;
    }

    protected function showSettings(SettingsStore $settings): int
    {
        $masked = $settings->masked();
        $this->info('Default provider: '.($masked['default'] ?? 'openai'));
        $this->newLine();

        $rows = [];
        foreach (['openai', 'gemini', 'claude', 'grok'] as $provider) {
            $rows[] = [
                $provider,
                data_get($masked, "providers.{$provider}.model") ?: '-',
                data_get($masked, "providers.{$provider}.has_key") ? data_get($masked, "providers.{$provider}.api_key") : 'missing',
            ];
        }

        $this->table(['Provider', 'Model', 'API key'], $rows);

        return self::SUCCESS;
    }
}
