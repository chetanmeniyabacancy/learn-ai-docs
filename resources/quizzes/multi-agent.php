<?php

return [
    [
        'question' => 'What is the real reason to use multiple agents?',
        'options' => [
            'Several agents are collectively more intelligent than one',
            'Context: a worker can read 20 pages and return 200 words, so the orchestrator reasons over conclusions instead of raw text',
            'It is faster in every case',
            'It reduces the number of API calls',
        ],
        'answer' => 1,
        'explanation' => 'A worker\'s context is discarded when it finishes — only its conclusion survives. That is what keeps the orchestrator\'s context small enough to think in. It is a context-window solution, not an intelligence one.',
    ],
    [
        'question' => 'Worker B needs the conclusion from worker A. What have you actually got?',
        'options' => [
            'A multi-agent system with a dependency',
            'A chain — dependent steps are sequential orchestration, not fan-out',
            'A voting system',
            'A memory problem',
        ],
        'answer' => 1,
        'explanation' => 'Fan-out requires independence. If one step feeds the next, that is the chain shape from module 14, and treating it as a fan-out just adds coordination bugs.',
    ],
    [
        'question' => 'Why should workers return structured output rather than prose?',
        'options' => [
            'It uses fewer tokens',
            'Prose from five workers is a parsing problem you gave yourself — and a required quote per finding keeps them from inventing things',
            'Structured output is more accurate in general',
            'The orchestrator cannot read prose',
        ],
        'answer' => 1,
        'explanation' => 'A schema per worker means the orchestrator merges data instead of interpreting essays. Requiring an exact quote per finding also makes the report checkable by a human.',
    ],
    [
        'question' => 'One worker fails. What must not happen?',
        'options' => [
            'The whole run is abandoned',
            'The final report silently covers only the sections that succeeded',
            'The worker is retried once',
            'The failure is logged',
        ],
        'answer' => 1,
        'explanation' => 'Silent partial coverage is the dangerous failure of this pattern. The orchestrator must know what it dispatched and state plainly which sections could not be reviewed.',
    ],
    [
        'question' => 'How should models be allocated between orchestrator and workers?',
        'options' => [
            'The best model everywhere, for consistency',
            'A cheap model for the reading-heavy workers, a good model for the judgement and synthesis',
            'The cheapest model everywhere, since workers are simple',
            'It makes no measurable difference',
        ],
        'answer' => 1,
        'explanation' => 'Reading is cheap work; judging is not. Routing the reading to a small model and the synthesis to a good one is often both cheaper and better than one big agent doing everything itself.',
    ],
];
