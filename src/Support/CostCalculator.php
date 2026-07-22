<?php

namespace ImranDevBd\AiHub\Support;

class CostCalculator
{
    public function __construct(
        protected array $pricing = [],
    ) {}

    public static function fromConfig(?array $pricing = null): self
    {
        return new self($pricing ?? config('ai-hub.pricing', []));
    }

    public function calculate(string $provider, string $model, int $promptTokens, int $completionTokens = 0): float
    {
        $rates = $this->ratesFor($provider, $model);

        if ($rates === null) {
            return 0.0;
        }

        $input = ((float) ($rates['input'] ?? 0)) * ($promptTokens / 1_000_000);
        $output = ((float) ($rates['output'] ?? 0)) * ($completionTokens / 1_000_000);

        return round($input + $output, 8);
    }

    public function ratesFor(string $provider, string $model): ?array
    {
        $providerRates = $this->pricing[$provider] ?? [];

        if (isset($providerRates[$model])) {
            return $providerRates[$model];
        }

        // fuzzy: match by prefix (e.g. gpt-4o-mini-2024-07-18 → gpt-4o-mini)
        foreach ($providerRates as $key => $rates) {
            if (str_starts_with($model, (string) $key) || str_starts_with((string) $key, $model)) {
                return $rates;
            }
        }

        return null;
    }
}
