<?php

return [
    'id' => 'nullable-field',
    'module' => 'structured-output',
    'title' => 'Modelling "not mentioned" without inviting invention',
    'intro' => 'A required string field pressures the model into producing something. Something is how an invented order number ends up in your database.',
    'language' => 'php',
    'code' => <<<'PHP'
    // ❌ Required string — the model must fill it in
    'order_reference' => ['type' => 'string'],

    // ✅ Nullable, with a description that forbids guessing
    'order_reference' => [
        'type' => ['string', 'null'],
        'description' => 'The order reference exactly as written by the customer, '
            .'or null if none is mentioned. Never invent one.',
    ],

    // The ticket says nothing about an order number:
    $ticket = "My delivery still hasn't turned up and it's been two weeks.";
    PHP,
    'output_language' => 'json',
    'output' => <<<'JSON'
    // ❌ with the required string field
    {
        "category": "shipping",
        "order_reference": "ORD-0000"      // ← invented. Now it is in your database.
    }

    // ✅ with the nullable field
    {
        "category": "shipping",
        "order_reference": null
    }
    JSON,
    'notes' => [
        'The <code>description</code> is load-bearing here. "or null if none is mentioned. Never invent one" is what changes the behaviour.',
        'Even with null handled, verify anything the model <em>does</em> claim: <code>Order::where(\'reference\', $data[\'order_reference\'])->exists()</code> before you act on it.',
        'A confident, well-formed, wrong value is worse than a missing one, because nothing downstream flags it.',
    ],
    'live' => 'extract',
];
