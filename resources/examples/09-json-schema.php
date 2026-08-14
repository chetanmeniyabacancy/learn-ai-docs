<?php

return [
    'id' => 'json-schema',
    'module' => 'structured-output',
    'title' => 'Guaranteed JSON with a schema',
    'intro' => 'Attach a JSON Schema and the response is constrained during generation. Your parser becomes json_decode(). No regex, no "please reply with only JSON", no retry loop.',
    'language' => 'php',
    'code' => <<<'PHP'
    $message = $client->messages->create(
        model: 'claude-sonnet-5',
        maxTokens: 1024,
        system: 'You triage inbound customer support messages.',
        messages: [['role' => 'user', 'content' => $ticket]],
        outputConfig: [
            'format' => [
                'type' => 'json_schema',
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'category' => ['type' => 'string',
                            'enum' => ['billing', 'shipping', 'technical', 'account', 'other']],
                        'priority' => ['type' => 'string',
                            'enum' => ['low', 'normal', 'high', 'urgent']],
                        'summary' => ['type' => 'string',
                            'description' => 'One sentence, under 140 characters, no greeting.'],
                        'mentions_refund' => ['type' => 'boolean'],
                    ],
                    'required' => ['category', 'priority', 'summary', 'mentions_refund'],
                    'additionalProperties' => false,
                ],
            ],
        ],
    );

    $data = json_decode(Claude::text($message), true);
    PHP,
    'output_language' => 'json',
    'output' => <<<'JSON'
    {
        "category": "billing",
        "priority": "urgent",
        "summary": "Customer charged twice for order ORD-1043 and has had no reply to two emails.",
        "mentions_refund": true
    }
    JSON,
    'notes' => [
        'The shape is guaranteed: those four keys, those types, one of those five categories. Every time.',
        '<code>additionalProperties: false</code> plus a complete <code>required</code> list means no optional fields — and therefore no null checks scattered through your code.',
        'The <code>description</code> is read by the model. Put the rule with the field rather than restating it in the system prompt.',
        'The shape is guaranteed; the <em>values</em> are not. Still validate — see the next example.',
    ],
    'live' => 'extract',
];
