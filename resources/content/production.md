## Summary

- Treat the model as a slow, rate-limited, per-request-billed external API. Everything follows from that.
- **User waiting → stream. Nobody waiting → queue.** One job per item, never per batch.
- Retry 429, 5xx and timeouts. **Never retry a 400.**
- **Prompt caching** cuts repeated prefixes to ~10% cost — but stable content must come first.
- Log every call: feature, prompt version, model, tokens, latency, cost, stop reason.
- Always have a non-AI fallback.

## The problem

The demo was perfect. Then it launched.

- Users stare at a spinner for eight seconds and assume it is broken.
- A queue worker times out and retries the *entire* batch.
- A 429 during a spike surfaces straight to customers.
- Finance asks what the $2,400 line item is and you cannot break it down.
- Someone says "it feels worse this week" and you cannot check.

None of this is AI-specific. It is normal integration work with an unusually expensive, slow, chatty
dependency.

## Latency: stream or queue

A model generates tokens one at a time. A 500-word answer takes seconds and you cannot make that small.

**User waiting → stream.** Same total time, first words in under a second.

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

`X-Accel-Buffering` is the header everyone loses an afternoon to. Without it nginx delivers the whole stream
at once, which looks exactly like streaming not working.

**Nobody waiting → queue.** Batch classification, indexing, overnight summaries.

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

One job per item, not per batch. A failure then retries one ticket instead of re-billing you for four hundred.

## Failure: retry the right things

| Status | Meaning | Do |
|---|---|---|
| 429 | Rate limited | Retry with backoff, honour `retry-after` |
| 500 / 529 | Server error | Retry with backoff |
| 400 | Bad request | **Do not retry** — fix the payload |
| 401 | Bad key | **Do not retry** — page someone |
| Timeout | Slow generation | Retry, or lower `maxTokens` |

The SDK already retries connection errors, 429 and 5xx a couple of times. Beyond that, add a circuit breaker
so a provider outage degrades your app instead of hanging every worker:

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

And always have a non-AI fallback. If the assistant is down, show the search box and the contact form.

## Cost: four levers

**1. Prompt caching.** If a long, stable prefix repeats — a big system prompt, a fixed document, few-shot
examples — mark it and pay about a tenth for those tokens next time.

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

**Caching is a prefix match.** One changed byte before the cache point invalidates everything after it. So:
stable content first, volatile content last. Putting `now()` or a user's name at the top of your system prompt
silently disables caching on every request, and the only symptom is a bigger bill.

**2. Right-size the model.** Haiku for classification, Sonnet for the assistant, Opus for the rare hard case.
That mix routinely halves a bill with no quality loss.

**3. Send fewer tokens.** Retrieve 4 chunks not 10. Trim conversation history. Summarise old context. Input
tokens are most of most bills.

**4. Cache identical requests.**

```php
return Cache::remember('triage:'.sha1($text.TriagePrompt::VERSION), now()->addDay(),
    fn () => $this->triage($text));
```

Include the prompt version in the key. Otherwise your prompt fix does nothing for a day.

## Observability

If you build one thing from this module, build this.

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

Now these become SQL instead of arguments:

- Which feature is 80% of the bill?
- Did p95 latency move when we switched models?
- How many answers hit `max_tokens` and were silently truncated?
- Is prompt v4 cheaper than v3 at the same quality?
- Which user is 30% of yesterday's spend?

Add a small dashboard: spend per day per feature, p50/p95 latency, error rate, cache hit rate. An afternoon of
work, and the difference between operating a system and hoping.

> Alert on **daily spend** and **error rate**, not just exceptions. The failure that hurts is a loop that works
> perfectly and costs $900 overnight.

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

- **Synchronous calls in a web request** with a user watching and no streaming.
- **Volatile content at the top of the prompt**, silently killing the cache.
- **Retrying 400s.** Three times the failure, three times the bill.
- **Batch jobs as one job.** One bad row re-runs four hundred good ones.
- **No spend alert.** You find out on the invoice.
- **No prompt version in the logs.** Quality moved; you cannot attribute it.
- **Raw API errors reaching users.** "Overloaded" is not a customer-facing sentence.

## You should now be able to

- [ ] Choose streaming or queueing per feature
- [ ] Retry the right errors and break the circuit on the rest
- [ ] Use prompt caching correctly and explain prefix order
- [ ] Log tokens, cost, latency and prompt version on every call
- [ ] Give every AI feature a non-AI fallback

## Practice

1. Add the `ai_calls` table and log every call from now on.
2. Convert one synchronous call to streaming. Time the first word.
3. Move a batch job to per-item queued jobs with backoff and a timeout.
4. Turn on prompt caching for your longest system prompt. Check `cacheReadInputTokens` on the second call. If
   it is zero, something before the cache point is changing.
5. Set a daily spend alert. Today.
