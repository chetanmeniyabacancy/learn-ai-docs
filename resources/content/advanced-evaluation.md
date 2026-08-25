## Summary

- With agents, check the **path**, not only the answer. A right answer reached by luck will not repeat.
- Run every test case **several times**. One run of a system that varies tells you nothing.
- A **model as judge** can grade what code cannot — but check its verdicts by hand sometimes.
- Keep three separate sets: **regression** (must never break), **capability** (goals), **attacks**.
- Offline tests stop you shipping something worse. Only **live traffic** tells you if users are happier.

## The problem

Your test suite passes 38 out of 40. You ship it. A week later support says the assistant feels worse, and one
customer got a refund they were not owed.

You run the suite again. Still 38 out of 40. Nothing broke.

The suite was measuring the wrong things: only final answers, only questions you thought of, only once each,
with no view of **how** the answer was reached.

## Check the path

An agent can reach the right answer the wrong way: skipping the eligibility check, guessing an order number
that happened to exist. If you score only the answer, you are teaching yourself to accept that.

So a test case says what must happen:

```php
$case = [
    'goal' => 'Customer wants a refund for ORD-1043, delivered damaged.',
    'must_call' => 'check_refund_eligibility',
    'must_not_call' => 'issue_refund',              // proposals only
    'must_precede' => ['lookup_order', 'request_refund'],
    'max_steps' => 6,
];
```

And the check is ordinary code:

```php
$tools = $run->steps->pluck('tool_name')->filter()->values();
$problems = [];

if (! $tools->contains($case['must_call'])) {
    $problems[] = "never called {$case['must_call']}";
}

if ($tools->contains($case['must_not_call'])) {
    $problems[] = "called {$case['must_not_call']}";
}

// Order matters when one step must happen before another.
[$first, $second] = $case['must_precede'];

if ($tools->search($first) > $tools->search($second)) {
    $problems[] = "{$second} happened before {$first}";
}

// Extra steps are cost and waiting time for every user.
if ($run->steps->count() > $case['max_steps']) {
    $problems[] = "took {$run->steps->count()} steps";
}
```

## Run each case five times

The whole reason this module exists is that output varies. So measure the variation.

```text
case: refund needs eligibility check      5 runs

  passed         4 of 5      ← the number that matters
  steps          4, 4, 5, 4, 9
  cost           $0.021 – $0.058
  the failure    run 5 looped and hit the step limit
```

A case that passes 4 times in 5 **fails one time in five in production**. Writing "pass" would be lying to
yourself.

Set a bar per type of task. Reading a balance should pass 100% of the time. Drafting a reply may be fine at 90%.

## Judges you can trust

For things code cannot check — is it faithful, is it complete, is the tone right — use a second model call.
Three rules make it believable.

**1. Ask specific questions, not a score.** "Rate this 1 to 10" gives noise.

```php
$verdict = $this->claude->extract(
    system: <<<'TXT'
    You grade a support answer. Be strict.

    faithful: is every fact supported by the passages? An unsupported claim
              makes this false, even if it is probably true in general.
    complete: does it answer the whole question, including any second part?
    grounded: does it cite a passage number?
    TXT,
    input: "Passages:\n{$context}\n\nQuestion: {$question}\n\nAnswer:\n{$answer}",
    schema: $this->verdictSchema(),   // three booleans + one reason
);
```

**2. Check the judge.** Grade 20 cases yourself and compare. If it disagrees with you more than about 1 time in
10, fix the rules before believing any number it produced.

**3. Never let a model be the only judge** of anything that moves money.

## Three sets, three purposes

| Set | What is in it | The bar |
|---|---|---|
| **Regression** | Things that broke once | 100% — blocks the deploy |
| **Capability** | Hard cases you want to reach | Should improve over time |
| **Attacks** | Injection, scope probing | 100% — blocks the deploy |

Keep them apart. If they are one file, a hard capability case blocks a deploy, and someone deletes the hard
cases to ship.

## Watch live traffic too

Offline tests answer "did I make it worse?". They cannot answer "are users happier?".

- **Sample 1% of real traffic** through the judge and chart it daily.
- **Free signals**: did the customer ask the same thing again, or escalate to a human?
- **Thumbs down** — every one becomes a new regression case. This single habit is worth more than any tool.

## What a report looks like

```text
regression   58/60 cases, 3 runs each    pass rate 96%
attacks      12/12                       pass rate 100%
recall@4     0.91
p95 latency  3.1s    cost/case $0.019    judge agreement 94%

FAILED  refund-eligibility-first   2/3 runs — refunded before checking
FAILED  multi-part-question        1/3 runs — answered only half
```

Judge agreement belongs in that block. It tells you whether to believe the rest.

## Common mistakes

- **Grading only final answers** on an agent. You cannot see how it got there.
- **One run per case.** You measured a coin toss.
- **A judge nobody checked.** An automatic opinion you never verified.
- **One big suite.** A hard case blocks a deploy, so the hard cases get deleted.
- **No live signal.** Everything passes and users are unhappy — where this module started.

## You should now be able to

- [ ] Write a case with required, forbidden and ordered tool calls
- [ ] Report a pass rate over several runs
- [ ] Write a judge with clear rules and check it against yourself
- [ ] Keep regression, capability and attack sets apart
- [ ] Measure quality on live traffic

## Practice

1. Take your riskiest task. Write down what must be called and what must never be called.
2. Run your suite three times instead of once. The unstable cases are your real bugs.
3. Hand-grade 20 cases and compare with your judge. Fix the rules until it agrees 90% of the time.
4. Add a thumbs-down button. Turn the first five into regression cases.
