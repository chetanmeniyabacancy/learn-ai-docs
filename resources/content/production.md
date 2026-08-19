## Summary

- Treat the model as an external API that is slow, rate-limited, and billed per request. Everything else in this module follows from that.
- If a user is waiting, **stream**. If nobody is waiting, **queue**. Create one job per item, never one job per batch.
- Retry on 429, on 5xx, and on timeouts. **Never retry a 400.**
- **Prompt caching** cuts the cost of a repeated opening section to about 10%, but the stable part must come first.
- Log every call: feature, prompt version, model, tokens, latency, cost and stop reason.
- Always have a fallback that does not use AI.

## The problem

The demo went perfectly. Then you launched.

- Users watch a spinner for eight seconds and decide the page is broken.
- A queue worker times out and retries the *whole* batch.
- A 429 arrives during a busy hour and the raw error reaches customers.
- Finance asks what the $2,400 line is, and you cannot break it down.
- Someone says "it feels worse this week" and you have no way to check.

None of this is really about AI. It is normal integration work with a dependency that happens to be slow,
expensive and talkative.

## Latency: stream or queue

The model produces tokens one at a time. A 500-word answer takes several seconds, and you cannot make that
much faster.

**If a user is waiting, stream the answer.** The total time is the same, but the first words appear in under a
second, so the page feels alive.

```php
Route::get('/assistant/stream', function (Request $request) {
    return response()->stream(function () use ($request) {
        $stream = app(Client::class)->messages->createStream(
            model: config('claude.model'),
            maxTokens: 2048,
            system: AssistantPrompt::system(),
            messages: $request->session()->get('conversation', []),
        );

        foreach ($stream as $event) {
            if ($event->type === 'content_block_delta' && ($event->delta->type ?? null) === 'text_delta') {
                echo 'data: '.json_encode(['text' => $event->delta->text])."\n\n";
                ob_flush();
                flush();
            }
        }

        echo "data: [DONE]\n\n";
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'X-Accel-Buffering' => 'no',   // without this nginx buffers everything
    ]);
});
```

`X-Accel-Buffering` is the header that costs people an afternoon. Without it, nginx holds the whole stream and
delivers it in one go, which looks exactly like streaming being broken.

**If nobody is waiting, use a queue.** That covers batch classification, indexing and overnight summaries.

```php
class ClassifyTicket implements ShouldQueue
{
    public int $tries = 3;
    public int $timeout = 120;          // model calls are slow; default 60 is tight
    public array $backoff = [10, 60, 180];

    public function handle(TicketTriage $triage): void
    {
        $triage->handle($this->ticket);
    }
}
```

Make one job per item, not one job per batch. Then a failure retries one ticket, instead of charging you again
for four hundred.

## Failure: retry the right things

| Status | Meaning | Do |
|---|---|---|
| 429 | Rate limited | Retry with backoff, honour `retry-after` |
| 500 / 529 | Server error | Retry with backoff |
| 400 | Bad request | **Do not retry** — fix the payload |
| 401 | Bad key | **Do not retry** — page someone |
| Timeout | Slow generation | Retry, or lower `maxTokens` |

The SDK already retries connection errors, 429s and 5xx errors a couple of times. On top of that, add a circuit
breaker. Then an outage at the provider makes your app degrade politely instead of leaving every worker
hanging:

```php
public function ask(string $prompt): string
{
    if (Cache::get('claude:circuit_open')) {
        throw new AiUnavailable('AI features are temporarily unavailable.');
    }

    try {
        return $this->call($prompt);
    } catch (InternalServerException|APITimeoutException $e) {
        $failures = Cache::increment('claude:failures');
        Cache::put('claude:failures', $failures, now()->addMinutes(5));

        if ($failures > 10) {
            Cache::put('claude:circuit_open', true, now()->addMinutes(2));
        }

        throw $e;
    }
}
```

And always keep a non-AI fallback. If the assistant is down, show the search box and the contact form.

## Cost: four levers

**1. Prompt caching.** If the same long opening section repeats on every call — a big system prompt, a fixed
document, a set of examples — mark it as cacheable. Next time you pay about one tenth for those tokens.

```php
$message = $client->messages->create(
    model: config('claude.model'),
    maxTokens: 1024,
    system: [
        ['type' => 'text', 'text' => $longStableSystemPrompt, 'cacheControl' => ['type' => 'ephemeral']],
    ],
    messages: [['role' => 'user', 'content' => $question]],
);

Log::info('cache', [
    'read' => $message->usage->cacheReadInputTokens,       // want this high
    'written' => $message->usage->cacheCreationInputTokens,
]);
```

**Caching works by matching the beginning of the prompt.** If one single byte before the cache point changes,
everything after it is invalid. So put stable content first and changing content last. Putting `now()` or the
user's name at the top of your system prompt quietly turns caching off on every request, and the only symptom
you will notice is a bigger bill.

**2. Use the right size of model.** Haiku for classification, Sonnet for the assistant, Opus for the rare hard
case. This mix often halves a bill with no drop in quality.

**3. Send fewer tokens.** Retrieve 4 chunks instead of 10. Trim the conversation history. Summarise old
context. Input tokens are the largest part of most bills.

**4. Cache identical requests.**

```php
return Cache::remember('triage:'.sha1($text.TriagePrompt::VERSION), now()->addDay(),
    fn () => $this->triage($text));
```

Put the prompt version in the cache key. Otherwise your prompt fix does nothing for a whole day.

## Observability

If you build only one thing from this module, build this table.

```php
Schema::create('ai_calls', function (Blueprint $table) {
    $table->id();
    $table->string('feature');            // 'triage', 'assistant'
    $table->string('prompt_version');     // 'triage.v3'
    $table->string('model');
    $table->foreignId('user_id')->nullable();
    $table->unsignedInteger('input_tokens');
    $table->unsignedInteger('output_tokens');
    $table->unsignedInteger('cache_read_tokens')->default(0);
    $table->unsignedInteger('latency_ms');
    $table->decimal('cost', 10, 6);
    $table->string('stop_reason')->nullable();
    $table->boolean('errored')->default(false);
    $table->timestamps();

    $table->index(['feature', 'created_at']);
});
```

With it, these questions become SQL queries instead of arguments in a meeting:

- Which feature is 80% of the bill?
- Did p95 latency change when we switched models?
- How many answers hit `max_tokens` and got cut off without anyone noticing?
- Is prompt v4 cheaper than v3 at the same quality?
- Which single user is 30% of yesterday's spend?

Then add a small dashboard: spend per day per feature, p50 and p95 latency, error rate, and cache hit rate. It
is an afternoon of work, and it is the difference between operating a system and hoping.

> Set alerts on **daily spend** and **error rate**, not only on exceptions. The failure that really hurts is a
> loop that works perfectly and costs $900 overnight.

## A production-shaped service

```php
class Assistant
{
    public function answer(User $user, string $question): string
    {
        $started = microtime(true);

        try {
            $message = $this->client->messages->create(/* … */);

            $this->record($user, $message, $started);

            if ($message->stopReason === 'refusal') {
                return __('assistant.refused');
            }

            if ($message->stopReason === 'max_tokens') {
                Log::warning('Truncated answer', ['user' => $user->id]);
            }

            return Claude::text($message);
        } catch (RateLimitException $e) {
            return __('assistant.busy');          // human message, not a 500
        } catch (AnthropicException $e) {
            report($e);
            return __('assistant.unavailable');   // degrade, do not explode
        }
    }
}
```

## Common mistakes

- **Calling the API synchronously inside a web request** while a user watches and nothing is streamed.
- **Putting changing content at the top of the prompt**, which silently kills caching.
- **Retrying 400 errors.** You get the same failure three times, and pay three times.
- **Running a whole batch as one job.** One bad row makes four hundred good rows run again.
- **No spend alert.** You find out when the invoice arrives.
- **No prompt version in the logs.** Quality changed, and you cannot say which change did it.
- **Raw API errors shown to users.** "Overloaded" is not a sentence a customer should read.

## You should now be able to

- [ ] Decide between streaming and queueing for each feature
- [ ] Retry the right errors and open a circuit breaker for the rest
- [ ] Use prompt caching correctly, and explain why order matters
- [ ] Log tokens, cost, latency and prompt version on every call
- [ ] Give every AI feature a fallback that does not use AI

## Practice

1. Add the `ai_calls` table and log every call from now on.
2. Convert one synchronous call to streaming. Measure how long until the first word appears.
3. Turn a batch job into per-item queued jobs, with backoff and a timeout.
4. Turn on prompt caching for your longest system prompt. Check `cacheReadInputTokens` on the second call. If it
   is zero, something before the cache point is changing.
5. Set a daily spend alert. Do it today.
