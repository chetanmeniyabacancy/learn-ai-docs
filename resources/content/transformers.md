## Summary

- The entire training goal is: **predict the next token**. Nothing else.
- Raw scores are **logits**. **Softmax** turns them into probabilities that sum to 1.
- **Temperature** used to flatten or sharpen that distribution. Current Claude models removed it.
- **Attention** lets every token look directly at every other token, weighted by relevance.
- Hallucination is not a bug. Plausible continuation is the objective.
- Cost grows faster than linearly with context, because every token is compared with every other.

## Next-token prediction

The model takes token vectors and outputs one score per vocabulary token.

```text
input:  "The customer wants a"

   " refund"       8.2
   " replacement"  6.9
   " discount"     6.1
   " new"          4.8
   " banana"      -3.1
```

Those are **logits**. They can be negative and sum to anything. **Softmax** fixes that:

```php
function softmax(array $logits): array
{
    $max = max($logits);                                   // numerical stability
    $exp = array_map(fn ($x) => exp($x - $max), $logits);
    $sum = array_sum($exp);

    return array_map(fn ($e) => $e / $sum, $exp);
}
```

```text
   " refund"      0.7001      ← 70%
   " replacement" 0.1908
   " discount"    0.0857
   " new"         0.0234
   " banana"      0.0000
```

Pick one, append it, run the whole model again on the longer sequence. Repeat until a stop token. Every LLM
response you have ever seen came out of this loop — which is also why streaming works (Level 1 module 10).

## Sampling: picking the token

| Strategy | Behaviour |
|---|---|
| **Greedy** | Always the highest. Repetitive, can loop. |
| **Temperature** | Flatten or sharpen before sampling. |
| **Top-p** | Sample only from the top tokens that sum to p. |

Temperature reshapes logits before softmax:

```php
$scaled = array_map(fn ($logit) => $logit / $temperature, $logits);
$probabilities = softmax($scaled);
```

```text
logits:            refund 8.2   replacement 6.9   discount 6.1

temperature 0.2 →  0.9985       0.0015            0.0000   nearly fixed
temperature 1.0 →  0.7001       0.1908            0.0857   natural
temperature 2.0 →  0.4859       0.2536            0.1700   adventurous
```

> **Current Claude models removed `temperature`.** Sending a non-default value returns a 400 (Level 1 module
> 1). You steer with the prompt now. Knowing what it did still matters, because most tutorials online still
> set it.

**And here is hallucination, mechanically.** Ask about your refund policy. The model computes a distribution
over next tokens. It has never seen your handbook, so the likeliest continuation is whatever a normal refund
policy sounds like. It emits that. There is no step where it could have checked, because there is nothing to
check against. The fix is to put the real text in the input — RAG.

## Attention: the actual invention

To predict the next token you must know which earlier tokens matter.

```text
"The invoice that Priya sent last Tuesday for the Manchester job was never ___"
```

To predict "paid" you need "invoice" — nine tokens back — far more than "Tuesday". Older architectures (RNNs,
LSTMs) read in order and carried everything in a fixed-size memory, so long links faded.

**Attention lets every token look at every other token in one step.**

Each token produces three vectors from its embedding, using three learned weight matrices:

| Vector | Meaning |
|---|---|
| **Query** | "What am I looking for?" |
| **Key** | "What do I offer?" |
| **Value** | "What do I contribute if picked?" |

```text
attention(Q, K, V) = softmax( Q · Kᵀ / √d ) · V
```

Four ordinary steps:

1. **Score** — dot each query with each key. High = relevant.
2. **Scale** — divide by `√d` so scores do not explode.
3. **Softmax** — turn scores into weights summing to 1.
4. **Mix** — weighted average of the value vectors.

```text
predicting after "was never"

  "The"          0.02
  "invoice"      0.41   ←  most of the signal
  "Priya"        0.08
  "sent"         0.11
  "Tuesday"      0.03
  "Manchester"   0.06
  "job"          0.09
  "was"          0.12
  "never"        0.04

  → the mixed vector is mostly "invoice", so "paid" scores high
```

Each token's output is now a blend of what it found relevant. That is the **contextual embedding** promised in
module F5: "bank" near "river" and "bank" near "mortgage" attend to different neighbours, so they end up as
different vectors.

**Multi-head attention** runs several of these at once with different weights, so one head can track grammar
while another tracks topic.

## A transformer block

```text
                ┌──────────────────────────┐
   input   ───► │  multi-head attention    │  tokens share information
   vectors      ├──────────────────────────┤
                │  add & normalise         │
                ├──────────────────────────┤
                │  feed-forward network    │  the neurons from module F4
                ├──────────────────────────┤
                │  add & normalise         │
                └──────────┬───────────────┘
                           ▼
                     next block
```

Repeat dozens of times. Attention moves information between positions; the feed-forward layers do the
per-position thinking. Add embeddings at the bottom and a logits layer at the top, and that is every current
frontier model. The 2017 paper was called *Attention Is All You Need*, and the title was not an exaggeration.

## Why this made models good

- **Parallel training.** All tokens process at once, unlike RNNs. That let training scale to GPU clusters —
  and scale was the whole game.
- **True long-range links.** Position 4,000 can look straight at position 3.
- **Predictable scaling.** More data + more parameters + more compute reliably gave better results, over
  several orders of magnitude. That predictability is why anyone spent hundreds of millions of dollars.

## What this explains at work

| You see | Because |
|---|---|
| Cost grows fast with input length | Every token is compared with every other |
| A huge context window still loses to good retrieval | The useful signal is a smaller share of what attention spreads over |
| Streaming works naturally | Generation is literally one token at a time |
| It cannot count letters | It sees tokens, not characters |
| It hallucinates confidently | Its goal is plausible continuation, not truth |
| Prompt position matters | Start and end of the prompt get attended to most reliably |

That last row is why Level 1 module 2 says role first, format rules last. Not superstition — a property of the
mechanism.

## Common mistakes

- Thinking there is a fact store inside. There is not.
- Expecting reliable reasoning because the text is fluent.
- Setting `temperature` on a current Claude model. Removed.
- Assuming a 1M context window makes RAG unnecessary. Cost and accuracy disagree.

## You should now be able to

- [ ] Explain next-token prediction and what softmax does
- [ ] Say what temperature did and why it was removed
- [ ] Describe query, key and value in one sentence each
- [ ] Explain attention as "every token looks at every other, weighted"
- [ ] Derive hallucination from the training goal

## Practice

1. Run the softmax example. Change a logit slightly and watch the probability move a lot.
2. Run the attention example and find the token carrying most of the signal.
3. Explain to a colleague in two sentences why the model invents plausible policies.
