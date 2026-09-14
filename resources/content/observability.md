## Summary

- You cannot fix what you cannot see. Logging the final answer is not enough.
- Save one **trace** per request, with one **span** per model call, tool call and search.
- Save the **inputs and outputs** of each span — with personal data removed — or you cannot reproduce a failure.
- Link cost to a **feature, a user and a prompt version**, or you cannot answer finance.
- Watch four things: **quality, cost, speed, failures.** Alert on all four, not just errors.

## The problem

Tuesday afternoon. Support says the assistant got worse. You open your logs:

```text
[2026-08-18 14:22:01] production.INFO: Assistant answered
[2026-08-18 14:22:09] production.INFO: Assistant answered
```

You cannot see which prompt version ran, what was retrieved, which tools were called, what it cost, or where
the 8 seconds went. You cannot even prove anything changed. Three deploys went out on Monday.

## Traces and spans

A **trace** is one request from start to finish. A **span** is one piece of work inside it. Spans sit inside
each other.

```text
trace: conversation/8f2a1c            3.4s   $0.021   ok
  user: priya@company.com   prompt: assistant.v4

├── retrieve                          0.4s   $0.000   4 of 39 chunks, top 0.81
│   ├── embed the question            0.1s   $0.000   local model
│   └── score and filter              0.3s   $0.000   floor 0.60
├── model call 1                      1.2s   $0.008   wanted search_policies
├── tool search_policies              0.1s   $0.000   found 3 passages
├── model call 2                      1.6s   $0.013   finished normally
└── output check                      0.1s   $0.000   no personal data
```

That one picture answers the four questions you will actually have: what did it do, where did the time go,
where did the money go, and what did it see. A flat log answers none of them.

## What to save

```php
Schema::create('spans', function (Blueprint $table) {
    $table->uuid('trace_id')->index();            // one per request
    $table->foreignId('parent_id')->nullable();   // for the nesting
    $table->string('name');                       // 'model_call', 'tool.lookup_order'

    // Without these three you cannot explain a single cost number.
    $table->string('feature');
    $table->string('prompt_version')->nullable();
    $table->foreignId('user_id')->nullable();

    $table->string('model')->nullable();
    $table->unsignedInteger('input_tokens')->default(0);
    $table->unsignedInteger('output_tokens')->default(0);
    $table->unsignedInteger('cache_read_tokens')->default(0);
    $table->decimal('cost', 10, 6)->default(0);

    $table->unsignedInteger('duration_ms');
    $table->string('status');                     // ok | error | refusal | timeout
    $table->string('stop_reason')->nullable();

    $table->json('input')->nullable();            // personal data removed
    $table->json('output')->nullable();           // removed and shortened
});
```

Wrapping a call is boring, and that is good:

```php
public function span(string $name, callable $work): mixed
{
    $span = Span::start($name, $this->traceId, $this->currentSpanId);

    try {
        return $work($span);
    } catch (\Throwable $e) {
        $span->fail($e);

        throw $e;
    } finally {
        // Always. The spans you need most are the ones that failed.
        $span->finish();
    }
}
```

## Remove personal data before saving

Traces hold customer data by definition. So they carry the same duties as customer records.

```php
$text = preg_replace([
    '/\b[\w.+-]+@[\w-]+\.[\w.]+\b/',                  // email
    '/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/',   // card-like numbers
], '[removed]', $text);
```

Remove it **on the way in**, not when displaying. What you never stored cannot leak. Shorten long payloads, and
delete traces after 30 days unless you can justify longer.

## The four things to watch

| What | Alert when |
|---|---|
| **Quality** — judge pass rate, thumbs down | drops 5 points in a week |
| **Cost** — spend per day per feature | crosses a number you chose |
| **Speed** — p50 and p95 | p95 doubles |
| **Failures** — errors, refusals, cut-off answers, loops | any of them rise |

Most teams alert only on errors. But the failure that hurts is a loop that works perfectly and costs $900
overnight, or a prompt change that quietly lowers quality with no exception anywhere.

```php
// Two cheap alerts worth having on day one.
if (Span::whereDate('created_at', today())->sum('cost') > config('claude.daily_spend_limit')) {
    // Somebody should know before the invoice arrives.
}

// Nothing throws when maxTokens is hit — answers just stop mid-sentence.
if (Span::where('stop_reason', 'max_tokens')->whereDate('created_at', today())->count() > 20) {
    // Answers are being cut off and nobody has complained yet.
}
```

## Questions your data should answer

If any of these needs a spreadsheet, add the missing column today:

- Which feature is 80% of the bill?
- Did p95 change when we switched models on Monday?
- How many answers were cut off yesterday?
- Is prompt v4 better than v3 at the same cost?
- Which user is 30% of today's spend?
- Show me the full trace of the conversation this customer complained about.

That last one is the real test: **from a customer reference to a full trace, in one minute, without SQL.**

## Things change on their own

- **Your data changes** — new products and policies, so search quality slowly drops.
- **Your users change** — they learn what works and ask differently.
- **The model changes** — when you upgrade, run every test again before switching.
- **Your prompts change** — six small edits and nobody measured. Version them.

The defence is the same in all four cases: a chart, and a test suite you re-run.

## Common mistakes

- **Logging only the final answer.** Then you cannot reproduce a failure.
- **No prompt version.** Quality moved and you cannot say which change did it.
- **Cost as one number.** Useless. Break it down by feature, user and version.
- **Keeping raw prompts forever.** You created a privacy problem by accident.
- **Alerting only on exceptions.** The expensive failures do not throw.

## You should now be able to

- [ ] Design a trace with nested spans holding cost, time and status
- [ ] Remove personal data before writing, and set a deletion time
- [ ] Break spend down by feature, user and prompt version
- [ ] Alert on cost and quality, not only errors
- [ ] Go from a complaint to a full trace in a minute

## Practice

1. Add a `spans` table and wrap every model call, tool call and search. One day of work.
2. Build one page: spend per day per feature, p50 and p95, error rate.
3. Read the trace of yesterday's most expensive conversation. Something will surprise you.
4. Set a daily spend alert and a cut-off-answer alert. Test both with low limits.
