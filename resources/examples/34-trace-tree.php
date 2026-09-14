<?php

return [
    'id' => 'trace-tree',
    'module' => 'observability',
    'title' => 'One trace answers four questions a log line cannot',
    'intro' => 'A flat "Assistant answered" log tells you nothing. A trace with nested spans tells you what it did, where the time went, where the money went, and what it saw — which are the only four questions you will actually have.',
    'language' => 'text',
    'code' => <<<'TEXT'
    trace: conversation/8f2a1c              3.4s    $0.021   ok
      user: priya@company.com   feature: assistant   prompt: assistant.v4

    ├── span: retrieve                       0.4s    $0.000   semantic
    │     query      "can I carry leave forward"
    │     result     4 of 39 chunks above floor 0.60, top score 0.81
    │   ├── span: embed query                0.1s    $0.000   nomic-embed-text (local)
    │   └── span: score + filter             0.3s    $0.000   visibility in [all]
    │
    ├── span: model call 1                   1.2s    $0.008   sonnet · tool_use
    │     in 2,140 tok   out 68 tok   cache read 1,890 tok
    │     wanted     search_policies({"query":"carry forward annual leave"})
    │
    ├── span: tool search_policies           0.1s    $0.000   found: true (3 passages)
    │
    ├── span: model call 2                   1.6s    $0.013   sonnet · end_turn
    │     in 3,020 tok   out 121 tok   cache read 1,890 tok
    │
    └── span: output check                   0.1s    $0.000   pii: none · numbers grounded
    TEXT,
    'output_language' => 'sql',
    'output' => <<<'SQL'
    -- Questions this makes answerable. If any needs a spreadsheet, you are
    -- missing a column.

    -- Which feature is 80% of the bill?
    select feature, sum(cost) from spans group by feature order by 2 desc;

    -- Did p95 move when we changed models on Monday?
    select date(created_at) d, model,
           max(duration_ms) p95_ish, avg(duration_ms) mean
    from spans where kind = 'model' group by d, model order by d desc;

    -- How many answers were silently truncated yesterday?
    select count(*) from spans
    where stop_reason = 'max_tokens' and date(created_at) = current_date - 1;

    -- Is prompt v4 better than v3 at the same cost?
    select prompt_version, count(*), avg(cost), avg(duration_ms)
    from spans where feature = 'assistant' group by prompt_version;
    SQL,
    'notes' => [
        'Note <code>prompt_version</code> on the trace. Without it, "why did quality drop?" is somebody\'s memory of last Tuesday rather than a GROUP BY.',
        'Redact on the way <em>in</em>, not when displaying. What is not stored cannot leak, and traces inherit your retention obligations the moment they exist.',
        'The practical test of good observability: from a customer complaint to that conversation\'s full trace in about a minute, without writing SQL.',
    ],
    'live' => null,
];
