<?php

return [
    'id' => 'stateless',
    'module' => 'orientation',
    'title' => 'The API remembers nothing',
    'intro' => 'Two calls in a row. The second one has no idea the first happened — unless you resend the history yourself. This is what "stateless" actually means for your code.',
    'language' => 'php',
    'code' => <<<'PHP'
    // ❌ Assuming the API remembers
    $client->messages->create(
        model: 'claude-sonnet-5', maxTokens: 100,
        messages: [['role' => 'user', 'content' => 'My name is Chetan.']],
    );

    $second = $client->messages->create(
        model: 'claude-sonnet-5', maxTokens: 100,
        messages: [['role' => 'user', 'content' => 'What is my name?']],
    );
    // → "I don't have access to your name."


    // ✅ Resending the history. THIS is what "memory" is.
    $history = [
        ['role' => 'user',      'content' => 'My name is Chetan.'],
        ['role' => 'assistant', 'content' => 'Nice to meet you, Chetan!'],
        ['role' => 'user',      'content' => 'What is my name?'],
    ];

    $third = $client->messages->create(
        model: 'claude-sonnet-5', maxTokens: 100, messages: $history,
    );
    // → "Your name is Chetan."
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    Call 2 (no history):
      "I don't have access to your name — you haven't told me in this conversation."

    Call 3 (history resent):
      "Your name is Chetan."

    Note the token counts:
      call 2 → input 12 tokens
      call 3 → input 34 tokens     ← you pay for the history, every single turn
    TEXT,
    'notes' => [
        'A chatbot\'s "memory" is a <code>messages</code> table plus a loop. There is no session on Anthropic\'s side.',
        'Because history is resent every turn, a long conversation gets more expensive with each message. Trimming or summarising old turns is a real production concern — module 10.',
        'This is also why you can edit history: replay a conversation with a message removed and the model has genuinely never seen it.',
    ],
    'live' => null,
];
