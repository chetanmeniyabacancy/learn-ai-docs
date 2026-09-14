## Summary

- One **manager** agent gives work to several **worker** agents, each with its own context and job.
- The real reason is **space, not intelligence**: a worker can read 20 pages and hand back 200 words.
- It also lets you use a cheap model for reading and a good model for deciding.
- The cost is more calls, more waiting, and much harder logs to read.
- **One good agent beats five badly organised ones.** Use this only when one context cannot hold the task.
- Workers must be independent. If worker B needs worker A's answer, that is a chain, not a fan-out.

## The problem

"Read this 60-page supplier contract and flag anything unusual."

One agent tries. It reads section 1, then 2, and by section 20 its context is a wall of contract text. The
instructions are 40,000 tokens behind it. It starts summarising instead of checking, misses a liability clause,
and gives a confident but thin answer.

The model is fine. The task needed **more reading than one context can hold and still think**.

## The shape

```text
                  ┌──────────────┐
                  │   manager    │  splits the work, joins the answers
                  └──────┬───────┘
          ┌──────────────┼──────────────┐
          ▼              ▼              ▼
     ┌─────────┐   ┌──────────┐   ┌───────────┐
     │ payment │   │liability │   │termination│   each: own context,
     │  terms  │   │ clauses  │   │  clauses  │   own job, one section
     └────┬────┘   └────┬─────┘   └─────┬─────┘
          └──────────────┼───────────────┘
                         ▼
                  ┌──────────────┐
                  │   manager    │  one report from three findings
                  └──────────────┘
```

Each worker reads 20 pages and returns 200 words. The manager sees 600 words, not 60 pages — so it can actually
think about them.

## Why it works

This is the key idea: **a worker's context is thrown away when it finishes.** Only its answer survives. So the
manager's context grows by a paragraph per worker, not by everything the worker had to read.

The second win is cost. Reading is cheap work; deciding is not.

```text
one agent doing everything
  60 pages on a good model, getting worse as it goes     ~$0.40, missed a clause

manager + 3 workers
  3 × 20 pages on a cheap model (reading)                ~$0.05
  1 × 600 words on a good model (deciding)               ~$0.02
                                                         ~$0.07, found the clause
```

Cheaper **and** better, because each part does work it suits. Measure it yourself, but this is common.

## Building it

```php
// Workers are independent, so run them together.
$findings = Concurrency::run(
    collect($sections)->map(fn ($s) => fn () => $this->worker($s))->all(),
);

private function worker(array $section): ?array
{
    return $this->agent(
        goal: "Review the {$section['name']} section. List anything that differs "
            . "from our standard terms.",
        context: $section['text'],           // 20 pages go in here…
        model: config('claude.fast_model'),  // reading is cheap work
        maxSteps: 4,
        // …and structured data comes out. Prose from five workers is a
        // parsing problem you gave yourself.
        schema: ['type' => 'object', 'properties' => [
            'findings' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                'clause' => ['type' => 'string'],
                'concern' => ['type' => 'string'],
                'severity' => ['type' => 'string', 'enum' => ['low', 'medium', 'high']],
                // A quote makes the report checkable and stops invented clauses.
                'quote' => ['type' => 'string'],
            ]]],
        ], 'required' => ['findings'], 'additionalProperties' => false],
    );
}
```

Three details that matter:

- **Ask for structured output**, not prose.
- **Require a quote** with every finding, so a human can check it.
- **A failed worker returns null**, and the report says that section was not checked. Silent missing coverage is
  the dangerous failure here.

## When it is the wrong answer

| Situation | Better answer |
|---|---|
| Step B needs step A's answer | A chain (module 14) |
| It all fits in one context | One agent. Fewer parts, readable logs |
| Same data, different questions | Parallel calls, not agents |
| You want a second opinion | Ask the same question twice |

> Five agents that disagree are worse than one agent that is sometimes wrong, because now you must decide who is
> right, and you have no way to.

## New problems it brings

- **Overlap** — two workers report the same clause. Give each a clear boundary.
- **Gaps** — nobody checked section 14. The manager must confirm everything came back.
- **Disagreement** — one calls a clause "high", another calls it "low". Give every worker the same rules, in the
  same words.
- **Cost surprises** — twenty workers is twenty runs. Put a limit on how many you start.
- **Logs nobody reads** — one job is now twenty transcripts. If your screen cannot show the tree, you cannot
  debug it.

## Common mistakes

- **Using this by default.** It solves a space problem. Check that you have one.
- **Letting workers chat freely.** Tokens burn and they drift. Everything goes through the manager.
- **The manager reading everything anyway.** Then you paid for workers and kept the problem.
- **The same expensive model everywhere.** Send the reading to a cheap model.
- **No limit on how many workers start.** A planner that wants 200 sections should be stopped by code.

## You should now be able to

- [ ] Explain this as a context problem, not an intelligence one
- [ ] Split independent work and join structured findings
- [ ] Send reading to a cheap model and deciding to a good one
- [ ] Spot overlap, gaps and disagreement between workers
- [ ] Say when a chain or one agent is better

## Practice

1. Find a task where your agent's context fills up with reading. Only that is a candidate.
2. Split it into three workers with clear boundaries and the same severity rules.
3. Compare one agent versus manager-plus-workers: quality, cost and time.
4. Make one worker fail. Check the report says that section was not covered.
