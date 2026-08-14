<?php

return [
    [
        'question' => 'What does a single neuron compute?',
        'options' => [
            'A probability between 0 and 1',
            'A weighted sum of its inputs, plus a bias, passed through an activation function',
            'The similarity between two vectors',
            'The gradient of the loss',
        ],
        'answer' => 1,
        'explanation' => 'Multiply, add, bend. Three lines of code. Everything else — vision, translation, Claude — is arrangement and scale.',
    ],
    [
        'question' => 'What happens if you remove the activation function from every neuron in a 100-layer network?',
        'options' => [
            'It trains faster with the same capability',
            'The whole network collapses to a single linear function — no more powerful than one line of algebra',
            'It can only handle binary classification',
            'Nothing — activations are an optimisation',
        ],
        'answer' => 1,
        'explanation' => 'A linear function of a linear function is linear. The non-linearity is what makes depth mean anything, which is exactly what XOR demonstrates.',
    ],
    [
        'question' => 'Why is XOR the standard example for why hidden layers exist?',
        'options' => [
            'It requires more training data than AND or OR',
            'No single straight line separates its two classes, so one neuron cannot learn it — a hidden layer can',
            'It is the only function neural networks cannot learn',
            'It needs a different activation function',
        ],
        'answer' => 1,
        'explanation' => 'Plot the four points and try to draw one line separating the 1s from the 0s. You cannot. A hidden layer transforms the space so that in the new space, a line does work.',
    ],
    [
        'question' => 'What does backpropagation do?',
        'options' => [
            'Reverses the network to generate inputs from outputs',
            'Passes the error backwards through the layers to work out each parameter\'s share of the blame, so each can step downhill',
            'Removes neurons that are not contributing',
            'Reloads the previous checkpoint when loss increases',
        ],
        'answer' => 1,
        'explanation' => 'It is the same gradient descent loop from module F3, extended so that a weight buried in layer 1 knows how much it contributed to an error at the output.',
    ],
    [
        'question' => 'What does "70 billion parameters" tell you as an application engineer?',
        'options' => [
            'The model was trained on 70 billion documents',
            '70 billion numbers must be held and multiplied through per token — which is why bigger models cost more and run slower',
            'It can hold 70 billion tokens of context',
            'It has 70 billion layers',
        ],
        'answer' => 1,
        'explanation' => 'Parameters are the learned numbers. More of them means more computation per token, which is the whole cost-and-speed trade-off between Haiku, Sonnet and Opus.',
    ],
];
