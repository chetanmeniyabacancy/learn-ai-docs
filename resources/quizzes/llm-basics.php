<?php

return [
    [
        'question' => 'Sonnet holds about a million tokens of context. Why still bother with RAG?',
        'options' => [
            'Because RAG is more accurate than the model',
            'Because cost, latency and accuracy all get worse as you send more irrelevant text',
            'Because the context window is shared across all users',
            'Because documents cannot be sent in the context window',
        ],
        'answer' => 1,
        'explanation' => 'You pay per token on every request, latency scales with input, and precision drops when the answer is buried in noise. The context window is a ceiling, not a target.',
    ],
    [
        'question' => 'Which is the most reliable way to reduce hallucination?',
        'options' => [
            'Adding "do not hallucinate" to the system prompt',
            'Lowering the temperature',
            'Giving the model the source text and requiring citations',
            'Switching to a larger model',
        ],
        'answer' => 2,
        'explanation' => 'Hallucination is what a text predictor does without grounding. The structural fixes are giving it real data (RAG, tools) and letting it say "I don\'t know". Temperature does not even exist on current Claude models.',
    ],
    [
        'question' => 'You need to classify 50,000 short messages a month and quality is already good on the cheapest model. Which do you pick?',
        'options' => [
            'Claude Opus 5 — always use the strongest model',
            'Claude Haiku 4.5 — cheapest and fastest, and evals show quality holds',
            'Claude Sonnet 5 — the default, regardless of the evidence',
            'Alternate between models to spread the load',
        ],
        'answer' => 1,
        'explanation' => 'Start on Sonnet, then move down to Haiku once evals prove quality holds. On high-volume classification that is a 3× saving for no quality loss.',
    ],
    [
        'question' => 'How should you count tokens for a document before sending it?',
        'options' => [
            'str_word_count() multiplied by 1.3',
            'tiktoken, the standard tokenizer',
            'The countTokens endpoint with the same model you will use',
            'strlen() divided by 4',
        ],
        'answer' => 2,
        'explanation' => 'Tokenization is model-specific. tiktoken is OpenAI\'s and undercounts Claude tokens badly on code. Rough estimates are fine for intuition; use countTokens when the number matters.',
    ],
    [
        'question' => 'A colleague\'s prompt sets temperature: 0.2 on claude-sonnet-5 and the request fails. Why?',
        'options' => [
            'Temperature must be an integer',
            'Sampling parameters were removed on current Claude models — a non-default value returns a 400',
            'Temperature is only allowed with thinking disabled',
            'The value is too low; the minimum is 0.5',
        ],
        'answer' => 1,
        'explanation' => 'temperature, top_p and top_k have been removed on current models. Most tutorials online predate the change. Steer style and variety through the prompt instead.',
    ],
];
