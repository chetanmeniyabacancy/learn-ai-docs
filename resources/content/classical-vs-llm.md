## Summary

- LLMs are for **open-ended language**. If your input is rows and columns, or your output is a ranking, use something else.
- Ask three questions first: can the rules be written down? is the input structured? is there only one correct answer?
- The real advantage of an LLM is that it needs **no training data**. It is not the most accurate option, and not the cheapest at high volume.
- The best systems are **hybrid**. Cheap steps handle most of the traffic, and the LLM handles the difficult remainder.
- Always ask: what happens when it is wrong, who will notice, and how quickly?

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

One sentence holds the whole table together: **LLMs are for open-ended language.**

## Three questions before you reach for a model

**1. Can the rules be written down?**

If you can write the rules, write the rules.

```php
$priority = match (true) {
    $order->total > 10_000 => 'high',
    $customer->isVip() => 'high',
    $order->isLate(days: 5) => 'medium',
    default => 'normal',
};
```

An LLM doing this same job would take a few hundred milliseconds, cost a small fraction of a cent, and be
wrong now and then. The `match` above is correct every single time, costs nothing, and keeps working forever.

**2. Is the input structured or unstructured?**

Numbers and categories arranged in columns belong to classical machine learning. Free text, images and audio
belong to deep learning, and sometimes to an LLM.

Churn prediction is the classic trap. It *sounds* like an AI project. Really it is 40 numeric columns and one
true/false column. Gradient boosting beats an LLM on accuracy, cost and speed, and the gap is large.

**3. Is there only one correct answer?**

"What is this customer's balance?" has exactly one correct answer, so use the system that stores the balance.
"Write a friendly reminder about that balance" has many acceptable answers. That second kind of task is where
an LLM belongs.

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

Look carefully: the LLM was not even the most accurate option, and it cost 250 times more.

But now change the number. At *ten thousand* documents the LLM clearly wins. There is no labelling to do, no
training to run, and it can be working this afternoon.

**So the LLM's real advantage is that it works straight away, with no training data.** That advantage is worth
paying for until your volume grows too big. Knowing roughly where that turning point sits is what makes an
engineer senior.

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

Most messages are finished before they reach the expensive path. The LLM only handles the open-ended cases,
which is the work it is uniquely good at.

Fraud detection built properly looks the same. A machine learning model gives a score in milliseconds, and an
LLM writes the explanation a human can read. Each part does the half it is good at.

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

The last two rows are the argument *for* LLMs. The first three rows are the argument against using them
everywhere.

## Six questions to ask

1. Is the input text, or is it columns?
2. Is there exactly one correct answer?
3. Could a good junior developer write the rules in a day?
4. What is the volume, and what does that cost at $3 per million tokens?
5. Does the output have to be explainable to a customer or a regulator?
6. **What happens when it is wrong? Who notices, and how fast?**

Question 6 is the one people forget, and it decides whether the feature is safe to build at all. An LLM that
drafts a reply for a human to approve fails gently. An LLM that approves refunds by itself does not.

## Common mistakes

- Treating the LLM as the tool for everything. It is expensive, slow, unpredictable, and often less accurate.
- Using it for arithmetic. Give it a calculator tool instead (Level 1 module 4).
- Using it for an exact lookup. That job belongs to SQL.
- Ignoring the volume turning point. Fine at 10k a month, painful at 10M.
- Leaving question 6 until after launch.
- Refusing to use an LLM on principle. Writing 400 rules by hand for something an LLM handles in a paragraph is
  stubbornness, not discipline.

## You should now be able to

- [ ] Send a task to rules, classical ML, or an LLM, and explain your choice
- [ ] Spot a rows-and-columns problem that is dressed up as an AI request
- [ ] Do the volume maths that changes the answer
- [ ] Design a hybrid pipeline
- [ ] Ask the six questions before you agree to build something

## Practice

1. Take the last five "can we use AI for this?" requests you received. Route each one honestly. Expect two to
   turn out to be SQL or plain rules.
2. Take one AI feature you already run. Work out its cost at today's volume, then at ten times that volume.
   Find the point where another approach wins.
3. For your riskiest AI idea, write down the answer to question 6. If the answer is "nobody would notice", fix
   that before building anything else.

---

**That is Level 0.** You now know what the model is, how it learned, what happens when you call it, and when
you should not call it at all. In Level 1 you start building.
