<?php

namespace ImranDevBd\AiHub\Tests;

use ImranDevBd\AiHub\AiHubServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            AiHubServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('ai-hub.default', 'openai');
        $app['config']->set('ai-hub.failover_enabled', false);
        $app['config']->set('ai-hub.logging.enabled', false);

        $providers = [
            'openai', 'gemini', 'claude', 'azure', 'grok', 'deepseek',
            'mistral', 'groq', 'ollama', 'openrouter', 'together', 'fireworks', 'perplexity',
        ];

        foreach ($providers as $provider) {
            $app['config']->set("ai-hub.providers.{$provider}.api_key", "test-{$provider}-key");
        }
    }
}
