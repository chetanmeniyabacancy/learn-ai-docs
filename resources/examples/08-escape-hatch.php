<?php

return [
    'id' => 'escape-hatch',
    'module' => 'prompt-engineering',
    'title' => 'Give it permission to fail',
    'intro' => 'Models are trained to be helpful, and helpfulness looks like an answer. If you never authorise "I don\'t know", you have implicitly required a guess.',
    'language' => 'php',
    'code' => <<<'PHP'
    $ticket = "hi, it's not working again. can you sort it? thanks";

    // ❌ No way out — every ticket must get a category
    $a = 'Classify this ticket as: billing, shipping, technical, or account.';

    // ✅ An explicit exit
    $b = <<<'TXT'
    Classify this ticket as: billing, shipping, technical, or account.

    If the ticket does not clearly state which, reply exactly: UNCLEAR
    Do not infer a category from tone, from the sender's name, or from
    what is statistically most common.
    TXT;

    echo "without escape hatch: ".$claude->ask($a, $ticket)."\n";
    echo "with escape hatch:    ".$claude->ask($b, $ticket)."\n";
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    without escape hatch: technical
    with escape hatch:    UNCLEAR
    TEXT,
    'notes' => [
        '"technical" is a plausible guess. It is also a coin flip that will route this ticket to the wrong team roughly half the time.',
        'One sentence removed the guess. This is the highest-value line in most prompts, and the cheapest to add.',
        'The same idea reappears in RAG (module 7): "if the context does not contain the answer, say so" is the single instruction that makes a document assistant trustworthy.',
    ],
    'live' => 'chat',
];
