## Summary

- A network can only multiply numbers. So text must be turned into numbers first.
- **Tokenization** cuts text into small pieces. Common words stay as one piece. Rare words break into parts.
- Code and non-English text use more tokens for the same amount of text.
- The ID number of a token means nothing by itself, so it cannot go straight into the network. **Embeddings** replace it with a few hundred learned numbers that carry meaning.
- A **static** embedding gives a word the same numbers forever. A **contextual** embedding changes with the sentence around the word.

## Step 1: tokenization

The first job is to cut the text into pieces the model already knows.

Two obvious ideas both fail:

**Cut by single character.** The list of possible pieces is tiny, which sounds good. But `"cat"` becomes three
meaningless pieces, and sentences turn into very long lists.

**Cut by whole word.** This feels natural, but the list of words never ends. Every typo, name and product code
is a new word, and a word the model has never seen cannot be handled at all.

**What real models use is subword tokenization** (the common method is called BPE). It starts from single
characters, then keeps joining the most common pair of pieces, again and again, until it has a fixed list of
about 30,000 to 100,000 pieces.

The result is nice: common words become one piece, and rare words break into familiar parts.

```text
"where is my order"   → ["where", " is", " my", " order"]         4 tokens
"unbelievable"        → ["un", "bel", "iev", "able"]              4 tokens
"ORD-1043"            → ["ORD", "-", "104", "3"]                  4 tokens
"$user->getName();"   → ["$", "user", "->", "get", "Name", "();"] 6 tokens
"やあ"                 → ["や", "あ"]                              2 tokens
```

Three effects of this show up in your daily work:

- **Nothing is impossible to handle.** Typos and brand-new product names still work, because they break into
  smaller known pieces.
- **Code and non-English text cost more.** `$user->getName();` is 17 characters but 6 tokens, while
  `"where is my order"` is also 17 characters but only 4 tokens. This is why Level 1 tells you to measure
  tokens instead of guessing.
- **Letter-counting questions go wrong.** "How many r's in strawberry?" is hard for the model because it sees
  `["str", "aw", "berry"]`, not individual letters. The model is not being stupid. It simply cannot see what
  you can see.

Each piece is then swapped for a number:

```text
"where" → 3595    " is" → 374    " my" → 856    " order" → 2015
```

So the sentence becomes `[3595, 374, 856, 2015]`. Those are numbers, but they are poor ones. Here is why.

## Step 2: why IDs are not enough

Token 3595 and token 3596 sit next to each other only by accident. The numbering carries no meaning. If you
feed these raw IDs into a network, you are telling it that 3596 is "one more than" 3595, like 4 is one more
than 3. That is nonsense, and it will learn nonsense from it.

The first fix people try is called **one-hot**. You make a list as long as the whole vocabulary, put zero
everywhere, and put a single 1 in the position of your word.

```text
vocabulary of 50,000

"cat"  → [0, 0, 0, …, 1, …, 0, 0]
"dog"  → [0, 0, 1, …, 0, …, 0, 0]
```

This does remove the fake ordering, but it creates two new problems:

- **It is enormous and almost empty.** You need 50,000 numbers to represent one token.
- **Every word is equally unrelated to every other word.** The distance from "cat" to "dog" is exactly the same
  as the distance from "cat" to "bureaucracy". All meaning has disappeared.

## Step 3: embeddings

The real solution is to use only a few hundred numbers per token, and to *learn* those numbers during training.

```text
"cat"     → [ 0.21, -0.44,  0.88, …]   512 numbers
"dog"     → [ 0.19, -0.41,  0.85, …]   ← close to "cat"
"invoice" → [-0.72,  0.13, -0.09, …]   ← far away
```

Nobody chooses these numbers by hand. They are parameters, and gradient descent adjusts them (module F3) with
only one goal: guess the next token well. Words that get used in similar places slowly end up with similar
numbers, because that similarity helps the model guess better.

Once the numbers are learned, the distances between them start to make sense:

```text
vector("king") - vector("man") + vector("woman")     ≈ vector("queen")
vector("Paris") - vector("France") + vector("Japan") ≈ vector("Tokyo")
```

Nobody built these relationships in. They appeared as a side effect of learning to predict text.

**These are the same embeddings as Level 1 module 5.** There, you use them to search your documents by
meaning. Here, you see where they come from.

## Static vs contextual

**Static embeddings** (older methods like word2vec and GloVe) give each word one fixed set of numbers forever.
So the word "bank" gets one vector that has to serve both of these sentences:

```text
"I sat on the river bank"
"I need to call the bank about my mortgage"
```

One vector, two completely different meanings, mixed together permanently.

**Contextual embeddings** are what transformers produce. Here each token's numbers are calculated *from the
sentence it appears in*. So the two "bank"s get different numbers, and the river never gets confused with the
mortgage.

Attention is the mechanism that does this. That is module F6.

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

After the third arrow, everything is arithmetic. Your original string no longer exists.

## What this explains at work

- Why token counts look strange, and why code costs more than normal text.
- Why the API charges per token — a token is literally the unit of work.
- Why "count the letters in this word" fails, while "summarise this page" works well.
- Why embeddings can search by meaning (Level 1 module 5).
- Why changing your embedding model means rebuilding the whole index. A different model learned a different set
  of numbers, so old and new vectors cannot be compared.

## Common mistakes

- Guessing token counts from word counts, especially for code or non-English text.
- Mixing two embedding models in one index. The numbers look fine but mean different things.
- Expecting the model to reason letter by letter.
- Thinking an embedding is a squeezed copy of the text. It records how the text is *used*, not what it says.
  You cannot turn it back into the original words.

## You should now be able to

- [ ] Explain subword tokenization, and why it beats characters and whole words
- [ ] Say why raw token IDs must not be fed into a network
- [ ] Explain what an embedding is and where its numbers come from
- [ ] Tell static and contextual embeddings apart using the "bank" example
- [ ] Follow a string all the way to next-token probabilities

## Practice

1. Run the tokenization example. Before you see the answer, guess the token count for a URL, a code snippet
   and a non-English sentence.
2. Take a real prompt from your app, count its tokens, and work out its cost.
3. Write two sentences that use "charge" in different senses. A static embedding gives both the same numbers.
   Convince yourself why that is a problem worth fixing.
