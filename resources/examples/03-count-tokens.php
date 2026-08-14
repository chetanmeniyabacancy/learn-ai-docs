<?php

return [
    'id' => 'count-tokens',
    'module' => 'llm-basics',
    'title' => 'Counting tokens before you send',
    'intro' => 'Tokens are your unit of cost and your unit of capacity. Guessing with a word count is fine for intuition and wrong when it matters.',
    'language' => 'php',
    'code' => <<<'PHP'
    $document = file_get_contents(storage_path('handbook.md'));  // ~4,200 words

    // ❌ The guess
    $guess = str_word_count($document) * 1.3;

    // ✅ The real number, from the same model you will actually call
    $count = $client->messages->countTokens(
        model: 'claude-sonnet-5',
        messages: [['role' => 'user', 'content' => $document]],
    );

    $tokens = $count->inputTokens;

    printf("guess:  %d tokens\n", $guess);
    printf("actual: %d tokens\n", $tokens);

    // Sonnet input is $3 per 1,000,000 tokens
    printf("cost per call: $%.4f\n", $tokens / 1_000_000 * 3);
    printf("cost for 5,000 calls: $%.2f\n", $tokens / 1_000_000 * 3 * 5000);
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    guess:  5460 tokens
    actual: 5912 tokens
    cost per call: $0.0177
    cost for 5,000 calls: $88.68
    TEXT,
    'notes' => [
        'The guess was 8% low here. On code or non-English text it can be 30–40% low, which turns a budget estimate into a surprise.',
        'Do not use OpenAI\'s <code>tiktoken</code> for Claude — different tokenizer, wrong numbers.',
        'That last line is the calculation that decides whether a feature ships. Do it before you write the feature, not after.',
    ],
    'live' => null,
];
