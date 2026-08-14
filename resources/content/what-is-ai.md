## Summary

- These four words are not the same thing. Each one is a smaller part of the one before it.
- Normal code: **you** write the rules. Machine learning: the computer **finds** the rules from examples.
- Some models only pick an answer from a fixed list, like "spam" or "not spam".
- Other models write something new, like an email. An LLM is this second kind.
- An LLM sometimes makes up facts. That is not a bug. It was trained to write text that *sounds* right.
- The model is already trained. You cannot change it. It knows nothing about your company.

## Four words people mix up

People use these four words as if they mean the same thing. They do not. Each one is a smaller box inside the
one before it.

```text
┌─ ARTIFICIAL INTELLIGENCE ─────────────────────────────┐
│  Any program that looks clever.                        │
│                                                        │
│  ┌─ MACHINE LEARNING ────────────────────────────┐    │
│  │  Programs that find rules from data.           │    │
│  │                                                 │    │
│  │  ┌─ DEEP LEARNING ─────────────────────┐       │    │
│  │  │  Machine learning with big           │       │    │
│  │  │  neural networks.                    │       │    │
│  │  │                                       │       │    │
│  │  │  ┌─ GENERATIVE AI ──────────┐        │       │    │
│  │  │  │  Makes new content.       │        │       │    │
│  │  │  │  ┌─ LLMs ────────────┐   │        │       │    │
│  │  │  │  │ Claude, GPT       │   │        │       │    │
│  │  │  │  └───────────────────┘   │        │       │    │
│  │  │  └───────────────────────────┘        │       │    │
│  │  └───────────────────────────────────────┘       │    │
│  └────────────────────────────────────────────────┘    │
└────────────────────────────────────────────────────────┘
```

Let us go through them one at a time.

**Artificial intelligence (AI)** is the oldest word. It means any program that does something we think is
clever. In 1997 a computer called Deep Blue beat the world chess champion. It used only rules written by
humans. There was no learning at all. It was still called AI.

**Machine learning (ML)** is smaller. Here the program is not given the rules. It finds them itself, by
looking at many examples.

**Deep learning** is smaller again. It is machine learning that uses big neural networks. You will learn what
those are in module F4.

**Generative AI** is smaller again. These models create new things — text, images, code.

**LLM** means Large Language Model. It is generative AI for text. Claude is an LLM. This is what your API key
opens.

> When someone says "we use AI", ask them: which box? A program that predicts who will cancel a subscription
> and a chatbot are both called AI. Inside, they are very different.

## Who writes the rules?

This is the most important idea in this module. Take your time with it.

### Way 1: you write the rules

You do this every day. Here is a spam filter:

```php
public function isSpam(Email $email): bool
{
    if (str_contains(strtolower($email->subject), 'viagra')) return true;
    if (str_contains($email->body, 'CLICK HERE NOW')) return true;
    if (substr_count($email->body, '!') > 10) return true;

    return false;
}
```

This is good code. You understand every line. If it makes a mistake, you can find out why.

But now the spammers change. They write "v1agra". You add a rule. They write "V I A G R A". You add another
rule. After six months this function has 400 lines. Nobody wants to touch it. And it still misses some spam.

### Way 2: the computer finds the rules

Here you do not write any rules. You give the computer many examples instead.

```php
$trainingData = [
    ['features' => $this->extract($email1), 'label' => 'spam'],
    ['features' => $this->extract($email2), 'label' => 'not_spam'],
    // …50,000 more emails that humans already sorted
];

$model = $trainer->fit($trainingData);        // the computer finds the rules

$model->predict($this->extract($newEmail));   // → 0.94, so probably spam
```

Nobody told it that many exclamation marks means spam. It found that itself. It also found thousands of other
patterns that no human would think of.

### Comparing the two ways

| | You write the rules | The computer finds them |
|---|---|---|
| Where is the logic? | In your code | In numbers the computer found |
| How do you improve it? | Edit the code | Add more examples, train again |
| Can you explain a decision? | Yes, easily | Not really |
| Do you need data? | No | Yes, a lot |
| Does it handle new cases? | No | Often yes |

Neither way is better. **Ask yourself one question: can I write the rules down?**

If yes, write them down. That code is faster, free, and easy to explain. Module F8 comes back to this. Using AI
when simple code would work is the most expensive mistake in this field.

## Two kinds of model

Inside machine learning there are two kinds. This explains why LLMs feel so different from older AI.

### Kind 1: models that choose

These models look at an input and pick an answer from a fixed list.

```text
an email          → "spam" or "not spam"
house details     → £340,000
a card payment    → 3% chance of fraud
```

They cannot say anything new. They can only choose. Almost all business AI before 2020 was this kind. Most
still is.

### Kind 2: models that create

These models make something that did not exist before.

```text
"write a haiku about queues"  → a real haiku
"a cat in a spacesuit"        → a picture
"def fibonacci(n):"           → the rest of the function
```

The first kind learns "which answer is correct?". The second kind learns "what usually comes next?" and then
writes it, one small piece at a time.

**This is why an LLM invents things.** Ask it about your refund policy. It has never seen your refund policy.
But it has seen thousands of refund policies. So it writes a normal-looking refund policy. It is not lying. It
is doing exactly what it was built to do. Module F6 shows how this works inside.

## What happens when you call Claude

When you write this:

```php
$client->messages->create(model: 'claude-sonnet-5', /* … */);
```

you send text to a model that Anthropic already trained. Three things follow from this. They shape your whole
job.

**1. You are not training anything.** The model was finished before you started. Nothing you send changes it.

**2. It knows nothing about your company.** It has never seen your database, your prices, or your policies. If
it needs to know something, you must send it in the request. This is what all of Level 1 is about.

**3. The same question can give different answers.** This makes normal testing difficult. Level 1 module 8
solves it.

## Why this matters in your job

- **When someone asks for a feature**, you can say which kind of problem it is. "Can AI predict who will
  cancel?" is not a job for an LLM.
- **When a salesperson says "our AI learns your business"**, you can ask what they actually mean. The answers
  have very different costs.
- **When an answer is wrong**, you can guess where the problem is instead of trying random fixes.
- **When you plan a budget**, you know that training costs millions, but using a model costs cents.

## Common mistakes

- Saying "AI" when you mean "LLM". Most AI is not an LLM.
- Thinking machine learning is always better than simple code. A rule that is always right beats a model that
  is right 97 times out of 100.
- Thinking the model learns from your users. It does not.
- Believing "AI-powered" without asking which box. Sometimes there is no AI inside at all.

## You should now be able to

- [ ] Draw the four boxes and name a real product in each
- [ ] Explain the difference between writing rules and finding rules
- [ ] Say why an LLM makes things up
- [ ] Say the three things that follow from using a model somebody else trained
- [ ] Notice a problem that does not need AI

## Practice

1. Pick five features in your app. For each one, ask: rules, or machine learning?
2. Find a product page that says "AI-powered". Which box is it really?
3. Look at the spam filter above. Write the rules to catch "v1agra", "V-I-A-G-R-A" and "\/iagra". Now imagine
   doing this for every word in the dictionary. This is the moment machine learning starts to look useful.
