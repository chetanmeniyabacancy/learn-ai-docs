<?php

/*
|--------------------------------------------------------------------------
| Claude configuration
|--------------------------------------------------------------------------
|
| Prices are USD per 1,000,000 tokens, taken from the Anthropic pricing page.
| They are used only to show a rough cost estimate in the playground — always
| trust your Console billing page over an estimate in a tutorial app.
|
*/

return [

    // Used by the playground when the visitor has not supplied their own key.
    'api_key' => env('ANTHROPIC_API_KEY'),

    'default_model' => env('CLAUDE_MODEL', 'claude-sonnet-5'),

    'models' => [
        'claude-opus-5' => [
            'label' => 'Claude Opus 5',
            'context' => '1M',
            'input' => 5.00,
            'output' => 25.00,
            'note' => 'Deepest reasoning. Reach for it on hard agentic and coding work.',
        ],
        'claude-sonnet-5' => [
            'label' => 'Claude Sonnet 5',
            'context' => '1M',
            'input' => 3.00,
            'output' => 15.00,
            'note' => 'The workhorse. Best speed/intelligence balance for most product features.',
        ],
        'claude-haiku-4-5' => [
            'label' => 'Claude Haiku 4.5',
            'context' => '200K',
            'input' => 1.00,
            'output' => 5.00,
            'note' => 'Fastest and cheapest. Great for classification and high-volume extraction.',
        ],
    ],

];
