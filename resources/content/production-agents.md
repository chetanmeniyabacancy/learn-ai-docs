## Summary

- An agent in production **spends money and changes data**. Treat it like a payment system, not a chat box.
- Give every run a limit on **steps, tokens, money and time** — checked *before* each step.
- Make every write **safe to run twice**, or a retry pays twice.
- A run that dies must be **resumable or safely abandoned** — never left half done.
- Anything you cannot undo needs **human approval**, and every action needs an **audit row**.
- Build a **kill switch** and test it before you need it.

## The problem

Your refund agent works well for three weeks. Then one Tuesday:

- A run loops on a slow tool for 40 minutes and costs $31 by itself
- A worker crashes after issuing a refund but before marking the ticket, so the nightly job issues it again
- A customer asks why they were paid twice, and nobody can reconstruct what happened

None of these is a prompt problem. They are all operations problems.

## Limits, checked before each step

```php
public function assertCanContinue(int $step): void
{
    if ($step > $this->maxSteps) {
        throw new BudgetExceeded('steps');
    }

    if ($this->run->cost >= $this->maxSpend) {
        throw new BudgetExceeded('spend');
    }

    if ($this->run->started_at->diffInSeconds(now()) > $this->maxSeconds) {
        throw new BudgetExceeded('time');
    }

    // The company-wide limit too. A bad day can be many well-behaved runs.
    if (AgentRun::whereDate('created_at', today())->sum('cost') > config('agents.daily_limit')) {
        throw new BudgetExceeded('daily');
    }
}
```

Hitting a limit is a **normal ending**, not a crash. The run stops, the user is told plainly, and a person can
pick it up. Silence is what turns this into an incident.

## Make writes safe to run twice

Retries, duplicate queue messages and resumed runs all cause the same action to be attempted again.

```php
public function issueRefund(int $orderId, int $amount, string $stepKey): array
{
    // Same run and step always produce the same key, even after a retry.
    $key = "refund:{$this->run->id}:{$stepKey}";

    return DB::transaction(function () use ($key, $orderId, $amount) {
        // A unique index on this column does the real work.
        if ($existing = AgentAction::where('idempotency_key', $key)->first()) {
            return ['already_done' => true, 'reference' => $existing->reference];
        }

        // Write what you are ABOUT to do, before doing it. A row saying
        // "about to refund" can be recovered. A refund with no row cannot.
        $action = AgentAction::create([
            'idempotency_key' => $key,
            'type' => 'refund',
            'payload' => ['order_id' => $orderId, 'amount' => $amount],
            'status' => 'pending_review',      // still not done — a human decides
        ]);

        return ['created' => true, 'reference' => $action->reference];
    });
}
```

Here is the failure this prevents:

```text
14:22:01  run 4471 step 6  issueRefund(ORD-1043, 2400)  → created REF-881
14:22:04  the worker crashed before marking the ticket
14:32:00  the orphan check finds run 4471 stuck in "running"

without a key   → resume runs step 6 again → SECOND refund of 2400
with a key      → "already_done, REF-881"  → nothing happens twice
```

## Resuming safely

Store the run as a state machine, so a crash is recoverable instead of a mystery.

```php
// A scheduled command finds runs that stopped without finishing.
AgentRun::where('status', 'running')
    ->where('updated_at', '<', now()->subMinutes(10))
    ->each(function (AgentRun $run) {
        // If an action was in flight, a HUMAN decides. The alternative is
        // charging someone twice to save a support ticket.
        $run->actions()->where('status', 'in_flight')->exists()
            ? $run->update(['status' => 'failed', 'stopped_because' => 'action in flight'])
            : ResumeAgentRun::dispatch($run);
    });
```

## Approval

Sort every action into three groups and treat them differently.

| Group | Examples | Control |
|---|---|---|
| **Undoable** | Add a note, add a tag | Log it, allow undo |
| **Hard to undo** | Send an email | Approval unless it is small and well logged |
| **Cannot undo** | Refund, delete, pay | Human approval, always |

```php
// The agent's most powerful verb should be "propose".
$this->propose('refund', [
    'amount' => min($requested, $order->total),   // a limit your code enforces
    'reasoning' => $draft['reasoning'],
    'evidence' => $facts,                          // so review takes seconds
]);
```

Auto-approving small amounts is a fine business decision — but write it as a rule in code, with a number and a
log line, not as something that emerges from a prompt.

## Audit

Assume you will one day explain one decision to a customer or a regulator.

```php
AgentAudit::create([
    'trace_id' => $run->trace_id,      // links to the spans from module 19
    'actor' => 'agent',                // agent | user | approver
    'action' => 'refund_proposed',
    'before' => $order->only(['status', 'refunded_amount']),
    'after' => ['status' => 'refund_pending'],
    'reasoning' => $reasoning,          // in the agent's own words
    'prompt_version' => AgentPrompt::VERSION,
]);
```

Without **before and after** you know something happened but not what changed. And if answering "why?" needs an
engineer, you will stop answering it.

## The kill switch

```php
// Checked before every step and every action.
if (Cache::get('agents:disabled')) {
    throw new AgentsDisabled('Agents are paused.');
}
```

Put it on an admin screen, make it work per customer as well as globally, and **test it during the day**. The
worst time to find out your kill switch needs a deploy is while it is costing money.

## Before you go live

- [ ] Limits on steps, money, tokens and time, checked before each step
- [ ] A company-wide daily limit as well as per-run limits
- [ ] Every write safe to run twice, with a unique key
- [ ] Runs stored so a crash is detected, and resume refuses mid-flight actions
- [ ] Anything you cannot undo needs approval, with the evidence shown
- [ ] Audit rows with before, after and reasoning
- [ ] A kill switch, tested
- [ ] Alerts on spend, loops and approval queue length
- [ ] Someone spent an afternoon trying to make it do the wrong thing

## Common mistakes

- **Checking the budget after the call.** You measured the overspend instead of stopping it.
- **Writes that are not safe to repeat.** The first double refund is a bug; the second is a pattern.
- **Resuming everything automatically.** Cheerful, and it charges customers twice.
- **Approval screens with no evidence.** People rubber-stamp, and approval becomes theatre.
- **No kill switch**, or one that needs a deploy.

## You should now be able to

- [ ] Enforce four kinds of limit before each step
- [ ] Make an agent write safe to run twice
- [ ] Store runs so a crash is recoverable, and know when not to resume
- [ ] Put actions you cannot undo behind approval
- [ ] Ship a kill switch and an audit trail

## Practice

1. List every write your agent can do. For each, write what happens if it runs twice.
2. Add a unique key to the riskiest one, then call it twice on purpose.
3. Kill a worker mid-run. Check the orphan is found and not blindly resumed.
4. Build the kill switch and use it during working hours, on purpose.

---

**That is Level 2.** Level 1 gave you features on top of a model. This level gave you systems that plan, act,
remember and keep working — and the judgement to know when a simple workflow beats an agent.
