<?php

return [
    'id' => 'rrf-fusion',
    'module' => 'advanced-rag',
    'title' => 'Merging keyword and vector results without comparing scores',
    'intro' => 'Keyword search returns BM25 scores. Vector search returns cosine similarities. The two scales have nothing to do with each other, so you cannot average them. Reciprocal rank fusion sidesteps the problem entirely by only looking at position.',
    'language' => 'php',
    'code' => <<<'PHP'
    function fuse(array $rankings, int $k = 60): array
    {
        $scores = [];

        foreach ($rankings as $ranking) {
            foreach (array_values($ranking) as $position => $id) {
                // Position 0 → 1/61, position 1 → 1/62. A small, smooth decay.
                $scores[$id] = ($scores[$id] ?? 0) + 1 / ($k + $position + 1);
            }
        }

        arsort($scores);

        return $scores;
    }

    // Same query, two very different result lists.
    $keyword = ['returns-policy', 'warranty-terms', 'shipping-times'];
    $vector  = ['refund-window', 'returns-policy', 'damaged-goods'];

    foreach (fuse([$keyword, $vector]) as $id => $score) {
        printf("%-16s %.5f\n", $id, $score);
    }
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    returns-policy   0.032266     ← found by BOTH methods, so it rises to the top
    refund-window    0.016393
    warranty-terms   0.016129
    damaged-goods    0.016000
    shipping-times   0.015873

    returns-policy was 1st for keyword and 2nd for vectors:
        1/(60+0+1) + 1/(60+1+1) = 0.016393 + 0.015873 = 0.032266
    TEXT,
    'notes' => [
        'Nothing here needs the two scoring scales to be comparable — that is the whole point. A document ranked highly by both methods wins, without you inventing a weighting.',
        'The <code>k = 60</code> constant comes from the original paper. It flattens the curve so being 1st instead of 3rd matters, but not overwhelmingly.',
        'This is normally step two of three: retrieve wide with both methods, fuse, then rerank the top 50 down to 4.',
    ],
    'live' => null,
];
