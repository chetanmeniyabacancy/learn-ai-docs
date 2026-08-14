<?php

return [
    'id' => 'softmax',
    'module' => 'transformers',
    'title' => 'Logits → probabilities, and what temperature did',
    'intro' => 'The last step of every LLM response, in six lines. Real numbers from running it, including what the temperature parameter used to do before it was removed.',
    'language' => 'php',
    'code' => <<<'PHP'
    function softmax(array $logits, float $temperature = 1.0): array
    {
        // Temperature reshapes the scores BEFORE normalising
        $scaled = array_map(fn ($x) => $x / $temperature, $logits);

        $max = max($scaled);                                    // numerical stability
        $exp = array_map(fn ($x) => exp($x - $max), $scaled);
        $sum = array_sum($exp);

        return array_map(fn ($e) => $e / $sum, $exp);
    }

    // Raw model output for "The customer wants a ___"
    $logits = [8.2, 6.9, 6.1, 4.8, -3.1];
    $tokens = [' refund', ' replacement', ' discount', ' new', ' banana'];

    foreach ([0.2, 1.0, 2.0] as $t) {
        print_r(array_combine($tokens, softmax($logits, $t)));
    }
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    logits (raw scores — can be negative, sum to nothing):
       refund 8.2   replacement 6.9   discount 6.1   new 4.8   banana -3.1

    ── temperature 0.2 ──────────────────────────────────────
       refund 0.9985   replacement 0.0015   discount 0.0000
       new    0.0000   banana      0.0000            effectively deterministic

    ── temperature 1.0 (the natural distribution) ───────────
       refund 0.7001   replacement 0.1908   discount 0.0857
       new    0.0234   banana      0.0000

    ── temperature 2.0 ──────────────────────────────────────
       refund 0.4859   replacement 0.2536   discount 0.1700
       new    0.0888   banana      0.0017            adventurous

    every column sums to exactly 1.0
    TEXT,
    'notes' => [
        'Notice how a 1.3 gap in logits (8.2 vs 6.9) becomes a 3.7× gap in probability. Exponentiating means small score differences become large probability differences.',
        '<strong>Current Claude models removed <code>temperature</code></strong> — sending a non-default value returns a 400. You steer with the prompt instead. Knowing what it did is still worth having, because most tutorials online still set it.',
        'Pick a token from this distribution, append it, run the whole model again on the longer sequence. That loop, one token at a time, produced every LLM response you have ever seen — and is why streaming works.',
        'Real numbers from running this code.',
    ],
    'live' => null,
];
