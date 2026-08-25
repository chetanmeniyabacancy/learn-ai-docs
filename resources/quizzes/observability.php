<?php

return [
    [
        'question' => 'What is the difference between a trace and a span?',
        'options' => [
            'A trace is an error, a span is a success',
            'A trace is one request end to end; a span is one unit of work inside it, and spans nest',
            'A trace is stored, a span is only logged',
            'They are the same thing with different names',
        ],
        'answer' => 1,
        'explanation' => 'The nesting is what makes it useful: retrieval inside a request, an embed call inside retrieval. That structure answers where the time went and where the money went, which a flat log cannot.',
    ],
    [
        'question' => 'Which field, missing from your logs, makes "why did quality drop?" unanswerable?',
        'options' => [
            'The response length',
            'The prompt version',
            'The HTTP status code',
            'The user agent',
        ],
        'answer' => 1,
        'explanation' => 'Prompts are versioned classes for exactly this reason. With the version on every call, "what changed?" is a GROUP BY. Without it, it is somebody\'s memory of last Tuesday.',
    ],
    [
        'question' => 'When should span inputs and outputs be redacted?',
        'options' => [
            'When they are displayed in the dashboard',
            'On the way in, before they are written — what is not stored cannot leak',
            'Only for customer-facing features',
            'Only if the customer asks',
        ],
        'answer' => 1,
        'explanation' => 'Traces contain customer data by definition, so they inherit your retention obligations the moment they exist. Redact before writing, truncate long payloads, and give traces a TTL.',
    ],
    [
        'question' => 'Most teams alert only on errors. Which failure does that miss?',
        'options' => [
            'A 500 from the provider',
            'A loop that works perfectly and costs $900 overnight, or a prompt change that quietly lowers quality',
            'A timeout',
            'An invalid API key',
        ],
        'answer' => 1,
        'explanation' => 'The expensive failures do not throw. Alert on daily spend, silent truncation (stop_reason max_tokens), refusal rate and judge pass rate — not only on exceptions.',
    ],
    [
        'question' => 'What is the practical test of whether your observability is good enough?',
        'options' => [
            'Every request produces a log line',
            'You can go from a customer complaint to that conversation\'s full trace in about a minute, without writing SQL',
            'You store logs for a year',
            'You use a paid tracing vendor',
        ],
        'answer' => 1,
        'explanation' => 'That single path — reference to trace, quickly, without an engineer — is what makes support able to answer questions and makes drift visible. If it takes SQL, it will not happen.',
    ],
];
