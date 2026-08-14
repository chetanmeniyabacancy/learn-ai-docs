<?php

return [
    'id' => 'base-vs-instruct',
    'module' => 'training-an-llm',
    'title' => 'Base model vs instruct model',
    'intro' => 'Same weights after pretraining, then two more stages. This shows what each stage actually adds.',
    'language' => 'text',
    'code' => <<<'TEXT'
    Stage 1 — PRETRAINING (self-supervised, trillions of tokens, months)
      → a model that continues text

    Stage 2 — SUPERVISED FINE-TUNING (tens of thousands of curated pairs)
      → a model that answers questions

    Stage 3 — PREFERENCE TUNING (humans rank answers)
      → a model that is helpful, calibrated, and declines sensibly

    Same prompt sent to each:
      "What is the capital of France?"
    TEXT,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    ── after stage 1 (base model) ───────────────────────────
    "What is the capital of Germany? What is the capital of Spain?
     What is the capital of Italy?

     Answer key: 1. Paris  2. Berlin  3. Madrid  4. Rome"

    Not wrong. It decided this line came from a worksheet and
    continued the worksheet. That is what it was trained to do.

    ── after stage 2 (SFT) ──────────────────────────────────
    "The capital of France is Paris."

    ── after stage 3 (preference tuning) ────────────────────
    "The capital of France is Paris. It has been the capital
     since the 10th century and is the country's largest city."

    Also gained here: tone, saying "I'm not sure" when it isn't,
    and refusals (stop_reason: "refusal" in Level 1 module 1).
    TEXT,
    'notes' => [
        'Pretraining creates capability. SFT creates form. Preference tuning creates character.',
        'The knowledge was already there after stage 1. Stage 2 only taught it how to present it.',
        'Almost all the cost is stage 1 — tens to hundreds of millions of dollars. Stages 2 and 3 are small in comparison.',
    ],
    'live' => null,
];
