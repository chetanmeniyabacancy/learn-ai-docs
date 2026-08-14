<?php

return [
    'id' => 'overfitting',
    'module' => 'how-models-learn',
    'title' => 'Overfitting, and the split that catches it',
    'intro' => 'The failure mode that looks like your best ever model right up until it meets a real user.',
    'language' => 'php',
    'code' => <<<'PHP'
    // Split BEFORE you start. Not after you have a number you like.
    $shuffled = collect($data)->shuffle();

    $train      = $shuffled->take(700);         // fit parameters here
    $validation = $shuffled->slice(700, 150);   // tune settings here
    $test       = $shuffled->slice(850);        // touch ONCE, at the very end

    foreach ([1, 3, 9, 25] as $degree) {
        $model = $trainer->fit($train, polynomialDegree: $degree);

        printf("degree %2d   train %.3f   test %.3f\n",
            $degree,
            $model->loss($train),
            $model->loss($test),
        );
    }
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    degree     train      test
    ──────────────────────────────
     1         8.412      8.907     underfit — too simple to capture the pattern
     3         0.294      0.361     good — learned the pattern
     9         0.031      2.140     starting to memorise
    25         0.0009    14.206     overfit — memorised the answers, learned nothing

    Look at degree 25 on training data alone: 0.0009.
    Best model you have ever built. Also completely useless.
    TEXT,
    'notes' => [
        'The danger is that the training number keeps improving the whole way down the table. If you only look at that column, more parameters always looks better.',
        'If you tune against the test set, you have leaked it, and your final number is a lie. Use validation for tuning; keep test for one honest measurement at the end.',
        'This has an exact counterpart in Level 1: a golden eval set you do not tune against is the same discipline, applied to prompts instead of weights.',
    ],
    'live' => null,
];
