## Summary

- A workflow is when **you** write the steps and the model does the work inside them.
- Five shapes cover almost everything: **chain, route, parallel, check-and-retry, human approval.**
- If you can name the steps, use a workflow. It is cheaper, faster and easy to test.
- Each step gets its own model, prompt and test. That is why you can fix quality.
- One failed step retries by itself, instead of the whole job.

## The problem

Your refund agent works about 70% of the time. In a demo that looks good. With 10,000 tickets a month it means
**3,000 wrong results**, and some of them move money.

You read the logs and notice something. The agent almost always does the same five things in the same order. It
is not planning. It is finding the same sequence again every time, badly, and you are paying for it.

Write that sequence down and the problem changes completely.

## Workflow or agent?

| | Workflow | Agent |
|---|---|---|
| Who decides the order | You, in code | The model, while running |
| Can you name the steps? | Yes | No |
| Cost | Fixed | A range |
| A step gets worse | Fix that step | Read logs and guess |

**Most real AI systems are workflows.** Often the best design is a workflow with one agent step inside it.

## Shape 1: chain

Each step feeds the next. Use it when one prompt is doing too much.

```php
$triage = $this->classify($ticket->body);            // Haiku, cheap
$facts = $this->extract($ticket->body, $triage);     // only what this type needs
$draft = $this->draftReply($triage, $facts);         // good model, clean input
```

One 200-line prompt that classifies **and** extracts **and** writes is a prompt nobody can improve. Three small
steps can each be tested and priced.

## Shape 2: route

Decide the type first, then send it down the right path — including paths with no AI at all.

```php
$intent = $this->classifyIntent($message);   // Haiku, ~40 tokens out

return match ($intent) {
    'order_status' => $this->lookupOrder($message),     // SQL. Free and exact.
    'password_reset' => view('help.password-reset'),    // A page. Free.
    'policy_question' => $this->rag($message),
    default => $this->agent($message),                  // only the hard tail
};
```

This is where most cost savings come from. Usually half your traffic needs no model at all.

## Shape 3: parallel

Do independent work at the same time. Three calls together take as long as the slowest one, not the total.

```php
[$sentiment, $entities, $risks] = Concurrency::run([
    fn () => $this->sentiment($document),
    fn () => $this->entities($document),
    fn () => $this->riskFlags($document),
]);
```

## Shape 4: check and retry

One model writes, another checks. This fixes quality without a bigger model.

```php
$draft = $this->draft($ticket);

for ($attempt = 1; $attempt <= 2; $attempt++) {
    $review = $this->critique($draft, $ticket);   // returns ok + reasons

    if ($review['acceptable']) {
        break;
    }

    $draft = $this->redraft($ticket, $draft, $review['problems']);
}
```

Stop after two tries. A third rarely helps, and the loop can swing between two drafts forever.

## Shape 5: human approval

The most important shape here, and the least technical.

```php
// The workflow only makes a proposal.
RefundProposal::create([
    'amount' => min($suggested, $order->total),   // a limit your code enforces
    'reasoning' => $draft['reasoning'],
    'evidence' => $facts,                          // so review takes seconds
    'status' => 'pending_review',
]);
```

Two things decide whether people use the queue: **show the evidence**, so they can decide quickly, and make
approving **one click**. A queue that takes two minutes per item gets abandoned by Thursday.

## A real pipeline

```text
ticket
  ├─ route ─────► not a refund? different pipeline
  ├─ chain: classify → extract order → check eligibility (SQL!)
  ├─ parallel: draft reply · work out amount · fraud check
  ├─ check: does the draft quote the right policy? one retry
  └─ approve: small and clearly eligible → auto. Otherwise → human
```

Look at where the AI is **not**. Eligibility is SQL, because it is a rule. The amount is arithmetic. The model
classifies, extracts and writes — the three things it is best at.

## Failure stays local

```php
class DraftReply implements ShouldQueue
{
    public int $tries = 3;
    public array $backoff = [10, 60, 180];

    // This step failing retries this step only. The steps before it are not
    // run again, and not paid for again.
}
```

You also get free monitoring: the queue shows which step is slow and which one fails.

## Common mistakes

- **Choosing an agent because the word sounds better.** Read your logs first.
- **One giant prompt doing four jobs.** Impossible to improve or test.
- **Doing independent calls one after another.** Three 2-second calls in a row waste 4 seconds.
- **A retry loop with no limit.** Two tries, then a human.
- **Auto-approving money** because the confidence looked high. Confidence is not correctness.

## You should now be able to

- [ ] Choose workflow or agent by trying to write the steps down
- [ ] Name the five shapes and pick the right one
- [ ] Send cheap traffic away from the model completely
- [ ] Run independent steps together
- [ ] Build an approval screen people will actually use

## Practice

1. Read ten agent logs. Write down the order it usually follows. That is your workflow.
2. Add a routing step. Measure how much traffic now needs no model.
3. Split your longest prompt into two steps. Give the first one Haiku.
4. Turn your riskiest automatic action into a proposal with an approval screen.
