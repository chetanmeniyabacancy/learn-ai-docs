## Summary

- You need a test suite for output that is never exactly the same twice. That means fixed inputs, a grader, and a number at the end.
- There are three ways to grade, cheapest first: **plain assertions**, **property checks**, and **LLM-as-judge**.
- A **golden dataset** of 20 real inputs is worth more than 200 invented ones.
- For RAG, measure **recall@K separately** from answer quality. Otherwise you will improve the wrong half.
- Give every prompt a version number and log it, or the question "why did it get worse?" has no answer.

## The problem

Your assistant has been live for a month. Someone edits the system prompt to fix a formatting complaint. Two
weeks later, refund questions are being answered wrongly, and nobody knows when that started.

You cannot use `git bisect` here. There is no failing test to find. The output was never identical twice, so "it
used to work" is only a feeling.

## Tier 1: plain assertions

When the output is structured, this is just normal testing. Use it wherever you possibly can.

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

Classification, extraction, routing and tagging can all be asserted directly. So if your feature returns
structured output, most of your evals are simply tests.

## Tier 2: property checks on prose

You cannot assert the exact words of a paragraph. But you can assert facts about it:

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

That last check is a cheap hallucination detector. It gives the same result every time, costs nothing, and
catches a surprising number of problems.

## Tier 3: LLM-as-judge

Some qualities cannot be written as an assertion: is the answer faithful to the source, is it complete, is the
tone right. For those, make a second model call with a clear rubric and structured output.

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

A judge is cheap and can grade hundreds of cases. But it can also be confidently wrong, so **check 10% of its
verdicts by hand**. A judge you never audited is a number you have no reason to believe.

## The golden dataset

Everything above depends on this, and it is the step people skip.

**Twenty real examples beat two hundred invented ones.** Take them from your own data: the common questions, the
awkward ones, and the ones that caused complaints.

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

Every time something goes wrong in production, add one case. After three months you have a suite that remembers
every mistake your system has ever made.

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

Now a prompt change comes with a number attached. You can also see the trade-offs. Version 5 might score higher
but cost twice as much. That is a decision you can make, instead of a guess.

## RAG has its own metric

Grade the two halves separately, or you will spend your time improving the wrong one.

**Retrieval — recall@K:** is the passage that contains the answer inside the top K results?

```php
$recall = $cases->filter(function ($case) {
    $chunks = app(Retriever::class)->search($case['question'], limit: 4);
    return $chunks->pluck('chunk_id')->contains($case['expected_chunk_id']);
})->count() / $cases->count();
```

If recall@4 is 0.6, then 60% is your maximum possible score, and no prompt change can lift it. Fix retrieval
first. This single number saves more wasted effort than anything else in this course.

**Generation — faithfulness:** when the right passage *was* retrieved, was the answer correct and properly
grounded? This is where the judge earns its money.

## Where evals fit

- **Locally:** run 10 cases while you are working, for quick feedback.
- **In CI:** run the full suite on any pull request that touches prompts, schemas or retrieval. Fail the build
  when quality drops. Remember that this costs real money.
- **In production:** send 1% of live traffic through the judge and put it on a chart. Quality drifts as your
  data and your users change, and you cannot see drift without a chart.

> Give your prompts version numbers (`triage.v3`) and log the version with every call. Then when quality moves,
> answering "what changed?" is a `GROUP BY` query instead of somebody's memory.

## Common mistakes

- **No golden set.** "It seems better" is how prompts slowly rot.
- **Invented test cases.** They test what you imagined, not what your users actually send.
- **Only testing easy questions.** Include refusals, empty inputs, hostile inputs, and the longest input you
  have ever seen.
- **A judge nobody audits.** You have automated an opinion you never checked.
- **Grading RAG only end to end.** Then you cannot tell a retrieval failure from a generation failure.
- **Ignoring cost and latency.** Paying 4 times more for 3% better quality is usually a bad deal, but you can
  only know that if you measured both.

## You should now be able to

- [ ] Build a golden dataset from real production data
- [ ] Pick the cheapest grading tier that can answer your question
- [ ] Write an LLM judge with a rubric, and audit its verdicts
- [ ] Measure recall@K separately from answer quality
- [ ] Put a number on a prompt change before you ship it

## Practice

1. Collect 20 real questions from your inbox or your logs. Write down the expected outcome for each one.
2. Build the runner. Print the pass rate, the cost, and the p95 latency.
3. Break your prompt on purpose by deleting a rule, then run it again. If the number does not move, your test
   set is too easy.
4. From now on, add one case for every bug. That habit is the whole practice.
