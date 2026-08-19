## Summary

- Training happens in three stages: **pretraining** gives the model its ability, **fine-tuning** gives it the shape of an assistant, and **preference tuning** gives it its character.
- Almost all the money goes into pretraining. The result is a **base model**, which continues text but does not answer questions properly.
- There are four ways to make a model know about your business: pretrain it, fine-tune it, use **RAG**, or just write a better prompt. The answer is nearly always RAG.
- Fine-tuning is good at teaching *how* to behave. It is bad at teaching *what is true*.
- Facts stored inside the weights cannot be quoted, permission-checked, updated or deleted. Facts you send in the request can be.

## The question you will be asked

> "Can we train the AI on our data?"

It sounds like a sensible question. Usually the answer is no. But you cannot just say no. You need to reply
with real numbers and a better option.

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

This is self-supervised learning (module F2) at a huge size. The model guesses the next token across a large
part of the public internet, plus books and code. It uses gradient descent (F3) on a transformer (F6), and it
runs for months.

Everything the model knows comes from here: grammar, facts, code and reasoning. This stage also costs almost
all the money, somewhere between tens and hundreds of millions of dollars.

What comes out is called a **base model**, and using it feels strange:

```text
prompt:  "What is the capital of France?"

base model:
  "What is the capital of Germany? What is the capital of Spain?
   Answer key: 1. Paris  2. Berlin…"
```

The model is not broken. It decided your text looked like a page from a worksheet, so it continued the
worksheet. That is exactly what it was trained to do.

### Stage 2 — supervised fine-tuning

Now you show the model tens of thousands of pairs: an instruction, and a good answer. People write or check
these pairs. This is ordinary supervised learning, and it is tiny compared to stage 1.

This stage teaches the model the *shape* of being an assistant. A question should get an answer. An instruction
should be followed. The knowledge was already there from stage 1.

```text
after SFT:  "The capital of France is Paris."
```

### Stage 3 — preference tuning

Even after stage 2, a model can talk too much, agree with everything you say, or help with things it should
refuse. The problem is that "helpful" is easy for a human to recognise but almost impossible to write down as
a formula.

So the labs do this instead: create several answers to the same question, ask humans which answer is better,
and train the model towards the answers people preferred. This is **RLHF**, and the same idea appears in
related methods like DPO and constitutional training.

This stage creates the model's tone, its honesty when it is unsure, and its refusals. The
`stop_reason: "refusal"` you handle in Level 1 module 1 was put there in this stage.

> **Pretraining creates ability. Fine-tuning creates form. Preference tuning creates character.**

## So — can we train it on our data?

You have four options. Most people who ask for the first one actually need the third one.

| Approach | Cost | Time | Right when |
|---|---|---|---|
| **Pretrain from scratch** | $10M+ | Months | You are an AI lab |
| **Fine-tune an open model** | $100–$10k | Days–weeks | Style, format, narrow repeated task |
| **RAG** (Level 1 module 7) | Cents per query | Hours | **Facts, documents, policies. Usually this.** |
| **Prompting** (Level 1 module 2) | Cents per query | Minutes | Behaviour, tone, output shape |

### Why RAG beats fine-tuning for knowledge

Keep these five points ready for the meeting:

- **Fine-tuning teaches behaviour well, and facts badly.** Facts learned this way get spread thinly across the
  weights and mixed up with things the model half-remembers from pretraining.
- **Your data keeps changing.** If you edit the handbook, RAG uses the new version on the very next request.
  With fine-tuning you would have to train again.
- **You cannot quote a weight.** RAG can show the exact paragraph the answer came from.
- **You cannot delete a weight.** Imagine you fine-tune on customer data, and then a customer asks you to
  delete their data. With RAG you delete one row.
- **You cannot apply permissions to a weight.** RAG filters by customer or tenant inside the query (Level 1
  module 6).

**When fine-tuning IS the right choice:** you need an output format that prompting cannot reach reliably; you
need a very specific writing style; or you run one narrow task millions of times and a small tuned model is
cheaper. Notice that all three are about *how* the model behaves, not about *what* it knows.

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

Your entire job in Level 1 is filling the second column. Prompting, tools and RAG are three different ways of
doing it.

## Numbers worth carrying

```text
frontier pretraining    ~10^13 tokens, thousands of GPUs, months, $10M–$100M+
fine-tune an open 8B    ~10^4–10^6 examples, a few GPUs, hours–days, $100–$10,000
RAG over your handbook  a few hours of engineering, then cents per query
a prompt change         minutes, free
```

Each row is roughly ten thousand times cheaper than the row above it. So when somebody suggests the top row,
it is almost always a misunderstanding.

## What this means for your API calls

- **The weights never change.** Your prompts do not teach the model anything. It does not learn from use.
- **There is a training cutoff date.** It does not know about last week, including your own product launch.
- **A model version is a fixed snapshot.** `claude-sonnet-5` is one exact set of weights. Pin that exact ID in
  your config.
- **Refusals are trained behaviour**, not a separate filter added on top afterwards.

## Common mistakes

- Suggesting fine-tuning to teach facts. It is slower, costlier, less accurate, and you cannot quote or delete
  anything.
- Expecting the model to learn from your production traffic.
- Fine-tuning on customer data without thinking about deletion requests first.
- Assuming a new model knows about recent events. Always check the cutoff.
- Believing a vendor who says "trained on your data" without asking which stage they mean. It usually means
  RAG.

## You should now be able to

- [ ] Name the three stages and say what each one produces
- [ ] Explain why a base model answers a question with more questions
- [ ] Give four reasons why RAG beats fine-tuning for company knowledge
- [ ] State roughly how much the four approaches cost
- [ ] Answer "can we train it on our data?" in under a minute

## Practice

1. Write your own company's answer to that question. Three sentences, ending with a recommendation.
2. Ask the model about something that happened last month. Watch the training cutoff appear.
3. List three things your company knows that no public model could know. All of them belong in the context.
   That list is your Level 1 backlog.
