<?php

return [
    [
        'question' => 'Why keep the rules in the system prompt rather than prepending them to the user message?',
        'options' => [
            'It is purely a style convention',
            'The system prompt carries more authority, stays stable for caching, and keeps user input from rewriting your rules',
            'System prompts are not billed',
            'The user message has a lower character limit',
        ],
        'answer' => 1,
        'explanation' => 'Separating stable rules from variable data is what makes prompt caching possible and is a genuine security boundary — a user cannot overwrite instructions they are not part of.',
    ],
    [
        'question' => 'Your extraction prompt guesses a date whenever one is not clearly stated. Best fix?',
        'options' => [
            'Add "BE ACCURATE!!!" in capitals',
            'Add an explicit escape hatch — "if no date is stated, reply exactly NONE" — plus an example showing it',
            'Lower maxTokens so it has less room',
            'Switch to a bigger model',
        ],
        'answer' => 1,
        'explanation' => 'Without permission to fail, "helpful" means producing an answer. An explicit escape hatch plus one demonstrating example removes a whole class of invention.',
    ],
    [
        'question' => 'What is wrong with "CRITICAL: You MUST ALWAYS use the search tool for EVERY question!!!"?',
        'options' => [
            'Nothing — emphasis improves compliance',
            'Current models follow plain instructions closely, so over-emphasis causes the rule to be over-applied',
            'Capital letters use more tokens',
            'The word CRITICAL is reserved by the API',
        ],
        'answer' => 1,
        'explanation' => 'That style exists because older models were less steerable. On current models it causes over-triggering. When a rule is applied too aggressively, turn the volume down, not up.',
    ],
    [
        'question' => 'Which change is most likely to teach an edge case that prose cannot express?',
        'options' => [
            'Repeating the rule three times',
            'Adding a few-shot example that demonstrates the edge case and its correct output',
            'Moving the rule to the end of the prompt',
            'Increasing maxTokens',
        ],
        'answer' => 1,
        'explanation' => 'Examples pin down behaviour that is fiddly to describe. "A relative date you cannot resolve is a NONE" is awkward as a rule and obvious as an example.',
    ],
    [
        'question' => 'What is the right way to iterate on a prompt?',
        'options' => [
            'Change several things at once and check two examples',
            'Change one thing, re-run the same fixed set of ~20 real inputs, compare',
            'Ask the model to improve its own prompt',
            'Add a rule for every failure you see',
        ],
        'answer' => 1,
        'explanation' => 'One change against a fixed set is the only way to attribute a difference. Adding a rule per incident is how prompts grow to 400 contradictory lines nobody will touch.',
    ],
];
