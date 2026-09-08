<?php

namespace ImranDevBd\AiHub\Tests;

use ImranDevBd\AiHub\Facades\AIHub;
use ImranDevBd\AiHub\PendingRequest;

class AIHubFakeTest extends TestCase
{
    public function test_can_fake_simple_response(): void
    {
        $fake = AIHub::fake('Hello world from fake');

        $response = AIHub::prompt('Hi there')->generate();

        $this->assertSame('Hello world from fake', $response->content);
        $this->assertTrue($response->success);

        $fake->assertSent();
        $fake->assertSentCount(1);
        $fake->assertSent(fn (PendingRequest $req) => $req->getPrompt() === 'Hi there');
    }

    public function test_can_fake_queued_responses(): void
    {
        $fake = AIHub::fake([
            'Response one',
            'Response two',
        ]);

        $r1 = AIHub::prompt('First')->generate();
        $r2 = AIHub::prompt('Second')->generate();

        $this->assertSame('Response one', $r1->content);
        $this->assertSame('Response two', $r2->content);
        $fake->assertSentCount(2);
    }

    public function test_can_fake_model_specific_responses(): void
    {
        $fake = AIHub::fake([
            'gemini-3.8-flash' => 'Hello from Gemini',
            'gpt-5.6-luna' => 'Hello from Luna',
        ]);

        $gemini = AIHub::gemini('gemini-3.8-flash')->prompt('Test')->generate();
        $openai = AIHub::openai('gpt-5.6-luna')->prompt('Test')->generate();

        $this->assertSame('Hello from Gemini', $gemini->content);
        $this->assertSame('Hello from Luna', $openai->content);
    }

    public function test_assert_nothing_sent(): void
    {
        $fake = AIHub::fake();
        $fake->assertNothingSent();
    }

    public function test_assert_not_sent(): void
    {
        $fake = AIHub::fake('Only one reply');

        AIHub::openai()->prompt('Say hello')->generate();

        $fake->assertSent('openai');
        $fake->assertNotSent('claude');
    }

    public function test_fake_response_helper(): void
    {
        $custom = AIHub::response(['status' => 'ok', 'code' => 200], 'openai', 'gpt-5.6-luna');

        AIHub::fake([$custom]);

        $res = AIHub::prompt('Status?')->generate();

        $this->assertSame(200, $res->json()['code']);
        $this->assertSame('openai', $res->provider);
    }
}
