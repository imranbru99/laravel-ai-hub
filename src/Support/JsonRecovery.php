<?php

namespace ImranDevBd\AiHub\Support;

class JsonRecovery
{
    public function __construct(
        protected bool $stripMarkdownFences = true,
    ) {}

    public static function make(?array $config = null): self
    {
        $config ??= config('ai-hub.json_recovery', []);

        return new self((bool) ($config['strip_markdown_fences'] ?? true));
    }

    /**
     * Attempt to decode JSON; if malformed, recover via brace-depth extraction.
     *
     * @return array{0: mixed, 1: bool} [decoded|null, recovered]
     */
    public function decode(string $content, bool $assoc = true): array
    {
        $normalized = $this->normalize($content);

        $decoded = json_decode($normalized, $assoc);
        if (json_last_error() === JSON_ERROR_NONE) {
            return [$decoded, false];
        }

        $recovered = $this->extractBalancedJson($normalized);
        if ($recovered === null) {
            return [null, false];
        }

        $decoded = json_decode($recovered, $assoc);
        if (json_last_error() === JSON_ERROR_NONE) {
            return [$decoded, true];
        }

        // Soft cleanup: trailing commas
        $cleaned = preg_replace('/,\s*([}\]])/', '$1', $recovered) ?? $recovered;
        $decoded = json_decode($cleaned, $assoc);

        return json_last_error() === JSON_ERROR_NONE
            ? [$decoded, true]
            : [null, false];
    }

    public function recoverRaw(string $content): ?string
    {
        [$decoded, $recovered] = $this->decode($content, true);

        if ($decoded === null) {
            return null;
        }

        return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function normalize(string $content): string
    {
        $content = trim($content);

        if ($this->stripMarkdownFences) {
            if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/i', $content, $m)) {
                $content = trim($m[1]);
            }
        }

        return trim($content);
    }

    /**
     * Brace/bracket depth parser — extracts first balanced JSON object or array.
     */
    public function extractBalancedJson(string $content): ?string
    {
        $startObj = strpos($content, '{');
        $startArr = strpos($content, '[');

        if ($startObj === false && $startArr === false) {
            return null;
        }

        if ($startObj === false) {
            $start = $startArr;
            $open = '[';
            $close = ']';
        } elseif ($startArr === false) {
            $start = $startObj;
            $open = '{';
            $close = '}';
        } else {
            if ($startObj < $startArr) {
                $start = $startObj;
                $open = '{';
                $close = '}';
            } else {
                $start = $startArr;
                $open = '[';
                $close = ']';
            }
        }

        $depth = 0;
        $inString = false;
        $escape = false;
        $length = strlen($content);

        for ($i = $start; $i < $length; $i++) {
            $ch = $content[$i];

            if ($inString) {
                if ($escape) {
                    $escape = false;
                    continue;
                }
                if ($ch === '\\') {
                    $escape = true;
                    continue;
                }
                if ($ch === '"') {
                    $inString = false;
                }
                continue;
            }

            if ($ch === '"') {
                $inString = true;
                continue;
            }

            if ($ch === $open) {
                $depth++;
                continue;
            }

            if ($ch === $close) {
                $depth--;
                if ($depth === 0) {
                    return substr($content, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }
}
