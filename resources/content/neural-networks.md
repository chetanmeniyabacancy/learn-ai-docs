## Summary

- A neuron = multiply inputs by weights, add them up, bend the result. Three lines of code.
- The **bend** (activation function) is essential. Without it, any depth collapses to one straight line.
- **XOR** cannot be solved by one neuron. That is why hidden layers exist.
- **Backpropagation** = pass the error backwards so every layer knows its share of the blame.
- "70 billion parameters" = 70 billion numbers to multiply per token. That is the cost and speed trade-off.

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

1. **Multiply** each input by its weight — how much that input matters.
2. **Add** them up, plus a bias — the neuron's baseline.
3. **Bend** the result through an activation function.

The weights and bias are the parameters. Gradient descent adjusts them (module F3). A neuron with 5 inputs
stores 6 numbers.

## Why the bend matters

This step is usually skipped, and it is why deep learning works at all.

Without step 3 a neuron is `w·x + b` — a straight line. Stack two, still a line. Stack a thousand, still a
line. A million-layer network with no activation is exactly as powerful as one line of algebra.

The bend breaks that. It lets stacked layers make curves and corners.

| Activation | Formula | Where |
|---|---|---|
| **ReLU** | `max(0, x)` | The workhorse. Default hidden layer. |
| **Sigmoid** | `1 / (1 + e^-x)` | Squashes to 0–1. Binary output. |
| **Tanh** | `tanh(x)` | Squashes to −1–1. |
| **GELU** | smooth ReLU | What transformers use. |
| **Softmax** | normalise to sum 1 | Scores → probabilities. Module F6. |

ReLU is just "if negative, zero", and it is most of modern deep learning.

## From one neuron to a network

**A layer** = several neurons reading the same inputs.
**A network** = layers in sequence, each reading the last one's output.

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

**Depth** = number of layers. That is what "deep learning" means. Early layers learn simple things; later
layers combine them. In an image network: layer 1 finds edges, the middle finds shapes, the end finds faces.
Nobody programmed that. It emerges from training.

Claude has hundreds of billions of parameters in many layers. Each one does the arithmetic above.

## Why one layer is not enough: XOR

One neuron can learn AND, and OR:

```text
AND                    OR
0,0 → 0                0,0 → 0
0,1 → 0                0,1 → 1
1,0 → 0                1,0 → 1
1,1 → 1                1,1 → 1
```

Both can be split by one straight line. A neuron *is* a straight line, so it can learn them.

XOR cannot:

```text
XOR                      1 │  ●(1)      ○(0)
0,0 → 0                    │
0,1 → 1                    │
1,0 → 1                  0 │  ○(0)      ●(1)
1,1 → 0                    └─────────────────
                              0            1

                 No single straight line separates ● from ○.
```

In 1969 this nearly killed neural network research for a decade. The fix is one hidden layer: it reshapes the
space, and in the new space a line *does* work.

This module's example shows the single neuron stuck at 50% — a coin flip — and the two-layer version at 100%.

## Backpropagation, without calculus

Module F3 nudged two parameters. A network has millions across layers. How does a weight in layer 1 know how
much it caused an error at the output?

**Backpropagation**: compute the error at the output, pass it backwards layer by layer, work out each
parameter's share of the blame. Then every parameter steps downhill, as before.

```text
forward   inputs → layer 1 → layer 2 → output → loss
backward                 ← ← ← ← ← ← ← blame
update    every weight steps against its own gradient
```

You will never implement this — PyTorch and friends do it automatically. Just hold the model: **it is still
the same loop from module F3, with blame shared across layers.**

## What this buys you

You will not build one of these at work. Knowing the mechanism still changes four things:

- **"70 billion parameters"** = 70 billion numbers, learned by gradient descent, that must fit in GPU memory
  and be multiplied per token. That is why bigger models cost more and run slower.
- **"Why can't it just be accurate?"** There is no lookup table. The answer is computed through billions of
  weights. There is nothing to look a fact up *in*.
- **Explainability is genuinely hard**, not laziness. A decision is spread over billions of numbers. That is
  why regulated work often still uses logistic regression.
- **Model size vs task** makes sense. Haiku has fewer parameters: cheaper, faster, weaker at hard reasoning.
  That is Level 1's model table.

## Common mistakes

- Thinking neurons are like brain cells. It is a 1940s analogy. It is a weighted sum with a bend.
- Forgetting the activation function. Without it, depth is worthless.
- Assuming deeper is always better. More layers = more data needed, more overfitting.
- Expecting to read the weights. You cannot look at neuron 4,201 and learn what it means.

## You should now be able to

- [ ] Write a neuron in three lines and name its parameters
- [ ] Explain why no activation = one line of algebra
- [ ] Use XOR to explain hidden layers
- [ ] Describe backpropagation as blame flowing backwards
- [ ] Say what "70 billion parameters" means for cost and speed

## Practice

1. Run the perceptron example. Watch weights go from noise to solving AND.
2. Point it at XOR. Watch it stall at 50%.
3. Count the parameters in a 3 → 4 → 1 network by hand. (3×4 + 4 + 4×1 + 1 = 21.) Now scale to 70 billion.
