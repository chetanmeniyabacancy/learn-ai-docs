<?php

return [
    [
        'question' => 'Why grade an agent\'s trajectory and not just its final answer?',
        'options' => [
            'Trajectories are easier to score automatically',
            'A right answer reached by a wrong route — guessing an id, skipping the eligibility check — will not repeat reliably',
            'Final answers are always correct if the tools worked',
            'It reduces evaluation cost',
        ],
        'answer' => 1,
        'explanation' => 'Scoring only the answer teaches you to accept lucky runs. A trajectory case states what must be called, what must never be called, what order matters, and how many steps are acceptable.',
    ],
    [
        'question' => 'A case passes 4 out of 5 runs. What do you report?',
        'options' => [
            'Pass — the majority succeeded',
            'A pass rate of 80%, which means it fails one time in five in production',
            'Pass, with a note about the flaky run',
            'Fail — anything under 100% is a failure',
        ],
        'answer' => 1,
        'explanation' => 'Reporting "pass" would be a lie you told yourself. Report the rate and pick a bar per category: reading a balance should be 100%; drafting a reply may be fine at 90%.',
    ],
    [
        'question' => 'What makes an LLM judge trustworthy enough to act on?',
        'options' => [
            'Using the largest available model',
            'A specific rubric of yes/no questions, plus hand-auditing a sample until it agrees with you more than about 90% of the time',
            'Running it three times and averaging',
            'Asking it to rate answers from 1 to 10',
        ],
        'answer' => 1,
        'explanation' => '"Rate this 1-10" produces noise. Definitions and booleans produce signal. And judge agreement belongs in your report — it is the number that tells you whether to believe the rest of it.',
    ],
    [
        'question' => 'Why keep regression, capability and adversarial suites separate?',
        'options' => [
            'To make the test run faster',
            'They have different bars: regression and adversarial block a deploy, capability is a scoreboard you expect to trend up',
            'Because they use different models',
            'To spread cost across the week',
        ],
        'answer' => 1,
        'explanation' => 'Put them in one file and a hard capability case blocks a deploy — so someone deletes the hard cases. Separating them keeps the gate meaningful and keeps ambition alive.',
    ],
    [
        'question' => 'Your offline suite passes and users still say it got worse. What is missing?',
        'options' => [
            'More offline cases',
            'Online measurement: sampled traffic through a judge, implicit signals like repeat questions and escalations, and thumbs-down feeding back into the suite',
            'A larger model',
            'More frequent CI runs',
        ],
        'answer' => 1,
        'explanation' => 'Offline evals answer "did I make it worse?" — they cannot answer "is this good for users". Every thumbs-down becoming a regression case is the single habit that makes a suite get stronger over time.',
    ],
];
