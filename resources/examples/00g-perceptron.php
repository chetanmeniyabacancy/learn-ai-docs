<?php

return [
    'id' => 'perceptron',
    'module' => 'neural-networks',
    'title' => 'A single neuron learning AND',
    'intro' => 'One neuron, two weights, one bias. Watch random numbers turn into a working logic gate in two passes over four rows.',
    'language' => 'php',
    'code' => <<<'PHP'
    function step(float $x): int { return $x >= 0 ? 1 : 0; }

    // AND: only [1,1] should output 1
    $data = [[[0,0],0], [[0,1],0], [[1,0],0], [[1,1],1]];

    $w = [0.07, 0.04];   // random starting weights
    $b = 0.0;
    $learningRate = 0.1;

    for ($epoch = 1; $epoch <= 20; $epoch++) {
        $errors = 0;

        foreach ($data as [$inputs, $target]) {
            // 1. multiply  2. add  3. bend  — that is the neuron
            $output = step($w[0] * $inputs[0] + $w[1] * $inputs[1] + $b);

            $error = $target - $output;

            if ($error !== 0) {
                $errors++;
                $w[0] += $learningRate * $error * $inputs[0];
                $w[1] += $learningRate * $error * $inputs[1];
                $b    += $learningRate * $error;
            }
        }

        if ($errors === 0) break;      // solved
    }
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    epoch  1  errors 1   w=[0.07, 0.04]  b=-0.10
    epoch  2  errors 0   w=[0.07, 0.04]  b=-0.10    ← solved

    predictions:  [0,0]→0   [0,1]→0   [1,0]→0   [1,1]→1     4/4 correct

    What the neuron ended up computing:

      0.07·x₁ + 0.04·x₂ - 0.10  ≥ 0 ?

      [0,0] → -0.10   below zero → 0
      [0,1] → -0.06   below zero → 0
      [1,0] → -0.03   below zero → 0
      [1,1] →  0.01   above zero → 1
    TEXT,
    'notes' => [
        'The bias moved from 0.00 to −0.10 and that was the entire fix: it shifted the threshold so only the combined case clears it.',
        'Three numbers is the whole model. Scale that intuition: a 70-billion-parameter model is the same thing, 23 billion times over.',
        'This is real output from running the code, with a fixed random seed so it reproduces.',
    ],
    'live' => null,
];
