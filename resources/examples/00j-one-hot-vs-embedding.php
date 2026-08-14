<?php

return [
    'id' => 'one-hot-vs-embedding',
    'module' => 'text-to-numbers',
    'title' => 'One-hot vs embedding',
    'intro' => 'The first fix anyone tries, why it fails, and what replaced it. This is where meaning gets into the numbers.',
    'language' => 'php',
    'code' => <<<'PHP'
    // ❌ One-hot: a vector as long as the vocabulary
    function oneHot(int $tokenId, int $vocabSize = 50_000): array
    {
        $vector = array_fill(0, $vocabSize, 0);
        $vector[$tokenId] = 1;

        return $vector;      // 50,000 numbers, 49,999 of them zero
    }

    // ✅ Embedding: a few hundred LEARNED numbers
    //    (a table of vocab_size × 512, adjusted by gradient descent
    //     along with every other parameter in the model)
    $embedding = $embeddingTable[$tokenId];   // 512 numbers

    // Because they were learned under pressure to predict text well,
    // words used in similar contexts end up pointing the same way:
    printf("cat  vs dog      %.3f\n", cosineSimilarity($cat, $dog));
    printf("cat  vs invoice  %.3f\n", cosineSimilarity($cat, $invoice));
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    ── one-hot ──────────────────────────────────────────────
    "cat"     [0, 0, 0, …, 1, …, 0, 0]     50,000 numbers
    "dog"     [0, 0, 1, …, 0, …, 0, 0]     50,000 numbers

    distance("cat", "dog")        = same as
    distance("cat", "bureaucracy")

    Enormous, almost entirely zeros, and every word is equally
    unrelated to every other. All the meaning is gone.

    ── embedding ────────────────────────────────────────────
    "cat"     [ 0.21, -0.44,  0.88, …]     512 numbers
    "dog"     [ 0.19, -0.41,  0.85, …]     512 numbers
    "invoice" [-0.72,  0.13, -0.09, …]

    cat  vs dog      0.914
    cat  vs invoice  0.203

    And the geometry turns out to be meaningful:

      vector("king") - vector("man") + vector("woman") ≈ vector("queen")
      vector("Paris") - vector("France") + vector("Japan") ≈ vector("Tokyo")
    TEXT,
    'notes' => [
        'Nobody assigned those numbers. They are parameters, found by gradient descent under a single pressure: predict the next token well. The word arithmetic fell out as a side effect.',
        'This is the same object you use for search in Level 1 module 5 — same vectors, same cosine similarity. Here you are seeing where they come from.',
        'One limitation remains: a static embedding gives "bank" one vector forever, serving both the river and the mortgage. Attention (module F6) is what makes it context-dependent.',
    ],
    'live' => null,
];
