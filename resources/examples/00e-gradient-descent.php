<?php

return [
    'id' => 'gradient-descent',
    'module' => 'how-models-learn',
    'title' => 'Training a model from scratch, in PHP',
    'intro' => 'The complete training loop with two parameters and real numbers. This is the same loop that trained Claude — the only differences are the number of parameters and a more complicated predict().',
    'language' => 'php',
    'code' => <<<'PHP'
    // distance (100km units) → actual delivery days
    $data = [[1.0, 2.0], [2.0, 2.8], [3.0, 4.1], [4.0, 4.9], [5.0, 6.2]];

    function predict(float $x, float $w, float $b): float {
        return $w * $x + $b;
    }

    // ONE number for how wrong the whole model is
    function loss(array $d, float $w, float $b): float {
        $t = 0.0;
        foreach ($d as [$x, $y]) { $t += (predict($x, $w, $b) - $y) ** 2; }
        return $t / count($d);
    }

    // Which way is downhill for each parameter?
    function gradients(array $d, float $w, float $b): array {
        $dw = 0.0; $db = 0.0;
        foreach ($d as [$x, $y]) {
            $error = predict($x, $w, $b) - $y;
            $dw += 2 * $error * $x;
            $db += 2 * $error;
        }
        return [$dw / count($d), $db / count($d)];
    }

    $w = 0.0; $b = 0.0; $learningRate = 0.01;

    for ($epoch = 1; $epoch <= 1000; $epoch++) {
        [$dw, $db] = gradients($data, $w, $b);
        $w -= $learningRate * $dw;      // the minus sign IS "learning"
        $b -= $learningRate * $db;
    }
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    epoch    0   loss 18.2200   w 0.000   b 0.000    ← untrained: predicts 0 days for everything
    epoch    1   loss 10.6441   w 0.282   b 0.080
    epoch    5   loss  1.2827   w 0.882   b 0.254
    epoch  200   loss  0.0277   w 1.123   b 0.586
    epoch  400   loss  0.0183   w 1.087   b 0.716
    epoch  600   loss  0.0158   w 1.069   b 0.782
    epoch 1000   loss  0.0151   w 1.055   b 0.832

    learned:  days = 1.055 × distance + 0.832

    ── learning rate 0.5 instead of 0.01 ────────────────────
    epoch 1   loss 2,129.9
    epoch 2   loss 249,853.0
    epoch 3   loss 29,310,133.1
    epoch 4   loss 3,438,357,611.8
    epoch 5   loss 403,352,077,412.3        ← diverging; ends at INF then NaN
    TEXT,
    'notes' => [
        'Nobody wrote the formula <code>1.055 × distance + 0.832</code>. The loop found it by repeatedly asking "which way is downhill?" and taking a small step.',
        'That second block is worth seeing once. If you ever see a training loss go to <code>NaN</code>, the learning rate is the first suspect — and now you know exactly why.',
        'Scale this to hundreds of billions of parameters, a transformer instead of <code>w·x + b</code>, cross-entropy instead of squared error, and months of GPU time. That is pretraining. The shape is identical.',
        'These are real numbers from actually running this code, not illustrative ones.',
    ],
    'live' => null,
];
