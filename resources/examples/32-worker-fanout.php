<?php

return [
    'id' => 'worker-fanout',
    'module' => 'multi-agent',
    'title' => 'Why a worker returns 200 words instead of 20 pages',
    'intro' => 'A worker\'s context is thrown away when it finishes — only its conclusion survives. That is the entire reason multi-agent works, and it is a context-window solution rather than an intelligence one.',
    'language' => 'php',
    'code' => <<<'PHP'
    // Independent sections, so run them at once.
    $findings = Concurrency::run(
        collect($sections)->map(fn ($section) => fn () => $this->worker($section))->all(),
    );

    private function worker(array $section): ?array
    {
        return $this->agent(
            goal: "Review the {$section['name']} section. List anything that "
                . "differs from our standard terms.",
            context: $section['text'],          // 20 pages go in here…
            tools: [$this->standardTermsLookup()],
            model: config('claude.fast_model'), // reading is cheap work
            maxSteps: 4,
            // …and a schema comes out. Prose from five workers is a parsing
            // problem you gave yourself.
            schema: ['type' => 'object', 'properties' => [
                'section' => ['type' => 'string'],
                'findings' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                    'clause' => ['type' => 'string'],
                    'concern' => ['type' => 'string'],
                    'severity' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                    // A quote makes the report checkable, and stops workers
                    // inventing clauses.
                    'quote' => ['type' => 'string'],
                ]]],
            ], 'required' => ['section', 'findings'], 'additionalProperties' => false],
        );
    }
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    a 60-page contract review

    one agent doing everything itself
      context by section 20    58,000 tokens of contract, instructions far behind
      model                    Sonnet throughout
      cost                     $0.41
      result                   missed a liability cap — summarising, not analysing

    orchestrator + 3 workers
      worker 1  payment terms      19,400 tokens in → 180 words out   Haiku
      worker 2  liability clauses  21,100 tokens in → 240 words out   Haiku
      worker 3  termination        17,800 tokens in → 150 words out   Haiku
      orchestrator                    570 words in → one report       Sonnet
      cost                         $0.07
      result                       found the liability cap

    cheaper AND better — each part doing work it is suited to
    TEXT,
    'notes' => [
        'The orchestrator never sees the contract. If it ends up reading everything anyway, you paid for workers and kept the original problem.',
        'A worker that fails must return null and be reported as an uncovered section. Silent partial coverage is the dangerous failure of this pattern.',
        'Give every worker the same severity rubric, word for word. Otherwise one calls a clause "high" and another calls the same thing "low".',
    ],
    'live' => null,
];
