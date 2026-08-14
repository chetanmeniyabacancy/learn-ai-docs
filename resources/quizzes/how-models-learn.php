<?php

return [
    [
        'question' => 'What is literally stored inside a trained model file?',
        'options' => [
            'A compressed copy of the training data',
            'Numbers — the parameters (weights and biases) found by training',
            'The rules the model derived, in a readable form',
            'An index of question-and-answer pairs',
        ],
        'answer' => 1,
        'explanation' => 'A model is a list of numbers plus the architecture that says how to use them. "70 billion parameters" means 70 billion of those numbers.',
    ],
    [
        'question' => 'What is the loss function for?',
        'options' => [
            'To reduce the size of the model',
            'To turn "how wrong are all the predictions" into a single number that can be minimised',
            'To decide which features to keep',
            'To prevent overfitting',
        ],
        'answer' => 1,
        'explanation' => 'You cannot improve what you cannot measure. Loss collapses every error into one number, and the entire training loop exists to push that number down.',
    ],
    [
        'question' => 'Your training loss goes 18.5 → 340 → 89,000 → NaN. What is the first thing to check?',
        'options' => [
            'The model has too few parameters',
            'The learning rate is too large — the steps overshoot and diverge',
            'The training data is corrupted',
            'You need more epochs',
        ],
        'answer' => 1,
        'explanation' => 'Exploding loss is the classic symptom of too large a learning rate. Too small is the opposite failure: correct but impossibly slow.',
    ],
    [
        'question' => 'A model scores 0.001 loss on training data and 14.2 on data it has never seen. What happened?',
        'options' => [
            'It is an excellent model — the training score proves it',
            'It overfit — it memorised the training data rather than learning the pattern',
            'The test data is wrong',
            'It underfit and needs more parameters',
        ],
        'answer' => 1,
        'explanation' => 'This is the dangerous failure because on the training set it looks like your best ever model. The defence is procedural: split the data before you start, and touch the test set once.',
    ],
    [
        'question' => 'Why does it matter that every API call you make is inference rather than training?',
        'options' => [
            'Inference is more expensive',
            'The weights are frozen — nothing you send changes the model, so anything it needs to know must be in the request',
            'Inference happens on your own hardware',
            'It means responses are deterministic',
        ],
        'answer' => 1,
        'explanation' => 'This single fact is why prompting, tools and RAG all exist. Knowledge either lives in weights that somebody else froze, or in the context you supply.',
    ],
];
