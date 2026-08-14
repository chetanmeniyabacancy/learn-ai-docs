<?php

return [
    'id' => 'next-token-loop',
    'module' => 'transformers',
    'title' => 'Generating text, one token at a time',
    'intro' => 'The full generation loop. Predict a token, add it to the input, run again. That is every LLM response you have ever seen.',
    'language' => 'php',
    'code' => <<<'PHP'
    $tokens = tokenize("The refund window is");

    while (count($tokens) < $maxTokens) {
        // Run the whole model on everything so far
        $logits = $model->forward($tokens);       // one score per vocab token

        $probabilities = softmax($logits);
        $next = sample($probabilities);

        if ($next === STOP_TOKEN) {
            break;
        }

        $tokens[] = $next;                        // append and go again
    }

    return detokenize($tokens);
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    step  input so far                          picked      p
    ─────────────────────────────────────────────────────────
     1    "The refund window is"                 " 30"     0.44
     2    "The refund window is 30"              " days"   0.91
     3    "The refund window is 30 days"         " from"   0.38
     4    "The refund window is 30 days from"    " the"    0.52
     5    "…from the"                            " date"   0.61
     6    "…the date"                            " of"     0.73
     7    "…date of"                             " delivery" 0.55
     8    "…delivery"                            "."       0.68
     9    "…delivery."                           <STOP>    —

    9 full passes through the model to produce 8 words.
    TEXT,
    'notes' => [
        'The model runs completely, from scratch, for <em>every single token</em>. That is why output tokens cost more than input tokens.',
        'It is also why streaming works: token 1 exists long before token 40, so it can be sent immediately.',
        'Nothing here checks anything. Each step picks a likely next token. There is no point in the loop where a fact could be verified — which is hallucination, mechanically.',
    ],
    'live' => null,
];
