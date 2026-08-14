<?php

return [
    'id' => 'validate-after',
    'module' => 'structured-output',
    'title' => 'Two layers: schema, then validation',
    'intro' => 'The schema makes the response parseable. Laravel validation makes it believable. They do different jobs and you want both.',
    'language' => 'php',
    'code' => <<<'PHP'
    $data = $this->claude->extract(
        system: TriagePrompt::system(),
        input: $ticket->body,
        schema: TriagePrompt::schema(),
    );

    // The schema cannot express "under 200 characters" or "this order must exist".
    $validated = Validator::make($data, [
        'category' => ['required', Rule::enum(TicketCategory::class)],
        'priority' => ['required', Rule::enum(TicketPriority::class)],
        'summary' => ['required', 'string', 'max:200'],
        'order_reference' => [
            'nullable',
            'regex:/^ORD-\d+$/',
            Rule::exists('orders', 'reference'),   // it must be a REAL order
        ],
    ])->validate();

    Ticket::find($ticket->id)->update($validated);
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    Ticket 4471 → passed
      category=billing  priority=urgent  order_reference=ORD-1043

    Ticket 4472 → ValidationException
      summary: The summary field must not be greater than 200 characters.
      (model wrote a 260-character summary despite the description saying 140)

    Ticket 4473 → ValidationException
      order_reference: The selected order reference is invalid.
      (model returned ORD-1050, which does not exist in the orders table)
    TEXT,
    'notes' => [
        'Both failures are real things that happen. The schema guaranteed the <em>shape</em> — a string was present and it matched the pattern — but not that it was true.',
        'The <code>exists</code> rule is the important one. It is the difference between "the model said this order exists" and "this order exists".',
        'Catch the exception and route those tickets to a human queue rather than dropping them.',
    ],
    'live' => 'extract',
];
