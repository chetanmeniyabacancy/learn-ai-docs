<?php

return [
    'id' => 'cache-keys',
    'module' => 'architecture-scaling',
    'title' => 'Three caches, and the two fields that must be in the key',
    'intro' => 'Caching is the difference between an AI feature that scales and one that becomes the biggest line on the invoice. Two one-line mistakes in a cache key cause a cross-customer leak and a fix that appears not to work.',
    'language' => 'php',
    'code' => <<<'PHP'
    // 1. EXACT MATCH — trivial, and it works better than people expect.
    $key = 'ai:'.sha1(
        $this->normalise($question)      // lowercase, collapse whitespace
        .'|'.$user->tenant_id            // ← without this you leak across customers
        .'|'.AssistantPrompt::VERSION    // ← without this your fix does nothing for 6 hours
    );

    return Cache::remember($key, now()->addHours(6), fn () => $this->answer($question, $user));

    // 2. SEMANTIC — "what's the return window" and "how long to send something
    //    back" deserve the same answer. Keep the bar HIGH: this is the one
    //    optimisation that can serve a confidently wrong answer.
    $similar = QuestionCache::where('tenant_id', $user->tenant_id)
        ->get()
        ->map(fn ($row) => [$row, $this->cosine($vector, $row->embedding)])
        ->filter(fn ($pair) => $pair[1] > 0.95)
        ->sortByDesc(fn ($pair) => $pair[1])
        ->first();

    // 3. PREFIX — provider-side, ~10% the price for cached input. Stable
    //    content first, volatile last: one changed byte before the cache point
    //    invalidates everything after it.
    $message = $client->messages->create(
        model: config('claude.model'),
        maxTokens: 1024,
        system: [[
            'type' => 'text',
            'text' => $this->handbook(),        // stable — cache this
            'cacheControl' => ['type' => 'ephemeral'],
        ]],
        messages: [['role' => 'user', 'content' => $question]],   // volatile — after
    );

    Log::info('cache', ['read' => $message->usage->cacheReadInputTokens]);  // want this high
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    one week, 41,000 messages

    cache            hit rate   saving        risk if wrong
    exact match         18%     full call     none — same question, same answer
    semantic             9%     full call     HIGH — a near-miss answers a
                                              different question entirely
    prefix (provider)   —       ~90% of       none
                                input tokens

    blended cost/message   $0.0031   (was $0.0110)

    the two key bugs, seen in the wild
      no tenant in key      customer B served customer A's answer
      no prompt version     prompt fixed at 09:00, users saw the old answer
                            until 15:00 and the fix looked broken
    TEXT,
    'notes' => [
        'If <code>cacheReadInputTokens</code> stays at zero on the second identical call, something before your cache point is changing — a timestamp, a name, an unsorted array.',
        'A semantic cache threshold of 0.95 is deliberately strict. Exact and prefix caching can only serve something correct; a loose semantic cache answers a question nobody asked.',
        'Cache keys are also where multi-tenancy leaks first. Retrieval, memory, traces and caches all need the tenant in them.',
    ],
    'live' => null,
];
