<?php

return [
    'id' => 'few-shot',
    'module' => 'prompt-engineering',
    'title' => 'Few-shot: showing beats telling',
    'intro' => 'Some rules are awkward to state and obvious to demonstrate. "A relative date you cannot resolve is not a date" is one of them.',
    'language' => 'php',
    'code' => <<<'PHP'
    // Without examples — it guesses at anything date-shaped
    $withoutExamples = 'Extract the delivery date. Reply with an ISO date only.';

    // With examples — the third one is the important one
    $withExamples = <<<'TXT'
    Extract the delivery date from customer messages.
    Reply with an ISO date and nothing else. If there is no resolvable date,
    reply exactly: NONE

    Examples:
    Input: "It was meant to come on the 3rd of March"
    Output: 2026-03-03

    Input: "Still waiting, ordered ages ago"
    Output: NONE

    Input: "Arriving next Tuesday apparently"
    Output: NONE
    TXT;

    foreach (['Should be here by the 14th of August', 'Sometime next week hopefully'] as $input) {
        echo $claude->ask($withoutExamples, $input)." | ".$claude->ask($withExamples, $input)."\n";
    }
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
                                        without examples │ with examples
    ─────────────────────────────────────────────────────┼───────────────
    "Should be here by the 14th of August"    2026-08-14 │ 2026-08-14
    "Sometime next week hopefully"            2026-08-19 │ NONE
                                              ▲
                                    invented — there is no
                                    Tuesday in that sentence
    TEXT,
    'notes' => [
        'One example removed a whole class of invention. Try writing that rule in prose and see how long it takes.',
        'Two or three examples is usually the sweet spot. Twenty is expensive and starts to over-constrain.',
        'Examples pin tone and length too — the model matches what you show it, so keep them the shape you actually want.',
    ],
    'live' => 'chat',
];
