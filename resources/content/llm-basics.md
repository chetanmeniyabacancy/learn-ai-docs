## Summary

- **Tokens** are the pieces text is cut into. One token is about ¾ of a word. You pay per token, so measure them with `countTokens` instead of guessing.
- The **context window** is one shared budget. Your system prompt, chat history, documents and the answer all fit inside it.
- Start with **Sonnet**. Move down to Haiku only after tests prove quality is still fine. Move up to Opus only for hard thinking.
- **`temperature` no longer exists** on current Claude models. If you send it, you get a 400 error.
- **Hallucination** means the model confidently writes something untrue. It happens because the model predicts text. Fix it with code and real data, not by asking politely.
- Always check `stopReason` before you use the text.

## The problem

A distributor gets 5,000 emails every month. Two people read each one and tag it by hand. It takes most of
their morning every day, and when the queue grows, mistakes start.

That work does not need a human. It needs a reader that never gets tired.

## Tokens

Before the model sees your text, the text is cut into small pieces called tokens.

- 1 token is roughly ¾ of an English word
- 1,000 tokens is roughly 750 words, about 1.5 pages
- Code and non-English text need more tokens for the same number of characters

Tokens decide three things: your bill, how much text fits in one request, and how slow the reply is. So when
something feels slow or expensive, it usually means you are sending or receiving too many tokens.

When the number matters, ask the API instead of guessing:

```php
$count = $client->messages->countTokens(
    model: 'claude-sonnet-5',
    messages: [['role' => 'user', 'content' => $document]],
);

echo $count->inputTokens;
```

> Do not use OpenAI's `tiktoken` to count tokens for Claude. It cuts text differently. It is usually 15–20%
> too low for normal text, and worse for code.

## The context window

One request has one budget. Your system prompt, the chat history, the documents you attach and the answer
itself all share it.

| Model | Context | Max output |
|---|---|---|
| Sonnet 5 | ~1,000,000 | 128,000 |
| Opus 5 | ~1,000,000 | 128,000 |
| Haiku 4.5 | 200,000 | 64,000 |

A million tokens sounds big enough to skip RAG completely. It is not, for three reasons:

1. **Cost.** You pay for every token in every request. Sending a 500-page handbook just to answer "what are
   your opening hours?" costs about 200 times more than sending one paragraph.
2. **Speed.** More input means a longer wait before the first word appears.
3. **Accuracy.** When the answer is hidden inside a lot of unrelated text, the model finds it less reliably.
   Four useful paragraphs work better than four hundred useless ones.

So treat the context window as a limit you must stay under, not a target to fill.

## Choosing a model

| Model | Best for | $/1M in/out |
|---|---|---|
| Haiku 4.5 | Classification, tagging, extraction at volume | $1 / $5 |
| Sonnet 5 | The default for product features | $3 / $15 |
| Opus 5 | Hard multi-step reasoning, long agent work | $5 / $25 |

Begin with Sonnet. Later you can move *down* to Haiku, but only after your tests show the quality stays good.
At high volume that saves real money. Move *up* to Opus only when the task truly needs deeper thinking.

Use the exact model ID strings. Do not add a date or invent a variant. A wrong ID gives you a 404.

## Thinking and effort

Current models can think first and answer after.

```php
$message = $client->messages->create(
    model: 'claude-sonnet-5',
    maxTokens: 4096,
    thinking: ['type' => 'adaptive'],      // model decides how much to think
    outputConfig: ['effort' => 'medium'],  // low | medium | high | xhigh | max
    messages: [['role' => 'user', 'content' => $hardQuestion]],
);
```

Thinking tokens are charged at the output price. So the effort setting directly controls your cost and your
waiting time. For classification and extraction, `low` is enough. For multi-step work, `high` is worth paying
for.

> Old tutorials still show `temperature`. Current Claude models **removed** it, and sending any value other
> than the default gives a 400 error. Control tone and length in the prompt instead. Many people get stuck
> here.

## Hallucination

Ask about your refund policy and you may get an answer that is confident, well written, and completely
invented.

This is not a bug, and no update will remove it. The model's job is to continue text in a believable way. It
has never seen your policy. So the most believable continuation of "our refund policy is" is *some
normal-looking refund policy* — not the truth.

You reduce it by changing the structure of your feature:

| Fix | Module |
|---|---|
| Give it the real source text and require citations | 7 (RAG) |
| Give it real data through functions | 4 (tools) |
| Force a fixed output shape | 3 |
| Allow "I don't know" | 2 |
| Measure how often it happens | 8 |

People skip the last one most often, and it matters most. "It hallucinates sometimes" is a feeling, not a
measurement.

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

Use Haiku. Each email costs about 600 input tokens and 40 output tokens. So 5,000 emails cost roughly
**$0.90 a month**, and that replaces most of two people's mornings.

That small calculation is what gets the feature approved — not the technology.

## Common mistakes

- Reading `content[0]->text`. It breaks as soon as you use thinking or tools.
- Not checking `stopReason`. A cut-off answer and a refused answer both just look short.
- Assuming `temperature` still works.
- Guessing token counts with `str_word_count`.
- Sending whole documents "because the context window is huge".

## You should now be able to

- [ ] Explain what a token is, and why it controls both cost and capacity
- [ ] Choose a model based on test results, not on feeling
- [ ] Name three structural ways to reduce hallucination
- [ ] Read a response carefully, checking for refusal and cut-off
- [ ] Estimate what a batch job will cost per month

## Practice

1. Run `countTokens` on a real document. Compare the result with `str_word_count`.
2. Build the email classifier and run it on ten real emails. Count how many you would have tagged differently.
   That number is your quality baseline.
3. Run the same ten emails on Haiku and on Sonnet. Is the difference worth paying 3× more?
4. Ask about a policy that only your company has. Watch it invent one.
