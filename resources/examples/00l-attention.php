<?php

return [
    'id' => 'attention',
    'module' => 'transformers',
    'title' => 'Attention weights, by hand',
    'intro' => 'What "every token looks at every other token" actually computes — and why the model gets the long-range link right where older architectures forgot.',
    'language' => 'php',
    'code' => <<<'PHP'
    // For each token the model produces three vectors from its embedding,
    // using three learned weight matrices:
    //
    //   query  — "what am I looking for?"
    //   key    — "what do I offer?"
    //   value  — "what do I contribute if chosen?"

    function attention(array $query, array $keys, array $values): array
    {
        $d = count($query);

        // 1. score every key against the query
        $scores = array_map(fn ($k) => dot($query, $k), $keys);

        // 2. scale so scores don't explode with vector length
        $scores = array_map(fn ($s) => $s / sqrt($d), $scores);

        // 3. softmax → weights that sum to 1
        $weights = softmax($scores);

        // 4. weighted average of the values
        return weightedSum($weights, $values);
    }
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    Sentence:
      "The invoice that Priya sent last Tuesday for the Manchester job was never ___"

    Attention weights when predicting the next token:

      token           weight
      ─────────────────────────
      "The"            0.02
      "invoice"        0.41   ████████████████████  ← most of the signal
      "that"           0.01
      "Priya"          0.08   ████
      "sent"           0.11   █████
      "last"           0.01
      "Tuesday"        0.03   █
      "for"            0.01
      "the"            0.01
      "Manchester"     0.06   ███
      "job"            0.09   ████
      "was"            0.12   ██████
      "never"          0.04   ██
                       ────
                       1.00

    The output vector is mostly "invoice" → " paid" scores highest.
    TEXT,
    'notes' => [
        '"Invoice" is nine tokens back. Older architectures (RNNs, LSTMs) processed in order and carried context in a fixed-size memory, so that link faded. Attention reaches it in one step.',
        'This is also what makes embeddings <em>contextual</em>: "bank" next to "river" attends to different neighbours than "bank" next to "mortgage", so it ends up as a different vector.',
        'Every token is compared with every other, which is why cost grows faster than linearly with context length — and why a huge context window is still not a substitute for good retrieval.',
        'Multi-head attention runs several of these in parallel with different learned weights, so one head can track grammar while another tracks subject matter.',
    ],
    'live' => null,
];
