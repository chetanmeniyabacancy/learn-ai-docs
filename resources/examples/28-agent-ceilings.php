<?php

return [
    'id' => 'agent-ceilings',
    'module' => 'ai-agents',
    'title' => 'The four ceilings every agent run needs',
    'intro' => 'An agent decides its own steps, so it can also decide to take fifty of them. Each ceiling below has stopped a real incident somewhere: a loop, a runaway context, a surprise invoice, and a request that never returns.',
    'language' => 'php',
    'code' => <<<'PHP'
    $maxSteps = 12;
    $maxSpend = 0.50;                    // dollars, this run
    $deadline = now()->addMinutes(2);
    $spent = 0.0;

    for ($step = 1; $step <= $maxSteps; $step++) {
        // Checked BEFORE the call. A budget checked afterwards measures the
        // damage instead of preventing it.
        if ($spent > $maxSpend) {
            return $this->stop($run, 'budget', "Spent \${$spent} of \${$maxSpend}.");
        }

        if (now()->gt($deadline)) {
            return $this->stop($run, 'timeout', 'Ran out of wall-clock time.');
        }

        $message = $client->messages->create(
            model: config('claude.model'),
            maxTokens: 4096,             // the third ceiling
            tools: $this->tools(),
            messages: $messages,
        );

        $spent += $this->costOf($message);

        // Log every step as it happens. A run that dies mid-way is exactly the
        // one you will need the trace for.
        $run->steps()->create(['step' => $step, 'cost' => $this->costOf($message)]);

        if ($message->stopReason !== 'tool_use') {
            return $this->finish($run, Claude::text($message), $spent);
        }

        // … run the tools, append the results, loop
    }

    return $this->stop($run, 'max_steps', 'Hit the step limit without finishing.');
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    ten runs of the same goal — this spread is what you actually ship

    run   steps   cost      outcome
      1     4     $0.021    finished
      2     4     $0.019    finished
      3     5     $0.026    finished
      4     4     $0.022    finished
      5     9     $0.058    finished — looped twice on a slow tool
      6     4     $0.020    finished
      7    12     $0.071    stopped: max_steps
      8     5     $0.027    finished
      9     4     $0.021    finished
     10     4     $0.020    finished

    pass rate 9/10 · cost $0.019 – $0.071 (3.7x) · p95 steps 12
    TEXT,
    'notes' => [
        'Judge an agent on ten runs, never one. The spread in steps and cost is the real behaviour; the worst run is what your support team hears about.',
        'Run 7 hitting <code>max_steps</code> is the ceiling doing its job. Without it, that run keeps going — and keeps billing.',
        'Note that the four ceilings are independent. A run can be cheap and still take too long, or fast and still spend too much.',
    ],
    'live' => null,
];
