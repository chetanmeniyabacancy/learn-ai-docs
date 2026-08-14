<?php

return [
    'id' => 'classify-email',
    'module' => 'llm-basics',
    'title' => 'The 5,000 emails job',
    'intro' => 'The classic first AI feature: replace a person reading and tagging a queue. Cheap model, tight prompt, no chat UI anywhere.',
    'language' => 'php',
    'code' => <<<'PHP'
    $system = <<<'TXT'
    You classify inbound emails for a distributor.

    Reply with exactly three lines and nothing else:
    Category: one of [order_status, invoice, complaint, returns, sales, spam]
    Priority: one of [low, normal, high, urgent]
    Summary: one sentence, under 20 words

    Use only what the email says. If it is unclear, use category "sales"
    and priority "normal".
    TXT;

    $email = <<<'TXT'
    Subject: STILL waiting

    This is the third time I'm emailing about invoice 88213. It was due
    two weeks ago and nobody has replied. If I don't hear back today I'm
    taking this to our account manager.
    TXT;

    $message = $client->messages->create(
        model: 'claude-haiku-4-5',       // cheapest model — plenty for this
        maxTokens: 100,
        system: $system,
        messages: [['role' => 'user', 'content' => $email]],
    );

    echo Claude::text($message);
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    Category: invoice
    Priority: high
    Summary: Customer chasing unanswered invoice 88213, overdue two weeks, threatening escalation.

    ─────────────────────────────────────────────
    input tokens:  178      output tokens: 32
    cost this email:        $0.00034
    cost for 5,000/month:   $1.70
    TEXT,
    'notes' => [
        'Haiku at $1/$5 per million tokens does this for under two dollars a month. That arithmetic — not the technology — is what gets the feature approved.',
        'Notice the closed lists. "one of [...]" is doing the same job an enum does in module 3, just less reliably.',
        'It still returns text you have to parse. Module 3 replaces those three lines with guaranteed JSON.',
    ],
    'live' => 'chat',
];
