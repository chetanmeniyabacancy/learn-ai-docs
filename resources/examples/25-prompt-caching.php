<?php

return [
    'id' => 'prompt-caching',
    'module' => 'production',
    'title' => 'Prompt caching, and the one byte that breaks it',
    'intro' => 'A long stable prefix can be cached and re-read at about a tenth the price. Caching is a prefix match, which is why the ordering of your prompt is a cost decision.',
    'language' => 'php',
    'code' => <<<'PHP'
    // ❌ A timestamp at the top invalidates the cache on EVERY request.
    //    Nothing errors. The bill just never goes down.
    $system = "Today is ".now()->toDateString()."\n\n".$longStablePrompt;

    // ✅ Stable content first, volatile content last
    $message = $client->messages->create(
        model: 'claude-sonnet-5',
        maxTokens: 1024,
        system: [
            ['type' => 'text', 'text' => $longStablePrompt,
             'cacheControl' => ['type' => 'ephemeral']],
        ],
        messages: [['role' => 'user', 'content' => "Today is {$today}.\n\n{$question}"]],
    );

    Log::info('cache', [
        'read' => $message->usage->cacheReadInputTokens,        // want this HIGH
        'written' => $message->usage->cacheCreationInputTokens,
        'fresh' => $message->usage->inputTokens,
    ]);
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    ── request 1 (cold) ─────────────────────────────────────
    cache written  8,412      fresh  46      cost  $0.0316

    ── request 2 (warm) ─────────────────────────────────────
    cache read     8,412      fresh  52      cost  $0.0027
                                                    ▲ 92% cheaper

    ── with the timestamp at the top of the system prompt ───
    request 1  cache written 8,412   fresh 46   $0.0316
    request 2  cache written 8,412   fresh 52   $0.0316
    request 3  cache written 8,412   fresh 49   $0.0316
               ▲ cacheReadInputTokens is always 0. This is the symptom.
    TEXT,
    'notes' => [
        'If <code>cacheReadInputTokens</code> is zero across repeated calls, something before the cache point is changing. A timestamp, a user name, a UUID, an unsorted <code>json_encode</code>.',
        'Cache writes cost about 1.25× normal input, reads about 0.1×. Two requests and you are ahead; below that it is not worth marking.',
        'The same rule governs tools and model choice: change either mid-conversation and the whole prefix is invalidated.',
    ],
    'live' => null,
];
