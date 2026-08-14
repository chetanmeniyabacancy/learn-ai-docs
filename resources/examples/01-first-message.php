<?php

return [
    'id' => 'first-message',
    'module' => 'orientation',
    'title' => 'Your first API call',
    'intro' => 'Send one message, read the answer back. This is the whole interface — everything else in the course is about controlling what goes in and what comes out.',
    'language' => 'php',
    'code' => <<<'PHP'
    use Anthropic\Client;

    $client = new Client(apiKey: config('claude.api_key'));

    $message = $client->messages->create(
        model: 'claude-sonnet-5',
        maxTokens: 300,
        messages: [
            ['role' => 'user', 'content' => 'In one sentence: what is Laravel?'],
        ],
    );

    // content is an ARRAY OF BLOCKS, not a string.
    foreach ($message->content as $block) {
        if ($block->type === 'text') {
            echo $block->text;
        }
    }

    echo "\n---\n";
    echo "stop reason:   {$message->stopReason}\n";
    echo "input tokens:  {$message->usage->inputTokens}\n";
    echo "output tokens: {$message->usage->outputTokens}\n";
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    Laravel is a PHP web framework that provides an expressive syntax and a large
    set of built-in tools — routing, ORM, queues, authentication — for building
    modern web applications.
    ---
    stop reason:   end_turn
    input tokens:  16
    output tokens: 38
    TEXT,
    'notes' => [
        '<code>content</code> is a list of typed blocks. Reaching for <code>content[0]->text</code> works until you enable thinking or tools, then it returns the wrong thing or explodes. Loop and filter on <code>type</code>.',
        '<code>stopReason: end_turn</code> means it finished naturally. Check this before you read the text — <code>refusal</code> and <code>max_tokens</code> both look like short answers.',
        '54 tokens total. At Sonnet rates that is about $0.0006 — roughly a twentieth of a cent.',
    ],
    'live' => 'chat',
];
