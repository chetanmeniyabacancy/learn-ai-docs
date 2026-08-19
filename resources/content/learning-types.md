## Summary

- **Supervised** learning uses examples where the answer is already known. It predicts a label or a number. Most business ML is this.
- **Unsupervised** learning gets no answers. It finds groups, or rows that look strange.
- **Reinforcement** learning learns from actions and rewards. It is rare in business software.
- **Self-supervised** learning makes its own labels. Hide the next word, and that word becomes the answer. This is how your LLM was built.
- In supervised learning, the labels cost the most money. The good news: your database already has many of them.

## Four questions, four kinds

You have a `customers` table. Someone asks you four things:

1. "Which customers will cancel next month?"
2. "Are there natural groups we should market to differently?"
3. "What email sequence wins people back?"
4. "Can we write the win-back email automatically?"

They sound like one type of question. They are actually four different kinds of problem. If you pick the wrong
kind, the project dies before you write any code.

| Kind | You give it | It learns | Example question |
|---|---|---|---|
| **Supervised** | Inputs **with** answers | Predict the answer for new inputs | "Will this customer churn?" |
| **Unsupervised** | Inputs only | Structure in the data | "What groups exist?" |
| **Reinforcement** | Environment + reward | What to do next | "Which action keeps them?" |
| **Self-supervised** | Raw data that labels itself | General patterns | "What word comes next?" |

## Supervised: learning from answers

You have old data, and you already know what happened in each row.

```php
$trainingData = [
    ['tenure_months' => 3,  'logins_30d' => 1,  'tickets' => 4, 'churned' => true],
    ['tenure_months' => 26, 'logins_30d' => 44, 'tickets' => 0, 'churned' => false],
    // …49,998 more
];
```

The columns on the left are called **features**. They are the facts you know. The last column is the
**label**. It is the answer you want to predict. Training means letting the computer find the connection
between the features and the label. After that, you can use the model on a customer whose future you do not
know yet.

There are two types of supervised learning:

**Classification** means the answer is a category.

```text
churned: true / false
category: billing | shipping | technical
```

**Regression** means the answer is a number.

```text
lifetime_value: £2,340
delivery_days: 4.2
```

> This method cannot work without labels, and labels cost money. Very often "we will use AI" really means
> "somebody must sit and label 5,000 rows first". Ask about this on day one.

**You probably already have labels.** Was the invoice paid? Was the ticket reopened? Which category did a
human choose? Was the delivery late? Did the user click? Your database is full of training data that you
collected by accident.

## Unsupervised: finding structure with no answers

Here nobody tells the computer what the right answer is. It only looks at the shape of the data and finds
patterns.

**Clustering** puts similar rows together.

```php
$clusters = $kmeans->fit($customers, groups: 4);

// You did NOT say what the groups are. Naming them is your job:
//   0 — high spend, low frequency
//   1 — low spend, high frequency
//   2 — new, barely active
//   3 — long tenure, high usage
```

**Anomaly detection** points out rows that do not fit the rest — fraud, a broken sensor, a bot. This is useful
because you do not need examples of fraud in advance. Most companies do not have them.

**Dimensionality reduction** takes many columns and squeezes them into a few. This is close to what embeddings
do, which you will see in module F5.

The catch: there is no correct answer to compare against. If you ask for four groups, you will always get four
groups. Deciding whether those groups mean anything is your job, not the algorithm's.

## Reinforcement: learning from consequences

Here a program (called an agent) does something, gets a reward or a punishment, and slowly learns a
**policy** — which action to take in each situation.

```text
state → action → reward → new state → …
```

This is how computers beat humans at Go, and how robots learn to walk. It is rare in business software,
because you need either a simulator or a lot of expensive trial and error in the real world.

You will meet it once in this course. **RLHF** (reinforcement learning from human feedback) is part of what
taught your model to be helpful, not just good at writing. That is module F7.

## Self-supervised: the LLM trick

Supervised learning needs labels made by humans. But to teach a machine *language*, you would need to label
more text than humanity could ever label by hand.

So here is the trick: **let the data label itself.** Take any sentence. Hide the last word. That hidden word
is now the correct answer.

```text
"The cat sat on the"        → mat
"To be or not to"           → be
"$user = User::find("       → $id
"The refund window is 30"   → days
```

No human wrote those answers. So every sentence ever written becomes a free training example, and there are
trillions of them.

Now the surprising part. Researchers did not expect this. A model that gets very good at this one boring
guessing game also picks up grammar, facts, translation, code and enough reasoning to be genuinely useful.
The reason is simple: to guess the next word well across the whole internet, you have to learn a lot about how
the world works.

> This is the most important idea in Level 0. Everything an LLM is good at, and everything it is bad at, comes
> from this one fact: it was trained to continue text. It was not trained to be correct, and not trained to be
> helpful.

## Back to the four questions

| Question | Kind | Use |
|---|---|---|
| "Which customers will cancel?" | Supervised (classification) | Gradient boosting. **Not an LLM.** |
| "What groups exist?" | Unsupervised (clustering) | k-means, DBSCAN |
| "Best email sequence?" | Reinforcement | Honestly: an A/B test |
| "Write the email" | Generative, built on self-supervised | An LLM — your job |

Only the last one is your job. But being able to recognise the other three, and say so in a meeting, helps
your team more than being able to build any of them.

## Common mistakes

- Using an LLM for a problem that is rows and columns. Gradient boosting is cheaper, faster and more accurate.
- Starting a supervised project with no labels. Getting the labels *is* the project.
- Trusting the groups just because the tool produced them. The tool always produces groups.
- Thinking an LLM "learns" from your prompts. Learning means training, and that already finished.

## You should now be able to

- [ ] Put a business question into the right one of the four kinds
- [ ] Tell classification and regression apart
- [ ] Explain self-supervised learning, and why it made LLMs possible
- [ ] Spot the hidden labelling cost inside a proposal
- [ ] Find training data that is already sitting in your own database

## Practice

1. Take three items from your backlog. Put each one in one of the four kinds. At least one will need no model
   at all.
2. Find three columns in your database that are really labels — a decision a human already made.
3. Take any sentence, delete the last five words, and try to guess them. You just did pretraining once. Now
   imagine doing it trillions of times.
