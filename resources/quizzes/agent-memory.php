<?php

return [
    [
        'question' => 'Which is NOT one of the four kinds of memory in this module?',
        'options' => [
            'Working memory — this conversation',
            'Episodic memory — what happened in past sessions',
            'Parametric memory — facts stored in the model weights by your prompts',
            'Semantic memory — durable facts about the user',
        ],
        'answer' => 2,
        'explanation' => 'Nothing you send changes the weights. The four kinds you build are working, episodic, semantic and procedural — and each one is retrieval and storage that you own.',
    ],
    [
        'question' => 'A conversation has grown to 40,000 tokens and quality has fallen. Why?',
        'options' => [
            'The model has a bug with long inputs',
            'The answer is buried in noise — precision drops when relevant content is a small share of a large context, and every reply now costs far more',
            'The context window has been exceeded',
            'Tokens degrade the longer they are stored',
        ],
        'answer' => 1,
        'explanation' => 'Remembering everything is the other failure mode. The fix is a rolling window of recent turns plus a refreshed summary of what came before — and deliberate forgetting of small talk and superseded facts.',
    ],
    [
        'question' => 'What must a conversation summary preserve above all else?',
        'options' => [
            'The friendly tone of the exchange',
            'Every reference number, date, amount, decision and commitment, exactly as written',
            'The exact wording of the customer\'s questions',
            'The order in which topics were discussed',
        ],
        'answer' => 1,
        'explanation' => 'A summary that reads beautifully but drops the order number is worse than useless — it looks like context while having removed the only facts that mattered.',
    ],
    [
        'question' => 'Why insist on a closed set of keys for semantic memory?',
        'options' => [
            'To reduce storage costs',
            'Free-form memory becomes a junk drawer within a month: unqueryable, untrustworthy, and impossible to delete on request',
            'Because embeddings need fixed keys',
            'To make the prompt shorter',
        ],
        'answer' => 1,
        'explanation' => 'A defined schema is what makes memory something you can query, expire, show to the user and delete. Facts should also record when they were learned, so old ones decay rather than harden into wrong answers.',
    ],
    [
        'question' => 'You store conversation summaries and user facts. What is now required?',
        'options' => [
            'A larger database server',
            'A retention policy, a working delete path, per-user scoping in every query, and ideally a way for people to see what is remembered',
            'Nothing extra — summaries are not personal data',
            'Encryption of the summaries only',
        ],
        'answer' => 1,
        'explanation' => 'Everything you remember about a person is personal data by definition. Memory retrieval needs the same "where user_id" discipline as documents, and "forget everything about me" must be a method you have actually tested.',
    ],
];
