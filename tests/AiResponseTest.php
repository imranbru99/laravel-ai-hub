<?php

namespace ImranDevBd\AiHub\Tests;

use ImranDevBd\AiHub\Data\AiResponse;
use PHPUnit\Framework\TestCase;

class AiResponseTest extends TestCase
{
    public function test_round_trips_tool_calls_through_array(): void
    {
        $response = new AiResponse(
            content: 'calling',
            provider: 'openai',
            model: 'gpt-4o-mini',
            toolCalls: [[
                'type' => 'function',
                'function' => ['name' => 'get_weather', 'arguments' => '{"city":"Dhaka"}'],
            ]],
        );

        $cloned = AiResponse::fromArray($response->toArray());

        $this->assertSame('calling', $cloned->content);
        $this->assertSame('get_weather', $cloned->toolCalls[0]['function']['name']);
    }
}
