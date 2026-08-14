## Summary

- **Supervised** = inputs with known answers. Predicts a label or a number. Most business ML.
- **Unsupervised** = no answers. Finds groups or odd rows.
- **Reinforcement** = actions and rewards. Rare in business software.
- **Self-supervised** = the data labels itself. Hide the next word; that word is the label. This built your LLM.
- Labels are the expensive part of supervised learning. Your database already holds some.

## Four questions, four kinds

You have a `customers` table. Someone asks four things:

1. "Which customers will cancel next month?"
2. "Are there natural groups we should market to differently?"
3. "What email sequence wins people back?"
4. "Can we write the win-back email automatically?"

These look the same. They are four different kinds of problem. Picking wrong kills a project before any code
is written.

| Kind | You give it | It learns | Example question |
|---|---|---|---|
| **Supervised** | Inputs **with** answers | Predict the answer for new inputs | "Will this customer churn?" |
| **Unsupervised** | Inputs only | Structure in the data | "What groups exist?" |
| **Reinforcement** | Environment + reward | What to do next | "Which action keeps them?" |
| **Self-supervised** | Raw data that labels itself | General patterns | "What word comes next?" |

## Supervised: learning from answers

You have past data where you know what happened.

```php
$trainingData = [
    ['tenure_months' => 3,  'logins_30d' => 1,  'tickets' => 4, 'churned' => true],
    ['tenure_months' => 26, 'logins_30d' => 44, 'tickets' => 0, 'churned' => false],
    // …49,998 more
];
```

Left columns = **features**. Right column = **label**. Training finds the link. Inference applies it to
someone whose future you do not know.

Two types:

**Classification** — the label is a category.

```text
churned: true / false
category: billing | shipping | technical
```

**Regression** — the label is a number.

```text
lifetime_value: £2,340
delivery_days: 4.2
```

> The whole method needs labels, and labels cost money. "We'll use AI" often means "someone must hand-label
> 5,000 rows first". Ask on day one.

**You already have labels.** Whether the invoice was paid. Whether the ticket was reopened. Which category a
human chose. Whether delivery was late. Whether the user clicked. Your database is full of accidental
training data.

## Unsupervised: finding structure with no answers

Nobody says what is right. The algorithm finds patterns in the shape of the data.

**Clustering** — group similar rows.

```php
$clusters = $kmeans->fit($customers, groups: 4);

// You did NOT say what the groups are. Naming them is your job:
//   0 — high spend, low frequency
//   1 — low spend, high frequency
//   2 — new, barely active
//   3 — long tenure, high usage
```

**Anomaly detection** — flag rows that do not fit. Fraud, broken sensors, bots. Useful because you do not need
labelled fraud, which you usually do not have.

**Dimensionality reduction** — squeeze many features into a few. Close to what embeddings do (module F5).

The catch: there is no right answer to check. Ask for four groups, get four groups. Whether they mean anything
is your judgement.

## Reinforcement: learning from consequences

An agent acts, gets a reward, and learns a **policy** — what to do in each situation.

```text
state → action → reward → new state → …
```

This beat humans at Go and controls robots. Rare in business software: it needs a simulator, or a lot of
expensive real-world trial and error.

You meet it once: **RLHF** (reinforcement learning from human feedback) helped teach your model to be helpful,
not just fluent. Module F7.

## Self-supervised: the LLM trick

Supervised learning needs labels. Labelling enough text to teach a machine *language* would take longer than
humanity has.

The trick: **let the data label itself.** Take any sentence. Hide the last word. That word is the label.

```text
"The cat sat on the"        → mat
"To be or not to"           → be
"$user = User::find("       → $id
"The refund window is 30"   → days
```

No human wrote those labels. Every sentence ever written is now a free training example, and there are
trillions.

The surprise — a real surprise to the field — was that a model which gets very good at this one boring task
also picks up grammar, facts, translation, code and enough reasoning to be useful. To predict text well across
the whole internet, you must model a lot about the world.

> This is the most important idea in Level 0. Everything an LLM does well, and everything it does badly, comes
> from being trained to continue text — not to be correct, not to be helpful.

## Back to the four questions

| Question | Kind | Use |
|---|---|---|
| "Which customers will cancel?" | Supervised (classification) | Gradient boosting. **Not an LLM.** |
| "What groups exist?" | Unsupervised (clustering) | k-means, DBSCAN |
| "Best email sequence?" | Reinforcement | Honestly: an A/B test |
| "Write the email" | Generative, built on self-supervised | An LLM — your job |

Only the fourth is yours. Spotting the other three, and saying so, is worth more to your team than being able
to build any of them.

## Common mistakes

- Using an LLM for a tabular supervised problem. Gradient boosting is cheaper, faster and more accurate.
- Starting a supervised project with no labels. The labelling *is* the project.
- Trusting clusters because they exist. The algorithm always returns groups.
- Thinking an LLM "learns" from your prompts. That is training, and it already happened.

## You should now be able to

- [ ] Put a business question in the right box
- [ ] Tell classification from regression
- [ ] Explain self-supervised learning and why it unlocked LLMs
- [ ] Spot the hidden labelling cost in a proposal
- [ ] Find accidental training data in your own database

## Practice

1. Take three backlog items. Put each in one of the four boxes. At least one will need no model.
2. Find three columns in your schema that are really labels — a human decision already recorded.
3. Take any sentence, remove the last five words, and predict them. That is pretraining, once. Now imagine
   trillions.
