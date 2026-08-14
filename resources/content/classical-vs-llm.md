## Summary

- LLMs are for **open-ended language**. If the input is columns, or the output is a ranking, use something else.
- Three questions first: are the rules knowable? is the input structured? is there one right answer?
- The LLM's real advantage is **no training data needed** — not accuracy, and not cost at volume.
- Best systems are **hybrid**: cheap steps handle most traffic, the LLM handles the hard tail.
- Always ask: what happens when it is wrong, who notices, how fast?

## The decision table

| Task | Use | Not an LLM because |
|---|---|---|
| "Status of ORD-1043" | SQL | It is a lookup. Free, instant, exact. |
| Validate an email format | Regex | Rules are knowable |
| "Which customers will churn?" | Gradient boosting | Tabular + labelled = supervised ML |
| "How many units in March?" | Time-series forecast | Numbers, trend, seasonality |
| "Customers also bought…" | Collaborative filtering | Behaviour, not language |
| Detect card fraud | Anomaly detection / ML | Millions of rows, milliseconds, must explain |
| Rank search results | Learning to rank / BM25 | Ranking is not generation |
| Classify 10M docs into 5 buckets | Small fine-tuned classifier | 1000× cheaper at that volume |
| **Summarise a support email** | **LLM** | Open-ended language |
| **Answer from a handbook** | **LLM + RAG** | Language + your documents |
| **Extract data from messy text** | **LLM** | Infinite input variety |
| **Draft a reply in your tone** | **LLM** | Generation |
| **Route free-text to tools** | **LLM** | Intent from language |

The pattern: **LLMs are for open-ended language.**

## Three questions before you reach for a model

**1. Are the rules knowable?**

If you can write them down, write them down.

```php
$priority = match (true) {
    $order->total > 10_000 => 'high',
    $customer->isVip() => 'high',
    $order->isLate(days: 5) => 'medium',
    default => 'normal',
};
```

An LLM would take a few hundred milliseconds, cost a fraction of a cent, and be wrong sometimes. The `match`
is right every time, free, forever.

**2. Is the input structured or unstructured?**

Numbers and categories in columns → classical ML. Free text, images, audio → deep learning, maybe an LLM.

Churn is the standard trap. It *feels* like AI. It is 40 numeric columns and a boolean. Gradient boosting
beats an LLM on accuracy, cost and speed, and it is not close.

**3. Is there one right answer?**

"What is this customer's balance?" has one right answer — use the system that stores it. "Draft a friendly
reminder about it" has many acceptable answers. That is where generation belongs.

## The volume argument

```text
Classify 10,000,000 documents into 5 categories

  LLM (Haiku, ~500 tokens each)
    ~$5,000 · days · accuracy ~96%

  Fine-tuned small classifier
    ~$50 training + ~$20 inference · hours · accuracy ~97%

  Logistic regression on TF-IDF
    ~free · minutes · accuracy ~91%
```

The LLM was not even the most accurate, and it cost 250× more.

But at *ten thousand* documents the LLM wins outright: no labelling, no training, working this afternoon.

**The LLM's real advantage is that it works immediately with no training data.** That is worth paying for
until volume says otherwise. Knowing where that crossover sits is the senior judgement.

## Hybrid systems win

```text
incoming message
      │
      ├─ regex ────────────► order reference? → SQL lookup (free, exact)
      │
      ├─ small classifier ─► spam? → drop (cheap)
      │
      ├─ LLM ─────────────► intent + entities as structured output
      │
      ├─ router (plain PHP)► known intent? → template reply (free)
      │
      └─ LLM + RAG ───────► generated answer with citations
```

Most messages never reach the expensive path. The LLM handles the open-ended tail — what it is uniquely good
at.

Fraud detection done properly: an ML model scores in milliseconds; an LLM writes the human-readable
explanation. Each does its half.

## Cost, speed, determinism

| | SQL / rules | Classical ML | LLM |
|---|---|---|---|
| Latency | < 1 ms | ~10 ms | 500 ms – 10 s |
| Cost per call | ~0 | ~0 | $0.0001 – $0.05 |
| Deterministic | Yes | Yes | No |
| Explainable | Fully | Mostly | Barely |
| Needs training data | No | Yes | No |
| Handles new input | No | Somewhat | Well |
| Time to first version | Hours | Weeks | Minutes |

Read the last two rows for the case *for* LLMs. Read the first three for the case against using them
everywhere.

## Six questions to ask

1. Is the input text, or columns?
2. Is there exactly one correct answer?
3. Could a competent junior write the rules in a day?
4. What volume, and what does that cost at $3 per million tokens?
5. Must the output be explainable to a customer or regulator?
6. **What happens when it is wrong? Who notices, and how fast?**

Question 6 is the one people skip, and it decides whether the feature is safe to build. An LLM drafting a
reply a human approves fails softly. An LLM approving refunds does not.

## Common mistakes

- The LLM as a universal hammer. Expensive, slow, non-deterministic, often less accurate.
- Using it for arithmetic. Give it a calculator tool (Level 1 module 4).
- Using it for exact lookup. That is SQL.
- Ignoring the volume crossover. Fine at 10k/month, ruinous at 10M.
- Skipping question 6 until after launch.
- Refusing to use one out of purity. Hand-writing 400 rules for something an LLM does in a paragraph is
  stubbornness, not discipline.

## You should now be able to

- [ ] Route a task to rules, classical ML, or an LLM, and defend it
- [ ] Spot the tabular-supervised trap dressed as an AI request
- [ ] Do the volume arithmetic that flips the answer
- [ ] Design a hybrid pipeline
- [ ] Ask the six questions before agreeing to build

## Practice

1. Take the last five "can we use AI for this?" requests. Route each honestly. Expect two to be SQL or rules.
2. Take one AI feature you run. Cost it at current volume, then at 10×. Find where another approach wins.
3. For your riskiest AI idea, answer question 6 in writing. If the answer is "nobody would notice", reconsider
   that feature first.

---

**That is Level 0.** You know what the model is, how it learned, what it does when you call it, and when not
to call it. Level 1 is where you build.
