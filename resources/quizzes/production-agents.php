<?php

return [
    [
        'question' => 'When should a run budget be checked?',
        'options' => [
            'After each step, so you can log the overspend accurately',
            'Before each step — a budget checked afterwards measures the damage instead of preventing it',
            'Once at the start of the run',
            'Only when the provider returns a rate limit error',
        ],
        'answer' => 1,
        'explanation' => 'Steps, spend, wall-clock and the global daily total all get checked before the next call. And exceeding a budget is a designed outcome: the run stops as stopped_budget, the user is told, and a human can pick it up.',
    ],
    [
        'question' => 'Why must every write an agent can trigger be idempotent?',
        'options' => [
            'To make the code cleaner',
            'Retries, duplicate queue deliveries and resumed runs all cause the same action to be attempted twice — and the second refund is a pattern, not a bug',
            'Because the provider requires it',
            'To allow parallel execution',
        ],
        'answer' => 1,
        'explanation' => 'A key derived from run plus step, with a unique index behind it, makes a repeat harmless. Write the intent before performing the action: a row saying "about to refund" is recoverable, a refund with no row is not.',
    ],
    [
        'question' => 'A run crashed with an action in flight. Should it resume automatically?',
        'options' => [
            'Yes, that is what resumability is for',
            'No — a human decides, because the alternative is charging someone twice to save a support ticket',
            'Yes, if the budget allows it',
            'Only if the action was a read',
        ],
        'answer' => 1,
        'explanation' => 'Orphan detection should mark that run failed with the reason recorded, rather than replaying it. Auto-resume is safe only when no action was mid-flight and the budget still has room.',
    ],
    [
        'question' => 'What makes an approval queue actually work?',
        'options' => [
            'Requiring two approvers',
            'Showing the evidence the decision was based on, so review takes seconds rather than a re-investigation',
            'Sending an email for every item',
            'A confidence score on each item',
        ],
        'answer' => 1,
        'explanation' => 'A queue that takes two minutes per item gets abandoned by Thursday, and approval becomes rubber-stamping — which is worse than no approval, because it looks like a control.',
    ],
    [
        'question' => 'What belongs in an audit row for an agent action?',
        'options' => [
            'The final answer text',
            'Before and after state, the reasoning, the model, the prompt version, the actor and a link to the trace',
            'The tokens used and the cost',
            'The user\'s original message only',
        ],
        'answer' => 1,
        'explanation' => 'Assume you will have to explain one decision to a customer, a manager or a regulator. Without before and after you know something happened but not what changed — and if answering takes an engineer, you will stop answering.',
    ],
];
