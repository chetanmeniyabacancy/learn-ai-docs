<?php

return [
    [
        'question' => 'What does softmax do in the output layer?',
        'options' => [
            'Selects the highest-scoring token',
            'Converts raw scores (logits) into a probability distribution that sums to 1',
            'Removes tokens below a threshold',
            'Compresses the vocabulary',
        ],
        'answer' => 1,
        'explanation' => 'Logits can be negative and sum to anything. Softmax exponentiates and normalises them, producing probabilities the sampler can then draw from.',
    ],
    [
        'question' => 'In attention, what are the query, key and value vectors for?',
        'options' => [
            'Encryption of the token stream',
            'Query = what this token is looking for, key = what each token offers, value = what it contributes if selected',
            'Three redundant copies for error correction',
            'Input, hidden and output representations',
        ],
        'answer' => 1,
        'explanation' => 'Dot every query against every key to score relevance, softmax the scores into weights, then take a weighted average of the values. That is attention in four steps.',
    ],
    [
        'question' => 'Why was attention such an improvement over the RNNs that preceded it?',
        'options' => [
            'It uses fewer parameters',
            'Every token can look directly at every other token in one step, and all tokens can be processed in parallel during training',
            'It removes the need for embeddings',
            'It guarantees factual accuracy',
        ],
        'answer' => 1,
        'explanation' => 'RNNs processed in order and carried context in a fixed-size memory, so long-range links faded. Parallelism is the bigger deal though — it is what let training scale to GPU clusters.',
    ],
    [
        'question' => 'A colleague sets temperature: 0.3 on claude-sonnet-5 and the request fails. Why, and what did the parameter do?',
        'options' => [
            'It only works with streaming; it controlled response length',
            'Sampling parameters were removed on current Claude models; it used to flatten or sharpen the probability distribution before sampling',
            'The value is out of range; it controlled the context window',
            'It requires a beta header; it controlled thinking depth',
        ],
        'answer' => 1,
        'explanation' => 'Dividing logits by a temperature before softmax made output more or less adventurous. Current models removed it — you steer with the prompt instead — but most tutorials online still set it.',
    ],
    [
        'question' => 'Why does the position of an instruction in a long prompt matter?',
        'options' => [
            'Tokens later in the prompt are cheaper',
            'Attention spreads across all tokens, and the start and end of the prompt are attended to most reliably',
            'The model only reads the first 1,000 tokens',
            'Position has no measurable effect',
        ],
        'answer' => 1,
        'explanation' => 'This is why the practical advice is "role first, format rules last". It is a property of the mechanism, not a superstition.',
    ],
];
