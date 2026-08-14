<?php

return [
    'id' => 'llm-judge',
    'module' => 'evaluation',
    'title' => 'LLM-as-judge for what assertions cannot express',
    'intro' => 'Faithfulness, completeness, tone — no assertion catches these. A second model call with a rubric and a schema does, cheaply and at scale.',
    'language' => 'php',
    'code' => <<<'PHP'
    $judgement = $this->claude->extract(
        system: <<<'TXT'
        You grade a support assistant's answers. Be strict and literal.

        faithful: is every factual claim supported by the context? An unsupported
                  claim makes this false, even if it is probably true in general.
        complete: does it answer the whole question?
        grounded: does it cite passage numbers?
        TXT,
        input: "Context:\n{$context}\n\nQuestion: {$question}\n\nAnswer:\n{$answer}",
        schema: [
            'type' => 'object',
            'properties' => [
                'faithful' => ['type' => 'boolean'],
                'complete' => ['type' => 'boolean'],
                'grounded' => ['type' => 'boolean'],
                'reason' => ['type' => 'string',
                    'description' => 'One sentence explaining the worst score above.'],
            ],
            'required' => ['faithful', 'complete', 'grounded', 'reason'],
            'additionalProperties' => false,
        ],
    );
    PHP,
    'output_language' => 'json',
    'output' => <<<'JSON'
    // Answer: "You have 30 days to return items, and we'll usually
    //          refund you within 3-5 working days."
    {
        "faithful": false,
        "complete": true,
        "grounded": false,
        "reason": "The context says card refunds take 5 to 10 working days; the answer says 3-5, and cites no passage."
    }
    JSON,
    'notes' => [
        'That is a subtle, plausible, wrong answer — exactly the kind that slips past a human skim-reading 40 outputs.',
        'A judge is cheap and scales to hundreds of cases. It is also capable of being confidently wrong, so <strong>spot-check 10% of its verdicts by hand</strong>. A judge you have never audited is a metric with no reason to believe it.',
        'Use the cheapest grading tier that answers the question: plain assertions for structured output, property checks for prose, a judge only for genuinely subjective qualities.',
    ],
    'live' => null,
];
