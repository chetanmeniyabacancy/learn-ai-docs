<?php

return [
    'id' => 'discriminative-vs-generative',
    'module' => 'what-is-ai',
    'title' => 'Discriminative vs generative, on the same input',
    'intro' => 'Two models, one email. One draws a boundary; the other continues text. This is why they fail in completely different ways.',
    'language' => 'php',
    'code' => <<<'PHP'
    $email = "I was charged twice for order ORD-1043 and nobody has replied.";

    // ── Discriminative: which side of the line? ───────────
    $classifier->predict($email);
    // Learns: given these features, which category?
    // Cannot produce anything that is not one of its known labels.

    // ── Generative: what comes next? ──────────────────────
    $claude->ask('Draft a reply to this customer.', $email);
    // Learns: what tends to follow this text?
    // Produces something that did not exist. Including, sometimes,
    // facts that do not exist.
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    ── discriminative model ─────────────────────────────────
    { "category": "billing", "confidence": 0.91 }

    Failure mode: it can be wrong, but only ever among its five
    known labels. It cannot invent a sixth category, and it cannot
    invent a refund policy.

    ── generative model ─────────────────────────────────────
    "Hi, I'm sorry about the duplicate charge on ORD-1043. I've
     refunded it and you'll see it back on your card within 3-5
     working days. Our policy allows duplicate refunds up to £500
     without review."

    Failure mode: fluent, helpful, and it just invented a refund
    timeline AND a policy limit. Neither was in the input. Neither
    was checked. Nothing in the mechanism could have checked them.
    TEXT,
    'notes' => [
        'The generative failure is not a bug being fixed in the next release. Continuing text plausibly is the objective it was trained on — module F6 shows the mechanism.',
        'This is why Level 1 spends so much effort on structure: give it the real policy (RAG), give it real data (tools), and constrain the output shape (schemas).',
        'Almost all commercially deployed machine learning before 2020 was the first kind, and most of it still is.',
    ],
    'live' => 'extract',
];
