## Summary

- At scale the model is the boring part. **Routing, caching, queues and fallbacks** decide your bill.
- **Send easy work away from the model.** Much of your traffic needs no model at all.
- Three caches: **exact match**, **similar question**, **prompt prefix**.
- **Queue** anything nobody is waiting for. Big batches can go at half price.
- Every AI feature needs a **fallback**, because the provider will be slow or down one day.

## The problem

Launch week, 10 users. Replies in 2 seconds, $12 for the month.

Six months later: 10,000 users. Replies take 9 seconds at busy times, one big customer is 60% of the bill, and
when the provider had a 20-minute problem your product simply stopped working.

Nothing in your code is wrong. Nothing in it was built for this either.

## Send easy work away from the model

This is the biggest saving, and it starts before any model is involved.

```php
public function handle(string $message, User $user): Response
{
    // Level 0 — no model. Free, instant, exact.
    if ($reference = $this->extractOrderReference($message)) {
        return $this->orderStatus($reference, $user);      // SQL
    }

    // Level 1 — somebody asked this 40 minutes ago.
    if ($cached = $this->cache->lookup($message, $user)) {
        return $cached;
    }

    // Level 2 — a cheap model works out the type. ~40 tokens out.
    $intent = $this->classify($message);                   // Haiku

    // Level 3 — a written answer with their data filled in. No generation.
    if ($template = $this->templateFor($intent)) {
        return $template->render($user);
    }

    // Level 4 — the good model, for questions that really need it.
    return $this->assistant($message, $user);              // Sonnet
}
```

On real traffic the split is usually in your favour:

```text
level 0  order lookups, links, statuses     31%   $0
level 1  cache hits                         18%   $0
level 2+3 typed, then templated             24%   $0.0002 each
level 4  genuinely open questions           27%   $0.0110 each

average per message   $0.0031      everything on level 4   $0.0110
```

That is 70% less, with no drop in quality, because the hard questions still reach the good model.

## Choose the model by the job

| The job | The model |
|---|---|
| Sorting, tagging, extracting, summarising | Haiku |
| Normal product features, RAG answers | Sonnet |
| Hard multi-step thinking, long agent runs | Opus |

Two rules. **Start on the good model and move down with evidence**, never the other way — you cannot notice the
quality you never measured. And **when a cheap model is unsure, retry on the better one**; that costs less than
being wrong.

## Three caches

**1. Exact match.** Simple, and it works better than people expect.

```php
$key = 'ai:'.sha1(
    $this->normalise($question)
    .'|'.$user->tenant_id             // without this you leak between customers
    .'|'.AssistantPrompt::VERSION     // without this your fix does nothing for 6 hours
);

return Cache::remember($key, now()->addHours(6), fn () => $this->answer($question, $user));
```

Those two extra fields are one-line mistakes with serious results. Keep them.

**2. Similar question.** "What is the return window?" and "how long do I have to send it back?" deserve the same
answer. Embed the question and reuse a previous answer **only if it is very close**:

```php
->filter(fn ($pair) => $pair[1] > 0.95)   // keep this bar high
```

Keep it strict. This is the one optimisation that can serve a confident **wrong** answer.

**3. Prompt prefix.** The provider caches the stable start of your prompt, at roughly a tenth of the price. Put
stable text first and changing text last — one changed byte before the cache point cancels everything after it.
If `cacheReadInputTokens` stays 0, something in your prefix is moving.

## Queues and batches

| Is someone waiting? | Do this |
|---|---|
| Yes | Stream it, and cache hard |
| No | Queue it, one job per item |
| No, and there are thousands | Batch API — about half price, hours not seconds |

Nightly re-tagging, embedding backfills and weekly summaries all belong off the live path. That also protects
your rate limit for requests that have a real person waiting.

## Plan for failure

```php
if (Cache::get('ai:circuit_open')) {
    return $this->fallback();     // search box, template, or a human
}

try {
    return $this->call($prompt);
} catch (InternalServerException|APITimeoutException $e) {
    $failures = Cache::increment('ai:failures');
    Cache::put('ai:failures', $failures, now()->addMinutes(5));

    if ($failures > 10) {
        // Open the circuit so 10,000 users do not each wait 30 seconds.
        Cache::put('ai:circuit_open', true, now()->addMinutes(2));
    }

    return $this->fallback();
}
```

Every AI feature needs an answer to "what does the user see when this is down?" — and it should be better than a
spinner.

## Many customers on one system

One customer's traffic must never become everyone's problem. So set limits **per customer**, not only per user;
report cost per customer, so pricing is based on something real; keep them apart everywhere (search, cache keys,
memory, logs); and add an off switch per customer for the 3am moment when one integration goes wrong.

## Where the money and time go

```text
cost                       time
input tokens   ~70%        generating the answer   ~75%
output tokens  ~25%        search + tools          ~20%
```

To cut **cost**, send fewer input tokens: 4 chunks not 10, trim history, cache prefixes. To cut **waiting**,
stream and ask for shorter answers. You cannot make generation fast, only shorter.

## Common mistakes

- **One model for everything.** Usually 2–3× the bill you need.
- **No cache, because "every question is different".** Measure it. They are not.
- **A loose similar-question cache.** Fast, cheap and wrong.
- **Cache keys without customer or prompt version.** Leaks, and fixes that seem not to work.
- **No fallback.** The provider has a bad hour and your product disappears.

## You should now be able to

- [ ] Send most traffic to levels that cost nothing
- [ ] Choose between the three caches and key them safely
- [ ] Decide what belongs on a queue or in a batch
- [ ] Add a circuit breaker and a real fallback
- [ ] Report cost per customer

## Practice

1. Take 200 real messages and sort them into the four levels by hand. That share is your saving.
2. Add exact-match caching with customer and prompt version in the key. Watch the hit rate for a week.
3. Move one background job off the live path.
4. Point your client at a dead URL and check the fallback works.
