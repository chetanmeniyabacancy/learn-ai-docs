<?php

return [
    [
        'question' => 'Which statement about the relationship between these terms is correct?',
        'options' => [
            'Machine learning and AI are the same thing',
            'Generative AI is a subset of deep learning, which is a subset of machine learning, which is a subset of AI',
            'Deep learning replaced machine learning entirely',
            'LLMs and generative AI are two separate fields',
        ],
        'answer' => 1,
        'explanation' => 'They nest. A 1997 chess engine was AI with no machine learning in it; most deployed machine learning is not deep learning; most deep learning is not generative.',
    ],
    [
        'question' => 'What is the defining difference between a rule-based system and a machine learning one?',
        'options' => [
            'Machine learning is always more accurate',
            'In rule-based systems a human writes the logic; in machine learning the logic is numbers derived from data',
            'Rule-based systems cannot handle text',
            'Machine learning does not need any code',
        ],
        'answer' => 1,
        'explanation' => 'That is the whole boundary. Nobody writes "many exclamation marks suggests spam" — training discovers it, along with thousands of patterns nobody would have thought of.',
    ],
    [
        'question' => 'A churn predictor and a chatbot are both called AI. What is the most useful thing to say about them?',
        'options' => [
            'They are basically the same technology with different interfaces',
            'They sit in different boxes — one is discriminative machine learning, the other is generative — and have almost nothing in common technically',
            'The chatbot is more advanced, so it could do the churn prediction too',
            'Both require training on your own data',
        ],
        'answer' => 1,
        'explanation' => 'One draws a boundary between classes; the other predicts what comes next and generates it. Confusing them is how a tabular problem ends up being solved expensively with an LLM.',
    ],
    [
        'question' => 'Why does an LLM confidently invent a refund policy it has never seen?',
        'options' => [
            'It is a bug in the current generation of models',
            'Because it generates plausible continuations, and a plausible continuation of "our refund policy is" is a normal-sounding refund policy',
            'Because the temperature is set too high',
            'Because it was trained on incorrect data',
        ],
        'answer' => 1,
        'explanation' => 'It is not looking anything up — there is nothing to look up in. It continues text plausibly. That is the mechanism, which is why the fixes are structural (give it the real text) rather than corrective.',
    ],
    [
        'question' => 'Which is NOT a consequence of calling a pretrained model over an API?',
        'options' => [
            'Nothing you send changes its weights',
            'It knows nothing about your business unless you put it in the request',
            'The same input can produce different output',
            'It gradually improves as your users interact with it',
        ],
        'answer' => 3,
        'explanation' => 'That last one is the common and expensive misconception. The weights were frozen before you touched it; usage does not train it. This is exactly why RAG exists.',
    ],
];
