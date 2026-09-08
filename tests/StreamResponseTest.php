<?php

namespace ImranDevBd\AiHub\Tests;

use ImranDevBd\AiHub\Facades\AIHub;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamResponseTest extends TestCase
{
    public function test_stream_raw_consumes_chunks(): void
    {
        AIHub::fake('Hello from streaming test');

        $chunks = [];
        AIHub::prompt('Test stream')->streamRaw(function (string $chunk) use (&$chunks) {
            $chunks[] = $chunk;
        });

        $this->assertNotEmpty($chunks);
        $this->assertSame('Hello from streaming test', implode('', $chunks));
    }

    public function test_stream_response_returns_streamed_response(): void
    {
        AIHub::fake('Streamed SSE payload');

        $response = AIHub::prompt('SSE prompt')->streamResponse();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame('text/event-stream', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-transform', (string) $response->headers->get('Cache-Control'));

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();

        $this->assertStringContainsString('data: ', $output);
        $this->assertStringContainsString('data: [DONE]', $output);
    }
}
