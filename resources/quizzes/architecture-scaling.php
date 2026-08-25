<?php

return [
    [
        'question' => 'Where does the biggest cost saving at scale usually come from?',
        'options' => [
            'Negotiating a discount with the provider',
            'Routing: most traffic can be answered by SQL, a cache or a template, so only the genuinely open-ended tail reaches an expensive model',
            'Using a smaller model for everything',
            'Reducing the number of output tokens',
        ],
        'answer' => 1,
        'explanation' => 'On real traffic the distribution is lopsided in your favour — order lookups, repeats and templatable intents are often two-thirds of messages. Routing them away typically cuts blended cost by more than half with no quality loss.',
    ],
    [
        'question' => 'What must be included in an exact-match cache key?',
        'options' => [
            'The question only',
            'The question, the tenant, and the prompt version',
            'The question and the user id',
            'The question and a timestamp',
        ],
        'answer' => 1,
        'explanation' => 'Without the tenant you leak answers across customers. Without the prompt version your fix does nothing until the cache expires. Both are one-line mistakes with serious consequences.',
    ],
    [
        'question' => 'Why keep a semantic cache threshold high, around 0.95?',
        'options' => [
            'To keep the cache small',
            'Because a near-miss serves a confidently wrong answer — it is the one optimisation that can be fast, cheap and incorrect',
            'Because embeddings are inaccurate below 0.95',
            'To reduce embedding cost',
        ],
        'answer' => 1,
        'explanation' => 'Exact and prefix caching can only ever serve something correct. A loose semantic cache answers a different question than the one asked, and the user has no way to tell.',
    ],
    [
        'question' => 'When should work go to the Batch API rather than the live path?',
        'options' => [
            'When the model is slow',
            'When nobody is waiting and there are many items — roughly half price, and it keeps your rate limit for requests with a person attached',
            'When the request is large',
            'When you need higher accuracy',
        ],
        'answer' => 1,
        'explanation' => 'Nightly reclassification, embedding backfills and weekly summaries all qualify. Moving them off the live path protects both your latency and your rate limit.',
    ],
    [
        'question' => 'Cost is mostly input tokens and latency is mostly generation. What follows?',
        'options' => [
            'Use a faster model and longer prompts',
            'To cut cost, retrieve fewer chunks, trim history and cache prefixes. To cut perceived latency, stream, cap maxTokens and ask for shorter answers',
            'Reduce the number of API calls only',
            'Increase maxTokens so answers finish sooner',
        ],
        'answer' => 1,
        'explanation' => 'The two levers act in different places. You cannot make generation fast — only shorter, or better disguised by streaming the first words quickly.',
    ],
];
