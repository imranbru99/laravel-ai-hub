<?php

namespace ImranDevBd\AiHub\Tests;

use ImranDevBd\AiHub\Support\JsonRecovery;
use PHPUnit\Framework\TestCase;

class JsonRecoveryTest extends TestCase
{
    public function test_decodes_clean_json(): void
    {
        $recovery = new JsonRecovery;
        [$decoded, $recovered] = $recovery->decode('{"a":1}');

        $this->assertSame(['a' => 1], $decoded);
        $this->assertFalse($recovered);
    }

    public function test_recovers_json_from_markdown_and_noise(): void
    {
        $recovery = new JsonRecovery;
        $raw = "Sure!\n```json\n{\"title\":\"Hello\",\"tags\":[\"a\"]}\n```\nThanks";
        [$decoded, $recovered] = $recovery->decode($raw);

        $this->assertIsArray($decoded);
        $this->assertSame('Hello', $decoded['title']);
    }

    public function test_extracts_balanced_object_from_prefix_junk(): void
    {
        $recovery = new JsonRecovery;
        $raw = 'Here you go: {"ok":true,"n":2} trailing text';
        $extracted = $recovery->extractBalancedJson($raw);

        $this->assertSame('{"ok":true,"n":2}', $extracted);
    }
}
