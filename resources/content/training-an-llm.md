## Summary

- Three stages: **pretraining** (capability), **fine-tuning** (form), **preference tuning** (character).
- Pretraining is where nearly all the cost is. A **base model** continues text but does not answer questions.
- Four ways to make a model know your business: pretrain, fine-tune, **RAG**, prompt. Almost always RAG.
- Fine-tuning teaches *how* to behave. It is bad at teaching *what* is true.
- Knowledge in weights cannot be cited, permissioned, updated or deleted. Knowledge in context can be.

## The question you will be asked

> "Can we train the AI on our data?"

It sounds reasonable. It is usually wrong. You need to answer with numbers and an alternative, not a shrug.

## Three stages

```text
┌──────────────────────────────────────────────────────────────┐
│  1. PRETRAINING          self-supervised, enormous            │
│     trillions of tokens · months · thousands of GPUs          │
│     → a model that continues text                             │
├──────────────────────────────────────────────────────────────┤
│  2. SUPERVISED FINE-TUNING (SFT)    supervised, small         │
│     tens of thousands of curated examples                     │
│     → a model that answers questions                          │
├──────────────────────────────────────────────────────────────┤
│  3. PREFERENCE TUNING (RLHF)        reinforcement             │
│     humans rank pairs of answers                              │
│     → helpful, honest, declines sensibly                      │
└──────────────────────────────────────────────────────────────┘
```

### Stage 1 — pretraining

Self-supervised learning (module F2) at huge scale: predict the next token across a large slice of the public
internet, books and code. Gradient descent (F3) on a transformer (F6), for months.

This is where knowledge, grammar, code and reasoning come from. It is also where nearly all the cost is: tens
to hundreds of millions of dollars.

The result is a **base model**, and it is odd to use:

```text
prompt:  "What is the capital of France?"

base model:
  "What is the capital of Germany? What is the capital of Spain?
   Answer key: 1. Paris  2. Berlin…"
```

Not wrong. It decided this looked like a worksheet and continued the worksheet. That is what it was trained to
do.

### Stage 2 — supervised fine-tuning

Show it tens of thousands of `(instruction, good answer)` pairs written or checked by people. Ordinary
supervised learning, tiny next to stage 1.

This teaches the *shape* of being an assistant: a question gets an answer, an instruction gets followed. The
knowledge was already there.

```text
after SFT:  "The capital of France is Paris."
```

### Stage 3 — preference tuning

Even after SFT a model can be wordy, over-agreeable, or willing to help with things it should not.
"Helpful" is easy to recognise and very hard to write as a loss function.

So: generate several answers, have humans rank them, train toward the preferred ones. That is **RLHF** and its
relatives (DPO, constitutional methods).

This stage produces tone, honesty about uncertainty, and refusals. The `stop_reason: "refusal"` you handle in
Level 1 module 1 was installed here.

> **Pretraining creates capability. SFT creates form. Preference tuning creates character.**

## So — can we train it on our data?

Four options. Most people asking for the first need the third.

| Approach | Cost | Time | Right when |
|---|---|---|---|
| **Pretrain from scratch** | $10M+ | Months | You are an AI lab |
| **Fine-tune an open model** | $100–$10k | Days–weeks | Style, format, narrow repeated task |
| **RAG** (Level 1 module 7) | Cents per query | Hours | **Facts, documents, policies. Usually this.** |
| **Prompting** (Level 1 module 2) | Cents per query | Minutes | Behaviour, tone, output shape |

### Why RAG beats fine-tuning for knowledge

Have this argument ready:

- **Fine-tuning teaches behaviour well and facts badly.** Facts learned this way are diffuse and unreliable,
  and get blended with half-remembered pretraining.
- **Your data changes.** Update the handbook and RAG picks it up next request. Fine-tuning means retraining.
- **You cannot cite a weight.** RAG shows which passage the answer came from.
- **You cannot delete a weight.** Fine-tune on customer data, then get a deletion request. RAG deletes a row.
- **You cannot permission a weight.** RAG filters by tenant in the query (Level 1 module 6).

**When fine-tuning IS right:** a fixed output format prompting cannot reach; a domain writing style; a narrow
task run millions of times where a small tuned model is cheaper. All three are about *how*, not *what*.

## Knowledge in weights vs in context

| | In the weights | In the context |
|---|---|---|
| Comes from | Training | Your request |
| To change it | Retrain | Edit a row |
| Cost | Huge, one-off | Tokens, per request |
| Can be cited | No | Yes |
| Can be permissioned | No | Yes |
| Can be deleted | No | Yes |
| Fresh | Frozen at cutoff | As fresh as your database |

Your whole job in Level 1 is filling the second column. Prompting, tools and RAG are all ways to do that.

## Numbers worth carrying

```text
frontier pretraining    ~10^13 tokens, thousands of GPUs, months, $10M–$100M+
fine-tune an open 8B    ~10^4–10^6 examples, a few GPUs, hours–days, $100–$10,000
RAG over your handbook  a few hours of engineering, then cents per query
a prompt change         minutes, free
```

Four orders of magnitude between neighbouring rows. When someone proposes the top row, that is usually a
misunderstanding.

## What this means for your API calls

- **The weights are frozen.** Your prompts change nothing. No learning from usage.
- **There is a training cutoff.** It does not know about last week, including your launch.
- **Model versions are snapshots.** `claude-sonnet-5` is one fixed set of weights. Pin the exact ID in config.
- **Refusals are trained behaviour**, not a filter added afterwards.

## Common mistakes

- Proposing fine-tuning for facts. Slower, dearer, less accurate, uncitable, undeletable.
- Expecting the model to learn from production traffic.
- Fine-tuning on customer data without thinking about deletion requests.
- Assuming a new model knows about recent events. Check the cutoff.
- Believing "trained on your data" without asking which stage. Usually it means RAG.

## You should now be able to

- [ ] Name the three stages and what each produces
- [ ] Explain why a base model answers a question with more questions
- [ ] Give four reasons RAG beats fine-tuning for company knowledge
- [ ] State the cost gap between the four approaches
- [ ] Answer "can we train it on our data?" in under a minute

## Practice

1. Write your company's answer to that question — three sentences, with a recommendation.
2. Ask the model about something from last month. Watch the cutoff appear.
3. List three things your company knows that no public model could. All belong in the context. That list is
   your Level 1 backlog.
