<?php

namespace ImranDevBd\AiHub\Tests;

use ImranDevBd\AiHub\Support\ModelCapabilities;
use PHPUnit\Framework\TestCase;

class ModelCapabilitiesTest extends TestCase
{
    /**
     * @dataProvider reasoningModels
     */
    public function test_openai_reasoning_omits_temperature_and_remaps_max_tokens(string $model): void
    {
        $this->assertFalse(ModelCapabilities::supportsTemperature($model), $model);
        $this->assertTrue(ModelCapabilities::usesMaxCompletionTokens($model), $model);

        $body = ModelCapabilities::sanitizeChatBody([
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => 'hi']],
            'temperature' => 0.7,
            'max_tokens' => 1024,
        ]);

        $this->assertArrayNotHasKey('temperature', $body);
        $this->assertArrayNotHasKey('max_tokens', $body);
        $this->assertSame(1024, $body['max_completion_tokens']);
    }

    public static function reasoningModels(): array
    {
        return [
            ['gpt-5.6-luna'],
            ['gpt-5.6-terra'],
            ['gpt-5.6-sol'],
            ['gpt-5.5'],
            ['gpt-5.4'],
            ['gpt-5'],
            ['gpt-5-mini'],
            ['gpt-5-pro'],
            ['openai/gpt-5'],
            ['o3'],
            ['o3-mini'],
            ['o4-mini'],
            ['o1'],
            ['o1-mini'],
            ['o1-preview'],
        ];
    }

    /**
     * @dataProvider samplingModels
     */
    public function test_chat_models_keep_temperature_and_max_tokens(string $model): void
    {
        $this->assertTrue(ModelCapabilities::supportsTemperature($model), $model);
        $this->assertFalse(ModelCapabilities::usesMaxCompletionTokens($model), $model);

        $body = ModelCapabilities::sanitizeChatBody([
            'model' => $model,
            'temperature' => 0.7,
            'max_tokens' => 256,
        ]);

        $this->assertSame(0.7, $body['temperature']);
        $this->assertSame(256, $body['max_tokens']);
        $this->assertArrayNotHasKey('max_completion_tokens', $body);
    }

    public static function samplingModels(): array
    {
        return [
            ['gpt-4o'],
            ['gpt-4o-mini'],
            ['gpt-4.1'],
            ['gpt-4.1-mini'],
            ['gpt-5-chat-latest'],
            ['chatgpt-4o-latest'],
            ['gpt-4-turbo'],
            ['gemini-3.7-flash'],
            ['claude-3-7-sonnet-latest'],
            ['grok-4.1-fast'],
            ['llama-3.3-70b-versatile'],
            ['o10'],
            ['gpt-50'],
        ];
    }

    public function test_deepseek_reasoner_drops_temperature_but_keeps_max_tokens(): void
    {
        $this->assertFalse(ModelCapabilities::supportsTemperature('deepseek-reasoner'));
        $this->assertFalse(ModelCapabilities::usesMaxCompletionTokens('deepseek-reasoner'));

        $body = ModelCapabilities::sanitizeChatBody([
            'model' => 'deepseek-reasoner',
            'temperature' => 0.7,
            'max_tokens' => 512,
        ]);

        $this->assertArrayNotHasKey('temperature', $body);
        $this->assertSame(512, $body['max_tokens']);
    }

    public function test_detects_openai_unsupported_temperature_error(): void
    {
        $message = "Unsupported value: 'temperature' does not support 0.7 with this model. Only the default (1) value is supported.";

        $this->assertTrue(ModelCapabilities::looksLikeUnsupportedSampling($message));

        $retried = ModelCapabilities::dropSampling([
            'model' => 'mystery-model',
            'temperature' => 0.7,
            'max_tokens' => 64,
        ], $message);

        $this->assertArrayNotHasKey('temperature', $retried);
        $this->assertSame(64, $retried['max_tokens']);
    }

    public function test_retries_max_tokens_as_max_completion_tokens(): void
    {
        $message = "Unsupported parameter: 'max_tokens' is not supported with this model. Use 'max_completion_tokens' instead.";

        $retried = ModelCapabilities::dropSampling([
            'model' => 'mystery-model',
            'max_tokens' => 2048,
        ], $message);

        $this->assertArrayNotHasKey('max_tokens', $retried);
        $this->assertSame(2048, $retried['max_completion_tokens']);
    }
}
