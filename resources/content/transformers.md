## Summary

- The whole training goal is one thing: **guess the next token**. Nothing more.
- The raw scores the model produces are called **logits**. **Softmax** turns them into percentages that add up to 1.
- **Temperature** used to make those percentages flatter or sharper. Current Claude models removed it.
- **Attention** lets every token look at every other token directly, and pay more attention to the important ones.
- Hallucination is not a bug. Writing a believable continuation is exactly the job the model was trained for.
- Cost grows faster than the length of your input, because every token is compared with every other token.

## Next-token prediction

The model reads the token vectors and gives one score to every token in its vocabulary.

```text
input:  "The customer wants a"

   " refund"       8.2
   " replacement"  6.9
   " discount"     6.1
   " new"          4.8
   " banana"      -3.1
```

Those scores are called **logits**. They can be negative, and they can add up to anything. **Softmax** turns
them into proper percentages:

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

Now the model picks one token, adds it to the end of the text, and runs the whole model again on the longer
text. It repeats this until it produces a stop token. Every reply you have ever seen from an LLM came out of
this small loop. It is also the reason streaming works, which you will use in Level 1 module 10.

## Sampling: picking the token

| Strategy | Behaviour |
|---|---|
| **Greedy** | Always the highest. Repetitive, can loop. |
| **Temperature** | Flatten or sharpen before sampling. |
| **Top-p** | Sample only from the top tokens that sum to p. |

Temperature changes the logits before softmax runs:

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

> **Current Claude models removed `temperature`.** If you send any value other than the default, you get a 400
> error (Level 1 module 1). Today you control style through the prompt instead. It is still worth knowing what
> temperature did, because most tutorials online still set it.

**Now you can see hallucination clearly.** You ask about your refund policy. The model works out a percentage
for every possible next token. It has never seen your handbook, so the most believable continuation is whatever
a normal refund policy sounds like. It writes that. There is no moment in this process where it could have
checked the fact, because there is nothing inside it to check against. The only real fix is to put your actual
text into the input, and that is RAG.

## Attention: the actual invention

To guess the next token well, the model must know which earlier words matter.

```text
"The invoice that Priya sent last Tuesday for the Manchester job was never ___"
```

To guess "paid", the important word is "invoice", which is nine tokens back. "Tuesday" barely matters. Older
designs (called RNNs and LSTMs) read the sentence word by word and squeezed everything into one small memory,
so connections to faraway words slowly faded away.

**Attention fixed this by letting every token look at every other token in a single step.**

From its embedding, each token creates three vectors, using three sets of learned weights:

| Vector | Meaning |
|---|---|
| **Query** | "What am I looking for?" |
| **Key** | "What do I offer?" |
| **Value** | "What do I contribute if picked?" |

```text
attention(Q, K, V) = softmax( Q · Kᵀ / √d ) · V
```

That formula is only four ordinary steps:

1. **Score** — compare every query with every key. A high score means "this one is relevant to me".
2. **Scale** — divide by `√d` to stop the scores from becoming too large.
3. **Softmax** — turn the scores into weights that add up to 1.
4. **Mix** — take a weighted average of the value vectors.

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

After this, each token's output is a mixture of everything it found relevant. This is exactly the **contextual
embedding** that module F5 promised. "Bank" next to "river" and "bank" next to "mortgage" pay attention to
different neighbours, so they finish with different numbers.

**Multi-head attention** simply runs several of these at the same time with different weights. So one head can
follow grammar while another head follows the topic.

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

This block is repeated dozens of times. Attention moves information between positions, and the feed-forward
layers do the thinking at each position. Add the embeddings at the bottom and the logits layer at the top, and
you have every modern frontier model. The 2017 paper that introduced this was called *Attention Is All You
Need*, and the title turned out to be accurate.

## Why this made models good

- **Training runs in parallel.** All tokens are processed together, which RNNs could not do. That made it
  possible to train on huge GPU clusters, and scale was what mattered most.
- **Long-distance links really work.** Token number 4,000 can look straight back at token number 3.
- **Results improved predictably.** More data, more parameters and more compute kept giving better models,
  again and again. That reliability is why companies were willing to spend hundreds of millions of dollars.

## What this explains at work

| You see | Because |
|---|---|
| Cost grows fast with input length | Every token is compared with every other |
| A huge context window still loses to good retrieval | The useful signal is a smaller share of what attention spreads over |
| Streaming works naturally | Generation is literally one token at a time |
| It cannot count letters | It sees tokens, not characters |
| It hallucinates confidently | Its goal is plausible continuation, not truth |
| Prompt position matters | Start and end of the prompt get attended to most reliably |

That last row is the reason Level 1 module 2 tells you to put the role first and the format rules last. It is
not a superstition. It comes from how attention works.

## Common mistakes

- Thinking there is a store of facts inside the model. There is not.
- Trusting the reasoning because the writing sounds confident.
- Setting `temperature` on a current Claude model. It has been removed.
- Assuming a 1M context window makes RAG pointless. Cost and accuracy say otherwise.

## You should now be able to

- [ ] Explain next-token prediction and what softmax does
- [ ] Say what temperature did, and why it was removed
- [ ] Describe query, key and value in one sentence each
- [ ] Explain attention as "every token looks at every other one, with weights"
- [ ] Show how hallucination follows directly from the training goal

## Practice

1. Run the softmax example. Change one logit a little and watch the percentage move a lot.
2. Run the attention example and find the token that carries most of the signal.
3. Explain to a colleague, in two sentences, why the model invents believable policies.
