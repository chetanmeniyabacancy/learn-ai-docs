## Summary

- One neuron does three small things: multiply each input by a weight, add everything up, then bend the result. It is three lines of code.
- The **bend** (called an activation function) is the important part. Without it, even a thousand layers behave like one straight line.
- One neuron cannot solve **XOR**. That limit is the reason hidden layers exist.
- **Backpropagation** means sending the error backwards through the layers, so every layer learns how much of the mistake was its fault.
- "70 billion parameters" means 70 billion numbers must be multiplied for every token. That is why big models cost more and reply slower.

## A neuron is three lines

```php
function neuron(array $inputs, array $weights, float $bias): float
{
    $sum = $bias;

    foreach ($inputs as $i => $input) {
        $sum += $input * $weights[$i];
    }

    return max(0, $sum);      // the bend
}
```

1. **Multiply** each input by its weight. The weight says how important that input is.
2. **Add** the results together, plus a bias. The bias is the neuron's starting point.
3. **Bend** the total using an activation function.

The weights and the bias are the parameters. They are the numbers that training changes, using the same
downhill method from module F3. A neuron with 5 inputs holds 6 numbers: five weights and one bias.

## Why the bend matters

People usually skip this step, but it is the reason deep learning works at all.

Without step 3, a neuron is only `w·x + b`, which is a straight line. Put two of them together and you still
have a straight line. Put a thousand together and you *still* have a straight line. So a network with a
million layers and no bend is exactly as powerful as one line of school algebra.

The bend removes that limit. Once each layer bends its output a little, stacked layers can make curves and
corners, and curves can describe real-world data.

| Activation | Formula | Where |
|---|---|---|
| **ReLU** | `max(0, x)` | The workhorse. Default hidden layer. |
| **Sigmoid** | `1 / (1 + e^-x)` | Squashes to 0–1. Binary output. |
| **Tanh** | `tanh(x)` | Squashes to −1–1. |
| **GELU** | smooth ReLU | What transformers use. |
| **Softmax** | normalise to sum 1 | Scores → probabilities. Module F6. |

ReLU only says "if the number is negative, make it zero". That tiny rule is inside most of modern deep
learning.

## From one neuron to a network

**A layer** is several neurons that all read the same inputs.
**A network** is layers placed one after another, where each layer reads the output of the layer before it.

```text
   inputs         hidden layer      output

  tenure ──┐      ┌──○──┐
           ├──────┤     ├────○──→  0.87
  logins ──┤      ├──○──┤          churn probability
           ├──────┤     │
  tickets ─┘      └──○──┘

  3 inputs      3 neurons        1 neuron
                (3×3 + 3 = 12)   (3 + 1 = 4)

                    16 parameters total
```

**Depth** means how many layers you have. That word is where "deep learning" comes from. The early layers
learn simple things, and the later layers join those simple things into bigger ideas. In a network that looks
at photos, the first layer finds edges, the middle layers find shapes, and the last layers find faces. Nobody
programmed that order. It appears on its own during training.

Claude has hundreds of billions of parameters spread over many layers. Every single one is doing the small
multiply-add-bend above.

## Why one layer is not enough: XOR

A single neuron can learn AND, and it can learn OR:

```text
AND                    OR
0,0 → 0                0,0 → 0
0,1 → 0                0,1 → 1
1,0 → 0                1,0 → 1
1,1 → 1                1,1 → 1
```

In both cases you can draw one straight line that separates the 0 answers from the 1 answers. A neuron *is*
basically a straight line, so it can learn both.

XOR is different:

```text
XOR                      1 │  ●(1)      ○(0)
0,0 → 0                    │
0,1 → 1                    │
1,0 → 1                  0 │  ○(0)      ●(1)
1,1 → 0                    └─────────────────
                              0            1

                 No single straight line separates ● from ○.
```

No straight line can separate them. When this was proved in 1969, research on neural networks almost stopped
for ten years. The fix is to add one hidden layer. That layer rearranges the data into a new shape, and in
that new shape a straight line *does* work.

The example for this module shows a single neuron stuck at 50% accuracy, which is the same as tossing a coin,
and the two-layer version reaching 100%.

## Backpropagation, without calculus

In module F3 we adjusted only two numbers. A real network has millions of them, spread across layers. So how
does a weight in layer 1 know how much it caused a mistake at the very end?

**Backpropagation** is the answer. First you measure the error at the output. Then you pass that error
backwards, layer by layer, working out how much each parameter is responsible for it. After that, every
parameter takes one small step downhill, exactly like before.

```text
forward   inputs → layer 1 → layer 2 → output → loss
backward                 ← ← ← ← ← ← ← blame
update    every weight steps against its own gradient
```

You will never write this yourself. PyTorch and similar libraries do it for you. Just keep the picture in your
head: **it is still the same loop from module F3, with the blame divided between the layers.**

## What this buys you

You will not build a network at work. But understanding the machinery changes how you answer four common
questions:

- **"What does 70 billion parameters mean?"** It means 70 billion learned numbers that must sit in GPU memory
  and be multiplied for every token. That is exactly why bigger models cost more and run slower.
- **"Why can't it just be accurate?"** Because there is no table of facts inside. The answer is calculated
  through billions of weights. There is nothing to look a fact up *in*.
- **"Why can't you explain the decision?"** Because the decision is spread across billions of numbers. This is
  a real technical limit, not laziness. It is why banks and insurers often still use logistic regression.
- **"Why choose a smaller model?"** Haiku has fewer parameters, so it is cheaper and faster, but weaker at hard
  reasoning. That is the trade-off behind Level 1's model table.

## Common mistakes

- Thinking a neuron works like a brain cell. That comparison is from the 1940s. It is really just a weighted
  sum with a bend.
- Forgetting the activation function. Without it, extra layers add nothing.
- Assuming that deeper is always better. More layers need more data and memorise more easily.
- Expecting to read meaning from the weights. You cannot open neuron 4,201 and see what it stands for.

## You should now be able to

- [ ] Write a neuron in three lines and name its parameters
- [ ] Explain why a network with no activation is only one line of algebra
- [ ] Use XOR to explain why hidden layers are needed
- [ ] Describe backpropagation as blame travelling backwards
- [ ] Say what "70 billion parameters" means for cost and speed

## Practice

1. Run the perceptron example. Watch the weights start as random noise and end up solving AND.
2. Point the same code at XOR. Watch it get stuck at 50%.
3. Count the parameters in a 3 → 4 → 1 network by hand. (3×4 + 4 + 4×1 + 1 = 21.) Now imagine 70 billion.
