<?php

return [
    [
        'question' => 'What does attaching a JSON Schema via outputConfig actually guarantee?',
        'options' => [
            'That the values are factually correct',
            'That the response is valid JSON matching your fields, types and enums',
            'That the response is shorter',
            'That the model will not refuse the request',
        ],
        'answer' => 1,
        'explanation' => 'The schema constrains the shape, not the truth. You get a guaranteed parse — you still validate the values and verify anything the model claims about your data.',
    ],
    [
        'question' => 'Why prefer an enum over a free-text string for a category field?',
        'options' => [
            'Enums are cheaper in tokens',
            'The value maps straight onto a database column or PHP enum with no normalising',
            'Free text is not supported in schemas',
            'Enums make the model respond faster',
        ],
        'answer' => 1,
        'explanation' => 'Free text gives you "Billing Issue" on Monday and "billing" on Tuesday. An enum closes the set, so downstream code never needs a normalisation layer.',
    ],
    [
        'question' => 'A field is often absent in the source text. What is the safest way to model it?',
        'options' => [
            'Leave it out of "required" and hope',
            'Make it nullable and use the description to say "or null if not mentioned — never invent one"',
            'Make it required and let the model produce a placeholder',
            'Use a separate API call for that field',
        ],
        'answer' => 1,
        'explanation' => 'A required string field pressures the model to produce something. Modelling absence explicitly, with a description that forbids invention, is what stops fabricated order numbers reaching your database.',
    ],
    [
        'question' => 'Your extraction returns broken JSON. What is the most likely cause?',
        'options' => [
            'The model ignored the schema',
            'maxTokens was too low, so the JSON was truncated mid-object',
            'The schema had too many fields',
            'json_decode needs the JSON_THROW_ON_ERROR flag to work',
        ],
        'answer' => 1,
        'explanation' => 'Truncated prose is just shorter; truncated JSON is invalid. Check stopReason for max_tokens and budget enough output tokens for the whole object.',
    ],
    [
        'question' => 'The schema guaranteed the shape. Why still run Laravel validation?',
        'options' => [
            'You should not — it is redundant',
            'Because the shape is guaranteed but the content is not: lengths, business rules and referenced records still need checking',
            'Because the SDK returns strings for every type',
            'Only for nullable fields',
        ],
        'answer' => 1,
        'explanation' => 'The schema cannot express "under 140 characters" or "this order must exist". Two layers: the schema makes it parseable, validation makes it believable.',
    ],
];
