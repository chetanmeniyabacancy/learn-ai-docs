## Summary

- A network only multiplies numbers. Text must become numbers first.
- **Tokenization** splits text into subword pieces. Common words = 1 token, rare words split up.
- Code and non-English text cost more tokens for the same length.
- Token IDs are arbitrary, so they cannot be used directly. **Embeddings** replace them: a few hundred learned
  numbers that carry meaning.
- **Static** embedding = one vector per word forever. **Contextual** = the vector depends on the sentence.

## Step 1: tokenization

Chop the text into pieces the model has seen before.

Two obvious ideas both fail:

**Split by character.** Tiny vocabulary, but `"cat"` becomes three meaningless parts and sequences get huge.

**Split by word.** Intuitive, but the vocabulary never ends. Every typo, name and product code is new, and an
unseen word cannot be represented at all.

**What is actually used: subword tokenization** (BPE). Start from characters, repeatedly merge the most common
pair, until you have a fixed vocabulary of about 30,000–100,000 pieces.

Common words become one token. Rare words split into familiar parts.

```text
"where is my order"   → ["where", " is", " my", " order"]         4 tokens
"unbelievable"        → ["un", "bel", "iev", "able"]              4 tokens
"ORD-1043"            → ["ORD", "-", "104", "3"]                  4 tokens
"$user->getName();"   → ["$", "user", "->", "get", "Name", "();"] 6 tokens
"やあ"                 → ["や", "あ"]                              2 tokens
```

Three results you will feel:

- **Nothing is unrepresentable.** Typos and new product names still work.
- **Code and non-English text cost more.** `$user->getName();` is 17 characters and 6 tokens.
  `"where is my order"` is 17 characters and 4. That is why Level 1 says measure, do not guess.
- **Letter tasks are hard.** "How many r's in strawberry?" is awkward because the model sees
  `["str", "aw", "berry"]`, not letters. Not stupidity — a result of the representation.

Each token maps to an integer:

```text
"where" → 3595    " is" → 374    " my" → 856    " order" → 2015
```

So the sentence is `[3595, 374, 856, 2015]`. Numbers — but bad ones.

## Step 2: why IDs are not enough

Token 3595 and 3596 are neighbours by accident. The numbering means nothing. Feed raw IDs to a network and you
have claimed 3596 is "one more than" 3595, which is nonsense.

The first fix people try is **one-hot**: a vector as long as the vocabulary, all zeros except one 1.

```text
vocabulary of 50,000

"cat"  → [0, 0, 0, …, 1, …, 0, 0]
"dog"  → [0, 0, 1, …, 0, …, 0, 0]
```

This removes the fake ordering but adds two problems:

- **Huge and nearly all zeros.** 50,000 numbers per token.
- **Every word is equally unrelated to every other.** "cat" to "dog" is the same distance as "cat" to
  "bureaucracy". All meaning is gone.

## Step 3: embeddings

Use a few hundred numbers instead — and *learn* them.

```text
"cat"     → [ 0.21, -0.44,  0.88, …]   512 numbers
"dog"     → [ 0.19, -0.41,  0.85, …]   ← close to "cat"
"invoice" → [-0.72,  0.13, -0.09, …]   ← far away
```

Nobody assigns those numbers. They are parameters, learned by gradient descent (module F3) under one pressure:
predict the next token well. Words used in similar places end up with similar vectors, because that helps.

The geometry ends up meaningful:

```text
vector("king") - vector("man") + vector("woman")     ≈ vector("queen")
vector("Paris") - vector("France") + vector("Japan") ≈ vector("Tokyo")
```

Nobody built that in. It fell out of learning to predict text.

**This is the same object as Level 1 module 5.** There you use embeddings to search documents by meaning. Here
you see where they come from.

## Static vs contextual

**Static embeddings** (word2vec, GloVe) give each word one vector forever. So "bank" has one vector serving
both:

```text
"I sat on the river bank"
"I need to call the bank about my mortgage"
```

One vector, two meanings, permanently blurred.

**Contextual embeddings** — what transformers produce — compute each token's vector *from the sentence it is
in*. The two "bank"s get different vectors.

That is what attention does. Module F6.

## The full journey

```text
"where is my order"
        │  tokenize
[["where"], [" is"], [" my"], [" order"]]
        │  look up IDs
[3595, 374, 856, 2015]
        │  embedding lookup (learned table of vocab × 512)
[[0.21, -0.44, …], [0.03, 0.91, …], …]
        │  transformer layers — attention mixes them (module F6)
contextual vectors
        │  final layer
next-token probabilities
```

After the third arrow it is all arithmetic. The string is gone.

## What this explains at work

- Why token counts look odd, and why code costs more.
- Why the API bills per token — it is the literal unit of computation.
- Why "count the letters" fails but "summarise this" works.
- Why embeddings can search by meaning (Level 1 module 5).
- Why changing embedding model means reindexing everything: different model, different learned space, numbers
  not comparable.

## Common mistakes

- Estimating tokens from word count on code or non-English text.
- Mixing embedding models in one index. Two spaces, silently incomparable.
- Expecting letter-level reasoning.
- Thinking an embedding is a compressed copy of the text. It represents usage, not content. You cannot decode
  it back.

## You should now be able to

- [ ] Explain subword tokenization and why it beats characters and words
- [ ] Say why raw token IDs cannot be fed to a network
- [ ] Explain what an embedding is and where the numbers come from
- [ ] Tell static from contextual using the "bank" example
- [ ] Trace a string to next-token probabilities

## Practice

1. Run the tokenization example. Guess the token count for a URL, a code snippet and a non-English sentence
   before revealing it.
2. Take a real prompt from your app and work out its cost from its token count.
3. Write two sentences using "charge" in different senses. A static embedding gives both the same vector.
   Convince yourself why that is worth fixing.
