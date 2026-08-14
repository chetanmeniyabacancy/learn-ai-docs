<?php

return [
    'id' => 'xor',
    'module' => 'neural-networks',
    'title' => 'XOR: why hidden layers exist',
    'intro' => 'The same neuron that solved AND in two epochs cannot solve XOR in a thousand. In 1969 this observation nearly killed neural network research for a decade.',
    'language' => 'php',
    'code' => <<<'PHP'
    // Identical code to the AND example. Only the data changed.
    $data = [[[0,0],0], [[0,1],1], [[1,0],1], [[1,1],0]];

    [$w, $b, $log] = trainPerceptron($data, epochs: 60);

    // …still wrong. Now add ONE hidden layer:
    //
    //   inputs ──┬──► h1 ──┬──► output
    //            └──► h2 ──┘
    //
    // The hidden layer transforms the space; in the NEW space
    // a single straight line does separate the classes.
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    ── single neuron ────────────────────────────────────────
    stopped after 60 epochs, still 4 errors per pass

    predictions: [0,0]→1 (want 0)   [0,1]→1 (want 1)
                 [1,0]→0 (want 1)   [1,1]→0 (want 0)

    accuracy: 2/4 = 50%      ← a coin flip

    why — plot it:
           1 │  ●(1)      ○(0)
             │
           0 │  ○(0)      ●(1)
             └──────────────────
                0            1

           no single straight line separates ● from ○

    ── with one hidden layer (2 neurons, sigmoid) ───────────
    epoch    500   loss 0.2043
    epoch   2000   loss 0.0052
    epoch   4000   loss 0.0017
    epoch   8000   loss 0.0007

    predictions: [0,0]→0   [0,1]→1   [1,0]→1   [1,1]→0
    accuracy: 4/4 = 100%
    TEXT,
    'notes' => [
        'A single neuron IS a straight line. AND and OR are separable by one line, so it can learn them; XOR is not, so it cannot — no amount of training helps.',
        'This is what "depth" buys you, made concrete. The first layer bends the space so the second can draw its line.',
        'It is also why the activation function matters: without the bend, stacking layers gives you another straight line and XOR stays impossible.',
    ],
    'live' => null,
];
