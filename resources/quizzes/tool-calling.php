<?php

return [
    [
        'question' => 'When Claude "calls a tool", what has actually happened?',
        'options' => [
            'Anthropic executed your function on their servers',
            'The response came back with stopReason "tool_use" and a structured request; your code runs the function and returns the result',
            'The model queried your database over a secure tunnel',
            'The SDK ran the function automatically before returning',
        ],
        'answer' => 1,
        'explanation' => 'The model only ever emits a request. Your code decides whether to run it, runs it, and passes back the result. That gap is where all your control lives.',
    ],
    [
        'question' => 'Which tool signature is dangerous in a customer-facing assistant?',
        'options' => [
            'lookup_my_order(reference) with the customer taken from auth()',
            'find_orders_by_email(email) where the model supplies the email',
            'search_handbook(query) filtered to public articles',
            'request_refund(reference, amount) that creates a pending record for review',
        ],
        'answer' => 1,
        'explanation' => 'An identifier that controls authorisation must never be a model argument. Anyone who can influence the text can then read another customer\'s data. Identity comes from the session.',
    ],
    [
        'question' => 'Two tool_use blocks come back in one turn. How do you return the results?',
        'options' => [
            'One user message per result, sent in sequence',
            'Both tool_result blocks in a single user message, each with the matching toolUseID',
            'Merge them into one tool_result with a combined id',
            'Return only the first and ignore the second',
        ],
        'answer' => 1,
        'explanation' => 'All results from one turn go back in one user message, each matched by toolUseID. Splitting them across messages quietly trains the model to stop requesting parallel calls.',
    ],
    [
        'question' => 'The model never calls your tool, even when it obviously should. Most likely cause?',
        'options' => [
            'The model is too small',
            'The tool description is vague — it is the only thing the model knows about your function',
            'You need tool_choice set to "any"',
            'The input schema is missing a "required" array',
        ],
        'answer' => 1,
        'explanation' => 'The description is the entire interface. Say what it does, what it returns, and when to call it. Vague descriptions cause both under- and over-triggering.',
    ],
    [
        'question' => 'Why cap the agent loop at a fixed number of turns?',
        'options' => [
            'The API rejects more than five turns',
            'A confused model in an unbounded loop burns credit at machine speed',
            'Conversations become less accurate after five turns',
            'It is required by the SDK',
        ],
        'answer' => 1,
        'explanation' => 'Nothing stops a model from asking for tools indefinitely. A hard cap turns a runaway into a graceful failure instead of an invoice.',
    ],
];
