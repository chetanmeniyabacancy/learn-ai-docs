## Summary

- An agent is the **same tool loop from Level 1**, with one change: the model decides the next step.
- You gain tasks you cannot plan in advance. You lose fixed cost, fixed speed and easy testing.
- Build one only when you **cannot write the steps down**.
- Every agent needs four limits: **steps, tokens, money, time**.
- Test an agent by running it **ten times**, not once.

## The problem

A customer writes:

> "I ordered two chairs three weeks ago. One came damaged, the other never arrived. I want the damaged one
> replaced and money back for the missing one. Also I have moved house."

Try writing the steps. You cannot. Which order comes first? Does the new address matter for the replacement?
Does the refund need the item marked lost first? Every step depends on what you find in the step before.

That is the real signal for an agent: **the steps are not knowable in advance.**

## What an agent is

Nothing new. It is the Level 1 loop with your plan removed.

```text
   goal + tools + what happened so far
                 ↓
      model decides: "call lookup_order(ORD-1043)"
                 ↓
           your code runs it
                 ↓
           result goes back
                 ↓
      model decides again  ← repeat until done or stopped
```

In Level 1 **you** wrote the order: search, then answer. Here the model picks the next step each turn. The code
is almost the same. The care around it is not.

## What it costs you

| | You control the steps | The model controls them |
|---|---|---|
| Cost per request | Known | A range, sometimes wide |
| Speed | 1–2 calls | 5–50 calls |
| Testing | Check the output | Check the behaviour, many times |
| When it fails | Loudly | Often quietly, and it sounds fine |

## Four questions before you build one

1. **Is it complex?** "Summarise this email" is not. "Find why this invoice does not match" is.
2. **Is it worth it?** Five to fifty model calls need to save real time.
3. **Can the model do the small steps?** If it cannot read your PDFs, an agent just fails expensively.
4. **What happens when it is wrong?** If a wrong step sends money, you need approval, not freedom.

If any answer is no, build a normal workflow instead (module 14).

> The most useful skill here is saying "this does not need an agent".

## The loop, with limits

```php
$maxSteps = 12;
$maxSpend = 0.50;                 // dollars for this run
$deadline = now()->addMinutes(2);
$spent = 0.0;

for ($step = 1; $step <= $maxSteps; $step++) {
    // Check BEFORE spending, not after.
    if ($spent > $maxSpend) {
        return $this->stop($run, 'budget');
    }

    if (now()->gt($deadline)) {
        return $this->stop($run, 'timeout');
    }

    $message = $client->messages->create(
        model: config('claude.model'),
        maxTokens: 4096,          // the third limit
        tools: $this->tools(),
        messages: $messages,
    );

    $spent += $this->costOf($message);

    // Save every step as it happens. A run that dies is the one you need to read.
    $run->steps()->create(['step' => $step, 'cost' => $this->costOf($message)]);

    if ($message->stopReason !== 'tool_use') {
        return $this->finish($run, Claude::text($message));
    }

    // …run the tools, send results back, loop again
}

return $this->stop($run, 'max_steps');
```

## Test it ten times

One good run tells you nothing. Run the same goal ten times and look at the spread:

```text
run   steps   cost      result
  1     4     $0.021    done
  5     9     $0.058    done, but looped twice
  7    12     $0.071    stopped at the step limit
 10     4     $0.020    done

9 of 10 finished · cost $0.019 to $0.071 (3.7× difference)
```

That spread is what your users will get. The worst run is what support will hear about.

## How agents fail

| What you see | What is happening | What to do |
|---|---|---|
| Same tool called again and again | It is stuck, not being careful | Return clearer errors; stop repeats in code |
| Stops with half an answer | It thinks it finished | Say in the prompt when the job is done |
| Invents an order number | Tool description too vague | Make the tool reject unknown ids |
| Forgets the goal | Context is full of tool output | Repeat the goal each turn; summarise old steps |

## Common mistakes

- **An agent for a two-step job.** A simple chain is cheaper and easier to test.
- **No limits.** A confused agent inside `while (true)` is a bill with no ceiling.
- **Judging it on the best run.** Judge it on the worst.
- **Twenty tools.** Accuracy drops as the tool list grows. Six to ten is plenty.
- **Saving only the final answer.** Then you cannot see why it failed.

## You should now be able to

- [ ] Explain an agent as a loop where the model picks the next step
- [ ] Reject agents that should be simple workflows
- [ ] Write the loop with four limits
- [ ] Name four ways agents fail, and the fix for each
- [ ] Judge an agent from ten runs

## Practice

1. Take a task you wanted an agent for. Try writing the steps. If you can, do not build an agent.
2. Build the loop above with one read-only tool. Read the step log.
3. Run the same goal ten times. Write down steps, cost and result each time.
4. Break a tool so it returns an error. Watch if the agent recovers or loops.
