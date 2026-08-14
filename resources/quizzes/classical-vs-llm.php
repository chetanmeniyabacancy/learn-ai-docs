<?php

return [
    [
        'question' => 'Your team wants to predict which customers will churn, from 40 numeric columns with historical outcomes. What do you recommend?',
        'options' => [
            'An LLM with a carefully written prompt',
            'Gradient boosting or similar classical supervised ML — cheaper, faster, more accurate and explainable on tabular data',
            'An LLM with RAG over customer records',
            'Fine-tune a small language model on the table',
        ],
        'answer' => 1,
        'explanation' => 'This is the most common expensive mistake in the field. Columns plus labels is textbook supervised learning, and the classical model wins on every axis that matters.',
    ],
    [
        'question' => 'What is the LLM\'s genuine advantage over a fine-tuned classifier?',
        'options' => [
            'It is more accurate',
            'It works immediately with no training data and no labelling effort',
            'It is cheaper at high volume',
            'It is deterministic',
        ],
        'answer' => 1,
        'explanation' => 'At 10 million documents a small tuned classifier is more accurate AND 250× cheaper. At ten thousand, the LLM ships this afternoon with no labelled data. Knowing where that crossover sits is the senior judgement.',
    ],
    [
        'question' => 'Which task is the LLM the right tool for?',
        'options' => [
            'Looking up the status of order ORD-1043',
            'Validating that an email address is well formed',
            'Extracting structured fields from messy free-text supplier emails',
            'Ranking search results by relevance score',
        ],
        'answer' => 2,
        'explanation' => 'Open-ended language with infinite input variety is exactly the sweet spot. Exact lookup is SQL, format validation is a regex, and ranking is not generation.',
    ],
    [
        'question' => 'What does a well-designed hybrid pipeline look like?',
        'options' => [
            'Everything goes through the LLM, which decides what to do',
            'Cheap deterministic steps filter and handle most traffic; the LLM handles only the open-ended tail',
            'Two LLMs check each other',
            'Classical ML first, then always an LLM to confirm',
        ],
        'answer' => 1,
        'explanation' => 'Regex finds the order reference, a cheap classifier drops spam, templates answer known intents — and the expensive path only runs for what genuinely needs language understanding.',
    ],
    [
        'question' => 'Which question do teams most often skip when proposing an AI feature?',
        'options' => [
            'Which model should we use?',
            'What happens when it is wrong — who notices, and how quickly?',
            'How many tokens will it use?',
            'Should we stream the response?',
        ],
        'answer' => 1,
        'explanation' => 'It decides whether the feature is safe to build at all. Drafting a reply a human approves is a soft failure; approving refunds is not.',
    ],
];
