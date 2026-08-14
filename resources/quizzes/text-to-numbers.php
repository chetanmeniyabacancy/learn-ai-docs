<?php

return [
    [
        'question' => 'Why do modern models use subword tokenization rather than splitting on words?',
        'options' => [
            'It produces fewer tokens for every input',
            'A word-level vocabulary is unbounded, and any unseen word becomes unrepresentable — subwords decompose anything into known pieces',
            'Subwords are faster to look up',
            'It removes the need for embeddings',
        ],
        'answer' => 1,
        'explanation' => 'Typos, product codes and new names all still work, because they break into familiar fragments. The cost is that code and non-English text tokenize less efficiently.',
    ],
    [
        'question' => 'Why can\'t you feed raw token IDs into a neural network?',
        'options' => [
            'They are too large to fit in memory',
            'The numbering is arbitrary — treating ID 3596 as "one more than" 3595 is meaningless',
            'They are strings, not numbers',
            'They change between requests',
        ],
        'answer' => 1,
        'explanation' => 'Adjacent IDs have nothing in common. One-hot encoding fixes the false ordering but makes every word equally unrelated to every other, which is why embeddings exist.',
    ],
    [
        'question' => 'Where do the numbers in an embedding come from?',
        'options' => [
            'A dictionary of hand-assigned semantic features',
            'They are parameters, learned by gradient descent under pressure to predict text well',
            'A hash of the token string',
            'The frequency of the word in the training corpus',
        ],
        'answer' => 1,
        'explanation' => 'Nobody assigns them. Words used in similar contexts drift to similar vectors because that helps next-token prediction — and the geometry ends up meaningful as a side effect.',
    ],
    [
        'question' => 'What is the difference between static and contextual embeddings?',
        'options' => [
            'Static ones are smaller',
            'A static embedding gives a word one vector forever; a contextual one computes the vector from the sentence, so "bank" differs by context',
            'Contextual embeddings are computed on the client',
            'Static embeddings cannot be used for search',
        ],
        'answer' => 1,
        'explanation' => 'A single vector has to serve "river bank" and "call the bank" simultaneously, blurring both. Attention is what produces the context-dependent version.',
    ],
    [
        'question' => 'Why does an LLM struggle with "how many r\'s are in strawberry?"',
        'options' => [
            'It cannot count',
            'It sees tokens like ["str", "aw", "berry"], not individual letters',
            'The question is ambiguous',
            'Counting requires a tool call',
        ],
        'answer' => 1,
        'explanation' => 'A consequence of the representation, not stupidity. The characters were consumed by tokenization before the model ever saw them.',
    ],
];
