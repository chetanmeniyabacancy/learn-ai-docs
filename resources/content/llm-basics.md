## Summary

- **Tokens** are your unit of cost and capacity. ~¾ of a word. Measure with `countTokens`, do not guess.
- **Context window** = system prompt + history + documents + answer, all in one budget.
- Start on **Sonnet**. Go down to Haiku with evidence. Go up to Opus for hard reasoning.
- **`temperature` was removed** on current Claude models. A non-default value returns a 400.
- **Hallucination** is what a text predictor does without grounding. Fix it structurally, not by asking nicely.
- Always check `stopReason` before reading the text.

## The problem

A distributor gets 5,000 emails a month. Two people read and tag every one. It takes most of a day, each day,
and they still get it wrong when the queue backs up.

That job does not need a human. It needs a reader that never gets bored.

## Tokens

Text is split into tokens before the model sees it.

- 1 token ≈ ¾ of an English word
- 1,000 tokens ≈ 750 words ≈ 1.5 pages
- Code and non-English text use more tokens for the same characters

Tokens are how you are billed, how capacity is measured, and how latency scales. When something is slow or
expensive, you are sending or receiving too many tokens.

Do not estimate from word count when it matters:

```php
$count = $client->messages->countTokens(
    model: 'claude-sonnet-5',
    messages: [['role' => 'user', 'content' => $document]],
);

echo $count->inputTokens;
```

> Do not use OpenAI's `tiktoken` for Claude. Different tokenizer — usually 15–20% low on prose, worse on code.

## The context window

Everything in one request shares one budget: system prompt + history + retrieved documents + the answer.

| Model | Context | Max output |
|---|---|---|
| Sonnet 5 | ~1,000,000 | 128,000 |
| Opus 5 | ~1,000,000 | 128,000 |
| Haiku 4.5 | 200,000 | 64,000 |

A million tokens sounds like it makes RAG unnecessary. It does not:

1. **Cost.** You pay for every token, every request. Sending a 500-page handbook to answer "what are your
   opening hours?" costs ~200× more than sending one paragraph.
2. **Latency.** More input, more time to first token.
3. **Accuracy.** Precision drops when the answer is buried in noise. Four relevant paragraphs beat four
   hundred irrelevant ones.

The context window is a ceiling, not a target.

## Choosing a model

| Model | Best for | $/1M in/out |
|---|---|---|
| Haiku 4.5 | Classification, tagging, extraction at volume | $1 / $5 |
| Sonnet 5 | The default for product features | $3 / $15 |
| Opus 5 | Hard multi-step reasoning, long agent work | $5 / $25 |

Start on Sonnet. Move *down* to Haiku once evals prove quality holds — a real saving at volume. Move *up* to
Opus when the task genuinely needs deeper reasoning.

Use exact model ID strings. Do not add dates or invent variants; a wrong ID is a 404.

## Thinking and effort

Current models can reason before answering.

```php
$message = $client->messages->create(
    model: 'claude-sonnet-5',
    maxTokens: 4096,
    thinking: ['type' => 'adaptive'],      // model decides how much to think
    outputConfig: ['effort' => 'medium'],  // low | medium | high | xhigh | max
    messages: [['role' => 'user', 'content' => $hardQuestion]],
);
```

Thinking tokens are billed like output tokens, so effort is a direct cost and latency dial. Classification and
extraction: `low` is plenty. Multi-step work: `high` earns its money.

> Older tutorials show `temperature`. Current Claude models **removed** it — a non-default value returns a
> 400. Steer style and length through the prompt. This catches people out constantly.

## Hallucination

Ask about your refund policy and you may get a confident, well-written, invented answer.

Not a bug, and no patch is coming. The model completes text plausibly. With no grounding, the likeliest
continuation of "our refund policy is" is *a normal-sounding refund policy* — not the truth, which it has
never seen.

Reduce it structurally:

| Fix | Module |
|---|---|
| Give it the real source text and require citations | 7 (RAG) |
| Give it real data through functions | 4 (tools) |
| Force a fixed output shape | 3 |
| Allow "I don't know" | 2 |
| Measure how often it happens | 8 |

The last one is skipped most and matters most. "It hallucinates sometimes" is not a measurement.

## Reading a response properly

```php
$message = $client->messages->create(/* … */);

// 1. Did it decline? HTTP 200, not an exception.
if ($message->stopReason === 'refusal') {
    return $this->handleRefusal($message->stopDetails?->category);
}

// 2. Did it run out of room? The answer is cut off.
if ($message->stopReason === 'max_tokens') {
    Log::warning('Answer truncated', ['id' => $message->id]);
}

// 3. Concatenate text blocks — never index content[0].
$text = collect($message->content)
    ->where('type', 'text')
    ->pluck('text')
    ->implode("\n");
```

| `stopReason` | Meaning |
|---|---|
| `end_turn` | Finished normally |
| `max_tokens` | Hit your ceiling — output cut off |
| `tool_use` | Wants a tool run (module 4) |
| `refusal` | Declined; content is empty |

## Back to the 5,000 emails

```php
$system = <<<'TXT'
You classify inbound emails for a distributor.

Reply with exactly three lines and nothing else:
Category: one of [order_status, invoice, complaint, returns, sales, spam]
Priority: one of [low, normal, high, urgent]
Summary: one sentence, under 20 words

Use only what the email says. If unclear, use category "sales" and priority "normal".
TXT;

$result = $claude->ask($system, $email->body);
```

Haiku, ~600 input and ~40 output tokens per email. 5,000 emails ≈ **$0.90 a month**, replacing most of two
people's mornings.

That arithmetic — not the technology — gets the feature approved.

## Common mistakes

- Reading `content[0]->text`. Breaks with thinking or tools.
- Not checking `stopReason`. Truncated and refused answers both look short.
- Assuming `temperature` exists.
- Estimating tokens with `str_word_count`.
- Sending whole documents "because the context window is huge".

## You should now be able to

- [ ] Explain what a token is and why it is cost *and* capacity
- [ ] Pick a model on evidence
- [ ] Name three structural fixes for hallucination
- [ ] Read a response defensively
- [ ] Estimate a batch job's monthly cost

## Practice

1. Run `countTokens` on a real document. Compare with `str_word_count`.
2. Build the email classifier and run it on ten real emails. Count how many you would tag differently. That is
   your quality baseline.
3. Run the same ten on Haiku and Sonnet. Is the difference worth 3×?
4. Ask about a policy only your company has. Watch it invent one.
