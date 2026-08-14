<?php

return [
    [
        'question' => '"Which customers will cancel next month?" — given historical data on who did cancel, what kind of problem is this?',
        'options' => [
            'Unsupervised learning — you are finding patterns',
            'Supervised classification — you have inputs with known correct answers',
            'Reinforcement learning — you optimise retention over time',
            'Generative AI — it produces a prediction',
        ],
        'answer' => 1,
        'explanation' => 'Past customers plus a known outcome is a labelled dataset, and the label is a category. This is textbook supervised classification — and gradient boosting will beat an LLM on it comfortably.',
    ],
    [
        'question' => 'What is the crucial trick behind self-supervised learning?',
        'options' => [
            'It needs no data at all',
            'The data provides its own labels — hide the next word, and the hidden word is the label',
            'It uses humans to label data more efficiently',
            'It supervises other models during training',
        ],
        'answer' => 1,
        'explanation' => 'Every sentence ever written becomes a free training example. That is what made training on trillions of tokens possible, and it is why LLMs exist at all.',
    ],
    [
        'question' => 'You run clustering on your customers and get four groups. What can you conclude?',
        'options' => [
            'There are genuinely four types of customer',
            'Nothing yet — the algorithm always returns groups; whether they mean anything is your judgement',
            'The model is 100% accurate because there were no labels to get wrong',
            'You should re-run it with labels',
        ],
        'answer' => 1,
        'explanation' => 'Unsupervised learning has no right answer to check against. Ask for four groups and you get four groups. Naming and validating them is human work.',
    ],
    [
        'question' => 'What is the most commonly underestimated cost in a supervised learning project?',
        'options' => [
            'GPU time',
            'Producing the labels — often somebody hand-labelling thousands of records',
            'Choosing the algorithm',
            'Model hosting',
        ],
        'answer' => 1,
        'explanation' => 'Supervised learning is defined by having labels. "We will use AI for this" quietly means "someone must label 5,000 records first" — worth asking about on day one.',
    ],
    [
        'question' => 'Which of these is accidental training data you probably already have?',
        'options' => [
            'Your application logs',
            'A column recording which category a human filed each ticket under',
            'Your database schema',
            'Your API documentation',
        ],
        'answer' => 1,
        'explanation' => 'A recorded human decision is a label. Ticket categories, paid/unpaid, reopened/not, clicked/not — your database is full of supervised datasets you already own.',
    ],
];
