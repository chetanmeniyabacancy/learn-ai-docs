<?php

return [
    'id' => 'rag-refusal',
    'module' => 'rag',
    'title' => 'Refusing is the valuable behaviour',
    'intro' => 'The most useful thing a document assistant does is admit when the answer is not there. There are two places to catch it, and the cheaper one comes first.',
    'language' => 'php',
    'code' => <<<'PHP'
    $chunks = $this->retriever->search($question, limit: 4);

    // Guard 1 — nothing scored well enough. No model call at all:
    // zero tokens, zero latency, zero chance of invention.
    if ($chunks->isEmpty() || $chunks->first()['score'] < 0.45) {
        return RagAnswer::notFound();
    }

    // Guard 2 — something scored, but it is not actually the answer.
    // The system prompt's "say I could not find that" rule catches this.
    return $this->claude->ask(RagPrompt::system(), RagPrompt::user($chunks, $question));
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    Q: "What is the CEO's home address?"

      retrieval → best score 0.31  (a chunk about changing a delivery address)
      guard 1   → below 0.45, short-circuit
      answer    → "I could not find that in the documentation."
      cost      → $0.000000   (no API call was made)

    Q: "Do you offer a student discount?"

      retrieval → best score 0.52  (a chunk about payment methods — related, wrong)
      guard 1   → passes
      guard 2   → model reads the passage, sees no discount policy
      answer    → "I could not find that in the documentation. The passages I have
                   cover payment methods and duplicate charges [0][1], but none
                   mention student discounts."
      cost      → $0.0021
    TEXT,
    'notes' => [
        'Two guards because retrieval scores are fuzzy. A high score means "similar vocabulary", not "contains the answer".',
        'A system that says "I could not find that, here is the contact form" is trusted. One that guesses convincingly gets used once and then abandoned.',
        'Put both cases in your eval suite. Teams test that it answers well and forget to test that it declines well — module 8.',
    ],
    'live' => 'rag',
];
