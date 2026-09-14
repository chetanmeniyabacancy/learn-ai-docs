<?php

return [
    [
        'question' => 'What separates a guardrail from a prompt instruction?',
        'options' => [
            'Guardrails are written in stronger language',
            'A guardrail is a check in code around the call — prompts ask, guardrails enforce',
            'Guardrails run on the provider\'s side',
            'Guardrails only apply to output',
        ],
        'answer' => 1,
        'explanation' => '"Never reveal salary bands" in a system prompt is a request that any clever input may talk its way past. A where clause, a regex check and an approval route are controls.',
    ],
    [
        'question' => 'How should you choose between a regex, a small classifier and a model-as-judge?',
        'options' => [
            'Always use the judge, for accuracy',
            'Cheapest first, sized to what the mistake actually costs — most traffic never needs the judge',
            'Use whichever is fastest to implement',
            'Use the classifier for input and the judge for output, always',
        ],
        'answer' => 1,
        'explanation' => 'A model judging every response doubles cost and latency to catch things a regex would catch. Save the expensive check for the features where being wrong is expensive.',
    ],
    [
        'question' => 'Your input guardrail spots "ignore all previous instructions". What is the best response?',
        'options' => [
            'Block the request and show an error',
            'Log it and continue — the architecture should already make it pointless, and blocking on keywords fires on curious employees while missing anything phrased differently',
            'Ban the user',
            'Send it to a model to check whether it is really an attack',
        ],
        'answer' => 1,
        'explanation' => 'Keyword matching catches lazy attempts, not clever ones, so it is a signal to log rather than a defence to rely on. Scoped tools and query-level permissions are what make a successful injection achieve nothing.',
    ],
    [
        'question' => 'Which output check is deterministic, free, and catches invented figures?',
        'options' => [
            'Asking a second model whether the answer is faithful',
            'Checking that every number in the answer also appears in the retrieved passages',
            'Measuring the answer\'s length',
            'Checking that the answer cites a passage number',
        ],
        'answer' => 1,
        'explanation' => 'It is a few lines of PHP, costs nothing, gives the same result every time, and catches the failure that matters most in a factual product: a plausible number that was never in the source.',
    ],
    [
        'question' => 'Why measure a guardrail\'s false positive rate?',
        'options' => [
            'To report it to auditors',
            'A guardrail blocking good answers is a bug that looks like safety — and it is why teams turn guardrails off',
            'To decide whether to use a bigger model',
            'False positives increase cost',
        ],
        'answer' => 1,
        'explanation' => 'Sample the triggers by hand. Above roughly 10% false positives, people stop trusting the system and eventually remove the check — which leaves you with neither safety nor trust.',
    ],
];
