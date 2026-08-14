<?php

return [
    'id' => 'streaming',
    'module' => 'production',
    'title' => 'Streaming: same total time, very different product',
    'intro' => 'A 500-word answer takes several seconds and you cannot make that number small. You can make the first word arrive in under one.',
    'language' => 'php',
    'code' => <<<'PHP'
    Route::get('/assistant/stream', function (Request $request) {
        return response()->stream(function () use ($request) {
            $stream = app(Client::class)->messages->createStream(
                model: 'claude-sonnet-5',
                maxTokens: 2048,
                system: AssistantPrompt::system(),
                messages: $request->session()->get('conversation', []),
            );

            foreach ($stream as $event) {
                if ($event->type === 'content_block_delta'
                    && ($event->delta->type ?? null) === 'text_delta') {
                    echo 'data: '.json_encode(['text' => $event->delta->text])."\n\n";
                    ob_flush();
                    flush();
                }
            }

            echo "data: [DONE]\n\n";
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',   // ← nginx will buffer the lot without this
        ]);
    });
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    ── without streaming ────────────────────────────────────
    0.0s  [ spinner ]
    3.8s  [ spinner ]
    7.4s  entire answer appears at once

    ── with streaming ───────────────────────────────────────
    0.7s  "Your"
    0.8s  "Your order"
    0.9s  "Your order ORD-1043"
    …
    7.4s  …complete

    Total time: identical. Perceived: completely different.
    TEXT,
    'notes' => [
        '<code>X-Accel-Buffering: no</code> is the header everyone loses an afternoon to. Without it nginx buffers the whole stream and delivers it in one lump — which looks exactly like streaming not working.',
        'Streaming is for when a user is waiting. If nobody is waiting — batch classification, indexing, overnight summaries — use a queue instead.',
        'One job per item on the queue, never one job for the batch. A failure then retries one record instead of re-billing you for four hundred.',
    ],
    'live' => null,
];
