<?php

return [
    'id' => 'memory-window',
    'module' => 'agent-memory',
    'title' => 'A rolling window beats an ever-growing history',
    'intro' => 'Resending the whole conversation is not memory, it is an expensive prompt. Keep the recent turns verbatim, represent everything older with one summary, and refresh that summary as the conversation grows.',
    'language' => 'php',
    'code' => <<<'PHP'
    public function messagesFor(Conversation $conversation): array
    {
        $recent = $conversation->messages()->latest('id')->limit(10)->get()->sortBy('id');

        $messages = [];

        if ($conversation->summary) {
            $messages[] = [
                'role' => 'user',
                'content' => "Summary of the earlier part of this conversation:\n{$conversation->summary}",
            ];
        }

        foreach ($recent as $message) {
            $messages[] = ['role' => $message->role, 'content' => $message->content];
        }

        return $messages;
    }

    // The summariser asks for facts, not prose. A summary that reads nicely
    // but drops the order number is worse than useless.
    $conversation->update(['summary' => $this->claude->ask(
        system: 'Summarise this part of a support conversation for a colleague '
              . 'picking it up. Keep every reference number, date, amount, '
              . 'decision and commitment exactly as written. Drop greetings and '
              . 'small talk. Under 200 words.',
        prompt: $old->map(fn ($m) => "{$m->role}: {$m->content}")->implode("\n"),
        model: config('claude.fast_model'),
    )]);
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    a 60-turn support conversation, priced per reply on Sonnet

                              tokens sent   cost/reply   quality
    full history                  41,200      $0.1236    falling — answer buried
    last 10 turns only             3,100      $0.0093    good, but forgets the order no.
    summary + last 10 turns        3,600      $0.0108    good, and remembers ORD-1043

    92% cheaper than full history, and more accurate than either extreme.
    TEXT,
    'notes' => [
        'Both extremes are wrong. Forgetting everything makes the customer repeat themselves; remembering everything buries the answer in small talk and costs 12x more.',
        'Check your summaries for identifiers. If <code>ORD-1043</code> did not survive, the summary has removed the only fact that mattered.',
        'Some APIs now offer server-side compaction that summarises earlier context for you. Same trade-off: something was dropped, and you should decide what.',
    ],
    'live' => null,
];
