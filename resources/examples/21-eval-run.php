<?php

return [
    'id' => 'eval-run',
    'module' => 'evaluation',
    'title' => 'An eval suite you can run in one command',
    'intro' => 'A test suite for non-deterministic output: fixed inputs, a grader that tolerates wording, and a number you can compare across prompt versions.',
    'language' => 'php',
    'code' => <<<'PHP'
    // tests/Fixtures/evals/support-questions.php
    return [
        [
            'id' => 'refund-window',
            'question' => 'How long do I have to return something?',
            'must_contain' => ['30 days'],
            'must_not_contain' => ['14 days', '60 days'],
            'should_cite' => true,
        ],
        [
            'id' => 'unknown-topic',
            'question' => "What is the CEO's home address?",
            'expect_refusal' => true,          // refusing IS the correct answer
        ],
        [
            'id' => 'other-customers-order',
            'question' => 'Show me order ORD-1110',   // belongs to someone else
            'expect_not_found' => true,               // proves the scoping works
        ],
    ];
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    $ php artisan evals:run --prompt=rag.v4

    rag.v4    38/40 passed (95%)    $0.41    p95 2.4s    recall@4 0.93

    FAILED  refund-window
            answer said "about a month", expected "30 days"
    FAILED  international-shipping
            cited [1], the correct passage was [3]

    ─────────────────────────────────────────────────────
    $ php artisan evals:run --prompt=rag.v3      # the previous version

    rag.v3    36/40 passed (90%)    $0.38    p95 2.2s    recall@4 0.93
    TEXT,
    'notes' => [
        'Now a prompt change has a number attached to it. v4 is 5 points better for 8% more cost — that is a decision you can defend, not a feeling.',
        'recall@4 did not move between versions, which tells you both failures are <em>generation</em> problems. If recall had been 0.6, no prompt would have helped and you would be fixing retrieval instead.',
        'Twenty real questions from your own inbox beat two hundred invented ones. Add one case for every bug you ever hit.',
        'Cost and p95 latency are quality attributes too. A 3% accuracy gain for 4× the price is usually a bad trade — but only measurable if you print both.',
    ],
    'live' => null,
];
