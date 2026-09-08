<?php

namespace ImranDevBd\AiHub\Tests;

use ImranDevBd\AiHub\Facades\AIHub;
use ImranDevBd\AiHub\PendingRequest;

class StructuredSchemaTest extends TestCase
{
    public function test_as_json_schema_configures_payload_and_getters(): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'sentiment' => ['type' => 'string', 'enum' => ['positive', 'negative', 'neutral']],
                'confidence' => ['type' => 'number'],
            ],
            'required' => ['sentiment', 'confidence'],
            'additionalProperties' => false,
        ];

        $req = AIHub::openai('gpt-5.6-luna')
            ->prompt('Analyze sentiment')
            ->asJsonSchema($schema, 'SentimentAnalysis', 'Extract sentiment');

        $this->assertSame($schema, $req->getResponseSchema());
        $this->assertTrue($req->isForceJsonObject());
    }

    public function test_structured_with_fake(): void
    {
        $fakeJson = json_encode(['sentiment' => 'positive', 'confidence' => 0.98]);
        AIHub::fake($fakeJson);

        $schema = [
            'type' => 'object',
            'properties' => [
                'sentiment' => ['type' => 'string'],
                'confidence' => ['type' => 'number'],
            ],
            'required' => ['sentiment', 'confidence'],
        ];

        $response = AIHub::structured($schema)
            ->prompt('I love Laravel AI Hub!')
            ->generate();

        $this->assertTrue($response->success);
        $decoded = $response->json();
        $this->assertSame('positive', $decoded['sentiment']);
        $this->assertEquals(0.98, $decoded['confidence']);
    }
}
