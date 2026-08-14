<?php

return [
    'id' => 'tool-loop',
    'module' => 'tool-calling',
    'title' => 'The agent loop, message by message',
    'intro' => 'What actually goes over the wire when "Claude calls a tool". Two API calls, one query, and your code in the middle the whole time.',
    'language' => 'php',
    'code' => <<<'PHP'
    $messages = [['role' => 'user', 'content' => 'Where is my order ORD-1043?']];

    for ($turn = 0; $turn < 5; $turn++) {              // always cap the loop
        $message = $client->messages->create(
            model: 'claude-sonnet-5', maxTokens: 2048,
            system: $this->systemPrompt(), tools: $this->tools(), messages: $messages,
        );

        if ($message->stopReason !== 'tool_use') {
            return Claude::text($message);            // final answer
        }

        // Echo the assistant turn back VERBATIM — it carries the tool_use blocks
        $messages[] = ['role' => 'assistant', 'content' => $message->content];

        $results = [];
        foreach (Claude::toolCalls($message) as $call) {
            $results[] = [
                'type' => 'tool_result',
                'toolUseID' => $call->id,             // must match the request
                'content' => json_encode($this->run($call->name, $call->input)),
            ];
        }

        // ALL results for a turn go back in ONE user message
        $messages[] = ['role' => 'user', 'content' => $results];
    }
    PHP,
    'output_language' => 'json',
    'output' => <<<'JSON'
    // ── API call 1 ─────────────────────────────────────────
    // response.stopReason = "tool_use"
    {
        "type": "tool_use",
        "id": "toolu_01A8vN…",
        "name": "lookup_my_order",
        "input": { "reference": "ORD-1043" }
    }

    // ── your code runs the query, and sends back ───────────
    {
        "type": "tool_result",
        "tool_use_id": "toolu_01A8vN…",
        "content": "{\"found\":true,\"status\":\"shipped\",\"expected_on\":\"2026-08-14\",\"carrier\":\"DHL Express\"}"
    }

    // ── API call 2 ─────────────────────────────────────────
    // response.stopReason = "end_turn"
    "Your order ORD-1043 has shipped and DHL Express expects to deliver it
     on 14 August."
    JSON,
    'notes' => [
        'The model never saw SQL, a connection string, or any row it was not handed. It emitted a function name and an argument; your code did everything else.',
        'Four rules, and breaking any one gives a confusing 400: echo the assistant turn unchanged, one <code>tool_result</code> per <code>tool_use</code> with the matching id, all results in a single user message, and cap the loop.',
        'Two API calls means you pay for the conversation twice over — the second call re-sends everything. That is normal, and it is why tool results should be small.',
    ],
    'live' => 'tools',
];
