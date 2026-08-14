<?php

return [
    [
        'question' => 'A user waits 8 seconds for an answer. What does streaming change?',
        'options' => [
            'It makes generation faster overall',
            'Total time is the same, but the first words appear in under a second',
            'It reduces token cost',
            'It removes the need for a queue',
        ],
        'answer' => 1,
        'explanation' => 'Streaming is a perceived-latency fix, not a throughput fix — and perceived latency is the difference between "fast" and "broken".',
    ],
    [
        'question' => 'Which error should you NOT retry?',
        'options' => [
            '429 rate limited',
            '529 overloaded',
            '400 bad request',
            'Connection timeout',
        ],
        'answer' => 2,
        'explanation' => 'A 400 means your payload is wrong; retrying gives you the same failure three times and three times the noise. Retry 429, 5xx and timeouts with backoff.',
    ],
    [
        'question' => 'You enable prompt caching but cacheReadInputTokens is always 0. Most likely cause?',
        'options' => [
            'Caching only works on Opus',
            'Something before the cache point changes every request — a timestamp, a user name, a random id',
            'The cache expires after one second',
            'You need to enable caching on the account',
        ],
        'answer' => 1,
        'explanation' => 'Caching is a prefix match: one changed byte early invalidates everything after it. Keep stable content first and volatile content last.',
    ],
    [
        'question' => 'You are classifying 500 tickets in a batch. How should the queue work?',
        'options' => [
            'One job for all 500, retried on failure',
            'One job per ticket, with backoff and a timeout',
            'A synchronous loop in the controller',
            'One job per 100 tickets',
        ],
        'answer' => 1,
        'explanation' => 'Per-item jobs mean a single failure retries one ticket. A single batch job re-bills you for 499 successful calls every time it retries.',
    ],
    [
        'question' => 'What single thing best answers "which feature is costing us the most and did quality drop?"',
        'options' => [
            'The Anthropic billing dashboard',
            'A table logging every call: feature, prompt version, model, tokens, latency, cost, stop reason',
            'Application logs with the full prompt text',
            'A daily spending alert',
        ],
        'answer' => 1,
        'explanation' => 'The provider dashboard shows a total, not a breakdown. Your own call log turns cost, latency and quality questions into SQL — and it is an afternoon of work.',
    ],
];
