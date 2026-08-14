<?php

return [
    'id' => 'self-supervised',
    'module' => 'learning-types',
    'title' => 'How text labels itself',
    'intro' => 'The trick that made LLMs possible, in twelve lines. Labelling enough text to teach a machine language would take humanity longer than it has — so the data labels itself.',
    'language' => 'php',
    'code' => <<<'PHP'
    // Take ANY text. No human labelling required.
    $corpus = "The refund window is 30 days from delivery.";

    $tokens = explode(' ', $corpus);
    $examples = [];

    // Every prefix is an input; the next token is the label.
    for ($i = 1; $i < count($tokens); $i++) {
        $examples[] = [
            'input' => implode(' ', array_slice($tokens, 0, $i)),
            'label' => $tokens[$i],
        ];
    }

    // One 8-word sentence just produced 7 training examples,
    // for free, with nobody labelling anything.
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    input                              → label
    ───────────────────────────────────────────
    "The"                              → refund
    "The refund"                       → window
    "The refund window"                → is
    "The refund window is"             → 30
    "The refund window is 30"          → days
    "The refund window is 30 days"     → from
    "The refund window is 30 days from"→ delivery.

    7 examples from 8 words.

    Scale that up:
      this sentence                 7 examples
      one book                      ~100,000
      Wikipedia                     ~4,000,000,000
      a pretraining corpus          ~10,000,000,000,000

    Human labelling cost: zero.
    TEXT,
    'notes' => [
        'Mechanically this is supervised learning. The supervision just comes from the structure of the data rather than from people — which is the entire unlock.',
        'The surprise, genuinely a surprise to the field, was that a model which gets very good at this one boring task also picks up grammar, facts, translation, arithmetic and code. To predict text well across the whole internet, you have to model a lot about the world.',
        'Everything an LLM does well <em>and</em> everything it does badly comes from this objective. It was never trained to be correct — only to continue plausibly.',
    ],
    'live' => null,
];
