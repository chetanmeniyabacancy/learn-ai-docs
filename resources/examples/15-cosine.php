<?php

return [
    'id' => 'cosine',
    'module' => 'embeddings',
    'title' => 'Cosine similarity in ten lines',
    'intro' => 'This is the entire mathematics of semantic search. Write it once and it stops being magic.',
    'language' => 'php',
    'code' => <<<'PHP'
    function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0.0; $magA = 0.0; $magB = 0.0;

        foreach ($a as $i => $value) {
            $dot  += $value * $b[$i];
            $magA += $value ** 2;
            $magB += $b[$i] ** 2;
        }

        return $dot / (sqrt($magA) * sqrt($magB));
    }

    $q  = $embedder->embed('How do I reset my password?');
    $a  = $embedder->embed('I forgot my login details');
    $b  = $embedder->embed('What are your delivery times?');
    $c  = $embedder->embed('Password reset instructions');

    printf("vs 'I forgot my login details'    %.3f\n", cosineSimilarity($q, $a));
    printf("vs 'Password reset instructions'  %.3f\n", cosineSimilarity($q, $c));
    printf("vs 'What are your delivery times' %.3f\n", cosineSimilarity($q, $b));
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    vs 'I forgot my login details'    0.842      ← no words in common
    vs 'Password reset instructions'  0.911
    vs 'What are your delivery times' 0.317

    the vectors themselves:
      [0.0231, -0.0512, 0.1140, 0.0074, ... ]   (1024 floats)
    TEXT,
    'notes' => [
        '0.842 between two sentences that share <em>zero</em> words. That is the whole reason embeddings exist.',
        'Rough intuition, though it varies by model: above 0.8 is clearly the same topic, 0.6–0.8 related, below 0.5 probably noise. Calibrate on your own data.',
        'Anthropic does not sell an embeddings endpoint — <code>$embedder</code> here is Voyage AI, OpenAI, Cohere or a local model. Claude generates text; something else vectorises it.',
    ],
    'live' => null,
];
