<?php

return [
    'id' => 'tool-definition',
    'module' => 'tool-calling',
    'title' => 'Defining a tool — the description is the interface',
    'intro' => 'The description is the only thing the model knows about your function. A vague one produces a tool called at the wrong times with the wrong arguments, and no system prompt will fix it.',
    'language' => 'php',
    'code' => <<<'PHP'
    // ❌ Vague — you will spend a week wondering why it never gets called
    [
        'name' => 'get_order',
        'description' => 'Gets order info.',
        'inputSchema' => ['type' => 'object', 'properties' => ['id' => ['type' => 'string']]],
    ]

    // ✅ Says what it does, what it returns, and WHEN to call it
    [
        'name' => 'lookup_my_order',
        'description' => 'Look up one order belonging to the current customer, by its '
            .'reference (e.g. ORD-1043). Returns status, totals and delivery dates. '
            .'Use this whenever the customer names an order reference. Returns '
            .'found:false if no such order belongs to them — do not retry with a guess.',
        'inputSchema' => [
            'type' => 'object',
            'properties' => [
                'reference' => [
                    'type' => 'string',
                    'description' => 'The order reference, like ORD-1043.',
                ],
            ],
            'required' => ['reference'],
        ],
    ]
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    Question: "any idea where ORD-1043 has got to?"

    with the vague description
      → no tool call
      → "I'd suggest checking your account page for order tracking."

    with the good description
      → tool_use: lookup_my_order(reference: "ORD-1043")
      → "Your order shipped on 2 August and DHL expects it on 14 August."
    TEXT,
    'notes' => [
        'Note the tool is <code>lookup_my_order</code>, not <code>lookup_order(customer_id)</code>. The customer comes from <code>auth()</code> in your code — module 9 explains why that naming decision is a security control.',
        'Stating the failure mode ("returns found:false … do not retry with a guess") stops the model looping on a bad reference.',
        'Few tools, clearly separated. If two descriptions could both answer the same question, the model will pick wrong roughly half the time.',
    ],
    'live' => 'tools',
];
