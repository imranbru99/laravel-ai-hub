<?php

namespace ImranDevBd\AiHub\Tests;

use ImranDevBd\AiHub\Facades\AIHub;

class ConversationTest extends TestCase
{
    public function test_multi_turn_conversation_maintains_history(): void
    {
        AIHub::fake([
            'Nice to meet you, Alice!',
            'Your name is Alice.',
        ]);

        $chat = AIHub::chat('openai', 'gpt-5.6-luna')
            ->system('You are a helpful companion.');

        $reply1 = $chat->reply('Hi, my name is Alice');
        $this->assertSame('Nice to meet you, Alice!', $reply1->content);

        $reply2 = $chat->reply('What is my name?');
        $this->assertSame('Your name is Alice.', $reply2->content);

        $messages = $chat->messages();

        $this->assertCount(4, $messages);
        $this->assertSame('user', $messages[0]['role']);
        $this->assertSame('Hi, my name is Alice', $messages[0]['content']);
        $this->assertSame('assistant', $messages[1]['role']);
        $this->assertSame('Nice to meet you, Alice!', $messages[1]['content']);
        $this->assertSame('user', $messages[2]['role']);
        $this->assertSame('What is my name?', $messages[2]['content']);
        $this->assertSame('assistant', $messages[3]['role']);
        $this->assertSame('Your name is Alice.', $messages[3]['content']);

        $this->assertGreaterThan(0, $chat->totalTokens());
    }

    public function test_export_and_hydrate_conversation(): void
    {
        AIHub::fake(['Answer one']);

        $chat = AIHub::chat('gemini', 'gemini-3.8-flash')->system('You are helpful.');
        $chat->reply('Question 1');

        $exported = $chat->export();

        $restored = AIHub::chat()->load($exported);

        $this->assertSame('gemini', $restored->export()['provider']);
        $this->assertSame('gemini-3.8-flash', $restored->export()['model']);
        $this->assertSame('You are helpful.', $restored->export()['system']);
        $this->assertCount(2, $restored->messages());
    }

    public function test_clear_conversation(): void
    {
        AIHub::fake(['A reply']);

        $chat = AIHub::chat();
        $chat->reply('Hello');
        $this->assertNotEmpty($chat->messages());

        $chat->clear();
        $this->assertEmpty($chat->messages());
        $this->assertSame(0, $chat->totalTokens());
    }
}
