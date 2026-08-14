<?php

return [
    'id' => 'tokenization',
    'module' => 'text-to-numbers',
    'title' => 'Where your tokens actually go',
    'intro' => 'Subword tokenization, and why the same number of characters costs wildly different numbers of tokens depending on what they are.',
    'language' => 'php',
    'code' => <<<'PHP'
    $samples = [
        'where is my order',
        'unbelievable',
        'ORD-1043',
        '$user->getName();',
        'やあ、元気ですか',
    ];

    foreach ($samples as $text) {
        $count = $client->messages->countTokens(
            model: 'claude-sonnet-5',
            messages: [['role' => 'user', 'content' => $text]],
        );

        printf("%-22s %2d chars  %2d tokens\n",
            '"'.$text.'"', mb_strlen($text), $count->inputTokens);
    }
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    text                     chars  tokens   how it splits
    ─────────────────────────────────────────────────────────────────
    "where is my order"       17      4      ["where"," is"," my"," order"]
    "unbelievable"            12      4      ["un","bel","iev","able"]
    "ORD-1043"                 8      4      ["ORD","-","104","3"]
    "$user->getName();"       17      6      ["$","user","->","get","Name","();"]
    "やあ、元気ですか"           8      8      one token per character

    ─────────────────────────────────────────────────────────────────
    Same 17 characters, 4 tokens vs 6 tokens.
    Code and non-English text cost more for the same string length.

    Then each token becomes an integer:

      "where" → 3595     " is" → 374     " my" → 856     " order" → 2015
    TEXT,
    'notes' => [
        'Common words are one token; rare words split into familiar pieces. That is why a typo or a brand-new product name still works — nothing is unrepresentable.',
        'This is exactly why Level 1 tells you to measure token counts rather than multiply a word count. On code the guess can be 30–40% low.',
        '"How many r\'s in strawberry?" is hard because the model sees <code>["str","aw","berry"]</code>. The characters were consumed before it ever saw them.',
        'Token IDs are arbitrary — 3596 is not "one more than" 3595 in any meaningful sense. That is why the next step is embeddings.',
    ],
    'live' => null,
];
