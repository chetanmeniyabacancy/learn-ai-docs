## Summary

- You need a test suite for output that is never identical twice: fixed inputs, a grader, a number.
- Three grading tiers, cheapest first: **assertions**, **property checks**, **LLM-as-judge**.
- A **golden dataset** of 20 real inputs beats 200 invented ones.
- For RAG, measure **recall@K separately** from answer quality, or you tune the wrong half.
- Version prompts and log the version, or "it got worse" is unanswerable.

## The problem

Your assistant has been live a month. Someone tweaks the system prompt to fix a formatting complaint. Two
weeks later refund questions are answered wrongly and nobody knows when it started.

You cannot `git bisect` this. There is no failing test. The output was never deterministic, so "it used to
work" is a feeling.

## Tier 1: plain assertions

For structured output this is ordinary testing. Use it wherever you can.

```php
it('classifies a duplicate-charge complaint as urgent billing', function () {
    $result = app(TicketTriage::class)->handle(
        Ticket::factory()->make(['body' => fixture('tickets/duplicate_charge.txt')])
    );

    expect($result->category)->toBe(TicketCategory::Billing)
        ->and($result->priority)->toBe(TicketPriority::Urgent)
        ->and($result->orderReference)->toBe('ORD-1043');
});
```

Classification, extraction, routing, tagging — all directly assertable. If your feature produces structured
output, most of your evals are just tests.

## Tier 2: property checks on prose

You cannot assert exact strings, but you can assert properties:

```php
expect($answer)
    ->not->toContain('As an AI')
    ->and(strlen($answer))->toBeLessThan(600)
    ->and($answer)->toMatch('/\[\d\]/');        // it cited a source

expect($answer)->toContain('30 days');          // the fact that must be right

// Grounding check: every number in the answer appeared in the context
foreach (extractNumbers($answer) as $number) {
    expect($context)->toContain($number);
}
```

That last one is a cheap, deterministic hallucination detector and it catches a lot.

## Tier 3: LLM-as-judge

For qualities no assertion can express — faithful, complete, right tone — use a second model call with a
rubric and structured output.

```php
$judgement = $this->claude->extract(
    system: <<<'TXT'
    You grade a support assistant's answers. Be strict and literal.

    faithful: is every factual claim supported by the context? An unsupported
              claim makes this false, even if probably true in general.
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
            'reason' => ['type' => 'string', 'description' => 'One sentence on the worst score.'],
        ],
        'required' => ['faithful', 'complete', 'grounded', 'reason'],
        'additionalProperties' => false,
    ],
);
```

A judge is cheap and scales to hundreds of cases. It can also be confidently wrong — so **spot-check 10% of
its verdicts by hand**. A judge you never audited is a metric you have no reason to believe.

## The golden dataset

Everything rests on this, and it is what people skip.

**Twenty real examples beat two hundred invented ones.** Pull them from your data: common cases, awkward ones,
the ones that caused complaints.

```php
// tests/Fixtures/evals/support-questions.php
return [
    [
        'id' => 'refund-window',
        'question' => 'How long do I have to return something?',
        'must_contain' => ['30 days'],
        'must_not_contain' => ['14 days', '60 days'],
        'should_cite' => true,
    ],
    [
        'id' => 'unknown-topic',
        'question' => "What is the CEO's home address?",
        'expect_refusal' => true,      // refusing IS the correct answer
    ],
];
```

Add one case every time something goes wrong in production. In three months you have a suite encoding every
mistake your system ever made.

## Running it

```php
class EvalRunner
{
    public function run(string $promptVersion): EvalReport
    {
        $cases = require base_path('tests/Fixtures/evals/support-questions.php');
        $results = [];

        foreach ($cases as $case) {
            $answer = app(KnowledgeBase::class)->answer($case['question'], $this->evalUser());
            $results[] = $this->grade($case, $answer);
        }

        return new EvalReport(
            promptVersion: $promptVersion,
            passed: collect($results)->where('passed')->count(),
            total: count($results),
            cost: collect($results)->sum('cost'),
            p95Latency: percentile(collect($results)->pluck('ms'), 95),
        );
    }
}
```

```text
$ php artisan evals:run --prompt=rag.v4

rag.v4   38/40 passed (95%)   $0.41   p95 2.4s

FAILED refund-window            answer said "about a month", expected "30 days"
FAILED international-shipping   cited [1], correct passage was [3]
```

Now a prompt change has a number. You can also see trade-offs: v5 might score higher but cost twice as much.
That is a decision, not a guess.

## RAG has its own metric

Grade the two halves separately or you will tune the wrong one.

**Retrieval — recall@K:** is the passage containing the answer in the top K?

```php
$recall = $cases->filter(function ($case) {
    $chunks = app(Retriever::class)->search($case['question'], limit: 4);
    return $chunks->pluck('chunk_id')->contains($case['expected_chunk_id']);
})->count() / $cases->count();
```

If recall@4 is 0.6, your ceiling is 60% and no prompt will raise it. Fix retrieval first. This one number
redirects more wasted effort than anything else in the course.

**Generation — faithfulness:** given the right passage *was* retrieved, was the answer correct and grounded?
That is where the judge earns its keep.

## Where evals fit

- **Local:** 10 cases while iterating. Fast feedback.
- **CI:** the full suite on any PR touching prompts, schemas or retrieval. Fail the build on a regression.
  Remember it costs real money.
- **Production:** sample 1% of live traffic through the judge and chart it. Quality drifts as your data and
  users change, and drift is invisible without a chart.

> Version your prompts (`triage.v3`) and log the version with every call. When quality moves, "what changed?"
> should be a `GROUP BY`, not someone's memory.

## Common mistakes

- **No golden set.** "It seems better" is how prompts rot.
- **Invented test cases.** They test what you imagined, not what users send.
- **Only happy paths.** Include refusals, empty inputs, hostile inputs, the longest input you have seen.
- **Judge with no audit.** You automated an opinion you never checked.
- **Grading RAG end-to-end only.** You cannot tell retrieval failures from generation failures.
- **Ignoring cost and latency.** 3% quality for 4× the price is usually bad — but only if you measured both.

## You should now be able to

- [ ] Build a golden dataset from real production data
- [ ] Choose the cheapest grading tier that answers the question
- [ ] Write an LLM judge with a rubric — and audit it
- [ ] Measure recall@K separately from answer quality
- [ ] Attach a number to a prompt change before shipping

## Practice

1. Collect 20 real questions from your inbox or logs. Write the expected outcome for each.
2. Build the runner. Print pass rate, cost and p95 latency.
3. Break your prompt on purpose — delete a rule — and re-run. If the number does not move, your set is too
   easy.
4. Add one case for every bug from now on. That habit is the whole practice.
