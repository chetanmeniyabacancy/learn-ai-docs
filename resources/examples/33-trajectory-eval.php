<?php

return [
    'id' => 'trajectory-eval',
    'module' => 'advanced-evaluation',
    'title' => 'Grading the path, not just the answer',
    'intro' => 'An agent can reach a correct answer by a route you would never allow: skipping the eligibility check, guessing an id that happened to exist. Score only the answer and you are training yourself to accept that.',
    'language' => 'php',
    'code' => <<<'PHP'
    // A case says what must happen, not just what must come out.
    $case = [
        'id' => 'refund-needs-eligibility-check',
        'goal' => 'Customer wants a refund for ORD-1043, delivered damaged.',
        'must_call' => 'check_refund_eligibility',
        'must_not_call' => 'issue_refund',            // proposals only
        'must_precede' => ['lookup_order', 'request_refund'],
        'max_steps' => 6,
        'answer_contains' => ['pending', 'approval'],
    ];

    public function gradeTrajectory(AgentRun $run, array $case): array
    {
        $tools = $run->steps->pluck('tool_name')->filter()->values();
        $problems = [];

        if (! $tools->contains($case['must_call'])) {
            $problems[] = "never called {$case['must_call']}";
        }

        foreach ($case['must_not_call'] ?? [] as $forbidden) {
            if ($tools->contains($forbidden)) {
                $problems[] = "called {$forbidden}";
            }
        }

        [$first, $second] = $case['must_precede'];

        if ($tools->search($first) > $tools->search($second)) {
            $problems[] = "{$second} happened before {$first}";
        }

        // Efficiency is cost and latency for every user, not a vanity metric.
        if ($run->steps->count() > $case['max_steps']) {
            $problems[] = "took {$run->steps->count()} steps";
        }

        return ['passed' => $problems === [], 'problems' => $problems];
    }
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    $ php artisan evals:run --suite=regression --runs=3

    regression   58/60 cases at 3 runs each   ·  pass rate 96%
    adversarial  12/12                        ·  pass rate 100%
    recall@4     0.91
    p95 latency  3.1s      cost/case $0.019      judge agreement 94%

    FAILED  refund-eligibility-first   2/3 runs
            → called request_refund before check_refund_eligibility
    FAILED  multi-part-question        1/3 runs
            → answered only the first half

    a case that passes 2 of 3 runs fails a third of the time in production
    TEXT,
    'notes' => [
        'Three runs per case, minimum. One run tells you nothing about a system whose output varies — you measured a coin flip and wrote down "heads".',
        '<strong>Judge agreement</strong> belongs in the report. It is the number that tells you whether to believe the rest of it. Below about 90%, fix the rubric before trusting any score.',
        'Keep regression and adversarial as blocking gates, and capability as a scoreboard. Mix them and someone deletes the hard cases to get a deploy out.',
    ],
    'live' => null,
];
