<?php

return [
    'id' => 'stop-reason',
    'module' => 'llm-basics',
    'title' => 'Truncation is not an error',
    'intro' => 'Set maxTokens too low and the answer stops mid-sentence. Nothing throws. Nothing logs. You just quietly ship half an answer.',
    'language' => 'php',
    'code' => <<<'PHP'
    $message = $client->messages->create(
        model: 'claude-sonnet-5',
        maxTokens: 30,                       // deliberately far too small
        messages: [['role' => 'user', 'content' => 'Explain how database indexes work.']],
    );

    echo Claude::text($message)."\n\n";
    echo "stopReason: {$message->stopReason}\n";

    // The check that belongs in every wrapper you write
    if ($message->stopReason === 'max_tokens') {
        Log::warning('Answer truncated', [
            'id' => $message->id,
            'output_tokens' => $message->usage->outputTokens,
        ]);
    }
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    A database index is a separate data structure that stores a sorted copy of one
    or more columns, allowing the database to find rows without scanning the

    stopReason: max_tokens

    [warning] Answer truncated {"id":"msg_01Wk...","output_tokens":30}
    TEXT,
    'notes' => [
        'HTTP 200. No exception. The only signal is <code>stopReason</code>.',
        'This is worst with structured output: truncated prose is just shorter, but truncated JSON will not parse at all.',
        'The other stop reason to handle is <code>refusal</code> — also a 200, with empty content. Code that reads <code>content[0]</code> renders a blank answer.',
    ],
    'live' => 'chat',
];
