<?php

namespace ImranDevBd\AiHub\Tests;

use ImranDevBd\AiHub\Support\CostCalculator;
use PHPUnit\Framework\TestCase;

class PricingAndModelsTest extends TestCase
{
    public function test_calculates_gemini_38_pricing(): void
    {
        $config = require __DIR__ . '/../config/ai-hub.php';
        $calculator = CostCalculator::fromConfig($config['pricing']);

        // 1,000,000 prompt tokens + 1,000,000 completion tokens on gemini-3.8-flash (0.15 + 0.60 = 0.75)
        $cost = $calculator->calculate('gemini', 'gemini-3.8-flash', 1_000_000, 1_000_000);
        $this->assertEqualsWithDelta(0.75, $cost, 0.0001);

        // gemini-3.8-pro (1.25 + 5.00 = 6.25)
        $proCost = $calculator->calculate('gemini', 'gemini-3.8-pro', 1_000_000, 1_000_000);
        $this->assertEqualsWithDelta(6.25, $proCost, 0.0001);
    }

    public function test_calculates_claude_opus_4_pricing(): void
    {
        $config = require __DIR__ . '/../config/ai-hub.php';
        $calculator = CostCalculator::fromConfig($config['pricing']);

        // claude-opus-4-latest (15.00 + 75.00 = 90.00)
        $cost = $calculator->calculate('claude', 'claude-opus-4-latest', 1_000_000, 1_000_000);
        $this->assertEqualsWithDelta(90.00, $cost, 0.0001);
    }

    public function test_calculates_perplexity_deep_research_pricing(): void
    {
        $config = require __DIR__ . '/../config/ai-hub.php';
        $calculator = CostCalculator::fromConfig($config['pricing']);

        // sonar-deep-research (5.00 + 25.00 = 30.00)
        $cost = $calculator->calculate('perplexity', 'sonar-deep-research', 1_000_000, 1_000_000);
        $this->assertEqualsWithDelta(30.00, $cost, 0.0001);
    }
}
