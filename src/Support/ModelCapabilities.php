<?php

namespace ImranDevBd\AiHub\Support;

/**
 * Sampling / token-limit quirks that differ across model families.
 *
 * OpenAI reasoning models (o1/o3/o4, GPT-5.x except *-chat) reject non-default
 * temperature and require max_completion_tokens instead of max_tokens.
 */
class ModelCapabilities
{
    public static function bareId(string $model): string
    {
        $model = strtolower(trim($model));
        if ($model === '') {
            return '';
        }

        $parts = preg_split('#[/\\\\]#', $model) ?: [$model];

        return (string) end($parts);
    }

    /**
     * Chat-tuned GPT-5 variants still accept temperature / max_tokens.
     */
    public static function isChatVariant(string $model): bool
    {
        return (bool) preg_match('/^gpt-5(?:\.\d+)?-chat/', self::bareId($model));
    }

    /**
     * OpenAI / Azure / OpenRouter GPT-5 & o-series reasoning models.
     */
    public static function isOpenAiReasoning(string $model): bool
    {
        $id = self::bareId($model);
        if ($id === '' || self::isChatVariant($id)) {
            return false;
        }

        // o1, o3-mini, o4-mini — do not match o10 / o30
        if (preg_match('/^o[1-4](?:-|$)/', $id)) {
            return true;
        }

        // gpt-5, gpt-5-mini, gpt-5.6-luna, gpt-5.4, gpt-5-pro
        if (preg_match('/^gpt-5(?:$|[.-])/', $id)) {
            return true;
        }

        return (bool) preg_match('/^codex-/', $id);
    }

    public static function isDeepSeekReasoning(string $model): bool
    {
        return (bool) preg_match('/^(deepseek-reasoner|deepseek-r1)/', self::bareId($model));
    }

    public static function isReasoning(string $model): bool
    {
        return self::isOpenAiReasoning($model) || self::isDeepSeekReasoning($model);
    }

    public static function supportsTemperature(string $model): bool
    {
        return ! self::isOpenAiReasoning($model) && ! self::isDeepSeekReasoning($model);
    }

    /**
     * OpenAI reasoning chat-completions require max_completion_tokens.
     * DeepSeek / Groq / others still use max_tokens.
     */
    public static function usesMaxCompletionTokens(string $model): bool
    {
        return self::isOpenAiReasoning($model);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function stripUnsupported(array $payload): array
    {
        $model = (string) ($payload['model'] ?? '');
        if ($model === '' || self::supportsTemperature($model)) {
            return $payload;
        }

        unset($payload['temperature'], $payload['top_p'], $payload['presence_penalty'], $payload['frequency_penalty']);

        return $payload;
    }

    /**
     * Shape an OpenAI-compatible chat body for the target model.
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public static function sanitizeChatBody(array $body): array
    {
        $body = self::stripUnsupported($body);
        $model = (string) ($body['model'] ?? '');

        if ($model !== '' && self::usesMaxCompletionTokens($model) && array_key_exists('max_tokens', $body)) {
            if (! array_key_exists('max_completion_tokens', $body)) {
                $body['max_completion_tokens'] = $body['max_tokens'];
            }
            unset($body['max_tokens']);
        }

        return $body;
    }

    /**
     * Last-resort rewrite after a 400 about unsupported sampling fields.
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public static function dropSampling(array $body, ?string $error = null): array
    {
        $msg = strtolower((string) $error);
        $unspecified = $error === null || $error === '';

        $dropTemp = $unspecified
            || str_contains($msg, 'temperature')
            || str_contains($msg, 'top_p')
            || str_contains($msg, 'presence_penalty')
            || str_contains($msg, 'frequency_penalty');

        $remapMax = $unspecified || str_contains($msg, 'max_tokens');

        if ($dropTemp) {
            unset($body['temperature'], $body['top_p'], $body['presence_penalty'], $body['frequency_penalty']);
        }

        if ($remapMax && array_key_exists('max_tokens', $body)) {
            if (! array_key_exists('max_completion_tokens', $body)) {
                $body['max_completion_tokens'] = $body['max_tokens'];
            }
            unset($body['max_tokens']);
        }

        return $body;
    }

    public static function looksLikeUnsupportedSampling(string $message): bool
    {
        $msg = strtolower($message);
        if ($msg === '') {
            return false;
        }

        $unsupported = str_contains($msg, 'unsupported')
            || str_contains($msg, 'does not support')
            || str_contains($msg, 'not supported');

        if (! $unsupported) {
            return false;
        }

        foreach (['temperature', 'top_p', 'max_tokens', 'presence_penalty', 'frequency_penalty'] as $param) {
            if (str_contains($msg, $param)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{temperature: bool, max_completion_tokens: bool, reasoning: bool}
     */
    public static function describe(string $model): array
    {
        return [
            'temperature' => self::supportsTemperature($model),
            'max_completion_tokens' => self::usesMaxCompletionTokens($model),
            'reasoning' => self::isReasoning($model),
        ];
    }
}
