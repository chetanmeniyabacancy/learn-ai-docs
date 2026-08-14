## Summary

- A model is just **numbers**. Training means changing those numbers until the model stops being wrong.
- **Loss** is one score for how wrong the model is. Like golf: lower is better.
- To improve, the computer asks "should I make this number bigger or smaller?" Then it changes it a little.
- It repeats this thousands of times. That is all training is.
- **Learning rate** is how big each change is. Too small is slow. Too big breaks everything.
- **Training** = changing the numbers. **Using** = only reading them. Your API calls only read.
- **Overfitting** = the model memorises the answers instead of learning the pattern.

## The question this module answers

People say "the model learns from data". That explains nothing.

What actually changes? If you paused training and saved the model to a file, what would be inside that file?

The answer is short. **Numbers.** That is it.

This module shows you exactly how those numbers get better. We will use a tiny example you can follow with a
calculator. The same method trains Claude.

## A model is just numbers

Let us build the smallest possible model.

**The job:** guess how many days a delivery will take, based on distance.

Our model has two numbers. Think of them as two dials you can turn.

```php
$w = 0.0;   // dial 1: how much distance matters
$b = 0.0;   // dial 2: the starting point

// The model. That is the whole thing.
function predict(float $distance, float $w, float $b): float
{
    return $w * $distance + $b;
}
```

Right now both dials are at zero. So the model says every delivery takes **0 days**. That is wrong, but the
model is not broken. It just has bad numbers.

**Training means turning those two dials until the guesses are good.**

Here is our real data. Distance is in units of 100km.

| Distance | Real days |
|---|---|
| 1 | 2.0 |
| 2 | 2.8 |
| 3 | 4.1 |
| 4 | 4.9 |
| 5 | 6.2 |

## Step 1: How wrong are we?

Before we can improve, we need to measure how bad we are. One number for the whole model.

With both dials at zero, the model guesses 0 every time:

| Distance | Model guess | Real | Wrong by | Wrong by, squared |
|---|---|---|---|---|
| 1 | 0.0 | 2.0 | 2.0 | 4.00 |
| 2 | 0.0 | 2.8 | 2.8 | 7.84 |
| 3 | 0.0 | 4.1 | 4.1 | 16.81 |
| 4 | 0.0 | 4.9 | 4.9 | 24.01 |
| 5 | 0.0 | 6.2 | 6.2 | 38.44 |

Add the last column and divide by 5. That gives **18.22**.

This number is called the **loss**. Think of it like a golf score: **lower is better, and 0 is perfect.**

```php
function loss(array $data, float $w, float $b): float
{
    $total = 0.0;

    foreach ($data as [$distance, $realDays]) {
        $wrongBy = predict($distance, $w, $b) - $realDays;
        $total += $wrongBy * $wrongBy;      // multiply by itself
    }

    return $total / count($data);           // the average
}
```

**Why multiply the error by itself?** Two reasons, both simple.

1. It removes minus signs. Being 2 days early and 2 days late are both mistakes. Without this they would
   cancel each other out and look perfect.
2. It makes big mistakes count much more. Being wrong by 6 gives 36. Being wrong by 2 gives only 4. So the
   model fixes its worst mistakes first.

Now watch the loss drop as we turn the dials to better numbers:

| Dial 1 (`w`) | Dial 2 (`b`) | Loss |
|---|---|---|
| 0 | 0 | 18.22 |
| 1 | 0 | 1.02 |
| 1.5 | 0 | 0.67 |
| **1.055** | **0.832** | **0.0151** ← best |

Our goal is to find those last two numbers automatically.

## Step 2: Which way should we turn the dial?

Trying every possible pair of numbers is impossible. There are infinite combinations. And a real model has
billions of dials, not two.

So we do something smarter.

**Imagine you are standing on a hill in thick fog.** You want to reach the bottom of the valley. You cannot
see anything.

But you can feel the ground under your feet. You can feel which direction goes **down**. So you take one small
step that way. Then you feel again. Then you step again.

Do this enough times and you reach the bottom, even though you never saw the valley.

That is exactly what training does.

- The **hill** is the loss.
- The **bottom of the valley** is the best numbers.
- **Feeling which way is down** is called the **gradient**.
- Each step is one small change to the dials.

```php
// This tells us: should each dial go up or down, and by how much?
function gradients(array $data, float $w, float $b): array
{
    $forW = 0.0;
    $forB = 0.0;

    foreach ($data as [$distance, $realDays]) {
        $wrongBy = predict($distance, $w, $b) - $realDays;

        $forW += 2 * $wrongBy * $distance;
        $forB += 2 * $wrongBy;
    }

    return [$forW / count($data), $forB / count($data)];
}
```

**You do not need to understand that maths.** You only need the idea:

> If making a dial bigger makes the loss worse, turn that dial down instead.

## Step 3: How big should each step be?

Now we know the direction. How far do we move?

This is called the **learning rate**. It is one number you choose, usually something small like `0.01`.

Back to the foggy hill:

- **Tiny steps** (0.0001) — you will get there, but it takes forever.
- **Good steps** (0.01) — you walk down nicely and reach the bottom.
- **Huge steps** (0.5) — you leap over the valley and land higher up the other side. Then you leap back, even
  higher. You keep bouncing until the numbers become too big for the computer.

Here is what that last one really looks like:

```text
too small (0.0001)   18.22 → 18.14 → 18.06 …              correct, but far too slow
good (0.01)          18.22 → 10.64 → 1.28 → 0.015         reaches the bottom
too big (0.5)        18.22 → 2,130 → 249,853 → 29 million  bounces higher and higher
```

If you ever see a training loss turn into `NaN`, this is almost always why.

## The whole loop

Now put the three steps together. This is all of training.

```php
$w = 0.0;
$b = 0.0;
$learningRate = 0.01;

// One "epoch" means one pass through all the data.
for ($epoch = 1; $epoch <= 1000; $epoch++) {

    // 1. Which way is downhill?
    [$forW, $forB] = gradients($data, $w, $b);

    // 2. Take one small step that way.
    //    The minus sign is what makes it go DOWN instead of up.
    $w -= $learningRate * $forW;
    $b -= $learningRate * $forB;
}
```

And here is what really happens when you run it:

```text
epoch    0   loss 18.2200   w 0.000   b 0.000    ← guesses 0 days for everything
epoch    1   loss 10.6441   w 0.282   b 0.080
epoch    5   loss  1.2827   w 0.882   b 0.254
epoch  200   loss  0.0277   w 1.123   b 0.586
epoch  600   loss  0.0158   w 1.069   b 0.782
epoch 1000   loss  0.0151   w 1.055   b 0.832    ← the bottom of the valley
```

The model ended up with `days = 1.055 × distance + 0.832`.

**Nobody wrote that formula.** The computer found it by asking "which way is down?" a thousand times.

## This is how Claude was trained too

That loop is not a simple version. It is the real thing.

Claude was trained with the same three steps: measure how wrong, find the downhill direction, take a small
step. Repeat.

What is different:

| | Our example | Claude |
|---|---|---|
| Dials (parameters) | 2 | hundreds of billions |
| The `predict()` function | one line | a transformer (module F6) |
| How wrong is measured | squared error | a similar idea, for words |
| How long it takes | one millisecond | months, on thousands of computers |

The shape is identical. Only the size changed.

## Training and using are two different things

This matters, and people mix it up.

| | Training | Using it (inference) |
|---|---|---|
| What happens to the numbers | They change | They are only read |
| How often | Once, at the start | Every request |
| Who does it | Anthropic | You |
| Cost | Millions of dollars | Fractions of a cent |

**Every call you make to Claude is the second column.** The numbers are locked. Nothing you send changes them.

This is why "the model will learn from our users" is false. And it is why anything the model needs to know has
to be sent inside your request. That single fact is the reason RAG exists (Level 1 module 7).

## The big danger: memorising instead of learning

Imagine a student preparing for an exam.

**Student A** learns the subject. In the exam they see new questions and answer them well.

**Student B** memorises last year's answer sheet. On last year's paper they score 100%. On this year's paper
they fail completely.

Models do this too. Give a model enough dials and it will memorise your data instead of learning the pattern.
This is called **overfitting**.

```text
                  score on data      score on data
                  it trained on      it never saw
too simple             8.4                8.9      has not learned enough
good                   0.3                0.4      learned the pattern
memorised              0.001             14.2      learned nothing useful
```

Look at that last row carefully. On its training data it looks like the best model you have ever built. That
is what makes it dangerous.

**The fix is simple: hide some data from the model before you start.**

```php
$shuffled = collect($data)->shuffle();

$train      = $shuffled->take(700);         // the model learns from this
$validation = $shuffled->slice(700, 150);   // you adjust settings using this
$test       = $shuffled->slice(850);        // look at this ONCE, at the very end
```

If you keep checking the test set while adjusting things, you have spoiled it. Your final score becomes a lie.

Level 1 module 8 uses the same rule for AI prompts.

## Common mistakes

- **Judging a model by its score on training data.** Of course it does well there. It has seen the answers.
- **Never checking the learning rate.** Too small wastes days. Too big gives you `NaN`.
- **Looking at the test data too early.** Once you tune against it, it stops being a fair test.
- **Thinking using a model changes it.** It does not. Ever.
- **Adding more dials to fix mistakes.** That usually buys memorising, not understanding.

## You should now be able to

- [ ] Say what is inside a trained model file
- [ ] Explain loss as a score where lower is better
- [ ] Describe training as walking downhill in fog
- [ ] Say what the learning rate does, and what happens when it is too big
- [ ] Explain why every API call only reads the numbers
- [ ] Spot memorising from two scores

## Practice

1. Open this module's example. Look at epoch 1, then epoch 5. Watch dial 1 climb from 0 towards 1.
2. Change the learning rate to `0.5` and run it again. Watch the numbers explode. Worth seeing once.
3. Add a strange row to the data, like `[5.0, 40.0]`. Run it again. See how one bad row pulls everything off.
   This is why cleaning data matters.
