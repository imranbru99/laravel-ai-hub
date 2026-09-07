<?php

namespace ImranDevBd\AiHub\Support;

class ProviderCatalog
{
    /**
     * All built-in provider keys (order = default priority suggestion).
     *
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(config('ai-hub.providers', [
            'openai' => [],
            'gemini' => [],
            'claude' => [],
            'grok' => [],
            'deepseek' => [],
            'mistral' => [],
            'groq' => [],
            'ollama' => [],
            'openrouter' => [],
            'azure' => [],
            'together' => [],
            'fireworks' => [],
            'perplexity' => [],
        ]));
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'openai' => 'OpenAI',
            'gemini' => 'Gemini',
            'claude' => 'Claude',
            'grok' => 'Grok',
            'deepseek' => 'DeepSeek',
            'mistral' => 'Mistral',
            'groq' => 'Groq',
            'ollama' => 'Ollama',
            'openrouter' => 'OpenRouter',
            'azure' => 'Azure OpenAI',
            'together' => 'Together',
            'fireworks' => 'Fireworks',
            'perplexity' => 'Perplexity',
        ];
    }

    public static function label(string $key): string
    {
        return self::labels()[$key] ?? ucfirst($key);
    }

    public static function validationRule(): string
    {
        return 'in:'.implode(',', self::keys());
    }
}
