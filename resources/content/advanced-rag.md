## Summary

- When RAG gives wrong answers, the **search** is usually the problem, not the prompt.
- **Rewrite the question** into better search words. Cheap, and it helps the most.
- **Use both searches together**: keyword search for codes, vector search for meaning.
- **Rerank**: pick 50 possible answers cheaply, then let a smarter model choose the best 4.
- Measure recall after every change. No number means you are guessing.

## The problem

You test your RAG system with 50 real questions. In 20 of them the answer is "I could not find that" — even
though the answer is definitely in your documents.

So recall is 60%. That means **60% is your best possible score**. You can write a perfect prompt and still fail
4 questions out of 10, because the right paragraph never reached the model.

Fixing the prompt cannot help here. You have to fix the search.

## Four reasons search misses

| The question | Why it fails | The fix |
|---|---|---|
| "what about that 30 day thing?" | Vague words | Rewrite the question |
| "is ORD-1043 under warranty?" | Codes confuse vectors | Add keyword search |
| "how do I cancel and get money back?" | Two questions in one | Split it |
| "what is the process?" | Chunk starts in the middle | Better chunking |

## Fix 1: rewrite the question

Users do not write good search queries. So ask a cheap model to write them for you.

```php
// Haiku, costs about $0.0002. Do this before searching.
$queries = $claude->extract(
    system: 'Rewrite this question as 2 or 3 search queries for a company handbook. '
          . 'Add the words the policy is likely to use. Remove chat filler.',
    input: $question,
    schema: ['type' => 'object', 'properties' => [
        'queries' => ['type' => 'array', 'items' => ['type' => 'string']],
    ], 'required' => ['queries'], 'additionalProperties' => false],
    model: config('claude.fast_model'),
);
```

```text
"what about that 30 day thing?"
   → "returns policy 30 day window"
   → "how long after buying can I return an item"
```

Now search with both and put the results together.

## Fix 2: use both kinds of search

Keyword search and vector search fail in **opposite** ways, so run both.

| Question | Keyword search | Vector search |
|---|---|---|
| `ORD-1043` | perfect | poor — ORD-1043 and ORD-1044 look almost the same |
| "I cannot log in" | finds nothing | finds "reset your password" |

To join two result lists, do not try to compare their scores — the scales are different. Just use the
**position** in each list:

```php
// Reciprocal rank fusion. Position 0 scores 1/61, position 1 scores 1/62.
foreach ($rankings as $ranking) {
    foreach (array_values($ranking) as $position => $id) {
        $scores[$id] = ($scores[$id] ?? 0) + 1 / (60 + $position + 1);
    }
}
```

A document found by **both** searches rises to the top. That is the whole idea.

## Fix 3: reranking

This gives the biggest jump in quality, and few people know about it.

A vector search compares two things that were turned into numbers **separately** — your question never met the
document. A **reranker** reads the question and one paragraph **together** and scores how well that paragraph
answers that question. Much more accurate, but too slow for a million documents.

So use both:

```text
100,000 chunks
   ↓  cheap search (keyword + vector), fast
 top 50
   ↓  reranker reads each one with the question
 top 4  →  goes into the prompt
```

Voyage, Cohere and others sell rerankers, and free models exist. Getting the right paragraph into the top 50 is
an easy job. Choosing between 50 is where a reranker shines.

## Measure every change

Each fix costs money or time, so prove it earns its place.

```text
                        recall@4   cost/question
basic vector search       0.62       $0.0004
+ rewrite the question    0.78       $0.0006
+ keyword search too      0.86       $0.0006
+ reranking 50 → 4        0.94       $0.0018
```

Now you can decide with numbers instead of opinions. Maybe 8 more points of recall is worth 3× the cost. Maybe
not. That is a real conversation, not a guess.

## Common mistakes

- **Fixing the prompt when the search is broken.** The most expensive mistake in RAG.
- **Adding everything at once.** Then you cannot tell what helped, or what to remove.
- **Asking for more chunks instead of better ones.** Ten weak paragraphs answer worse than three good ones.
- **Using your best model to rewrite queries.** That is a small job for a cheap model.

## You should now be able to

- [ ] Say which of the four reasons caused a bad answer
- [ ] Rewrite a question with a cheap model before searching
- [ ] Join keyword and vector results by position
- [ ] Explain why a reranker beats a vector score
- [ ] Show a recall number for every change you made

## Practice

1. Take your 20 worst questions. Write down which of the four problems each one has.
2. Add question rewriting only. Measure recall@4 before and after.
3. Add keyword search and join the lists. Measure again.
4. Try a reranker on 50 results. Keep it only if the number justifies the cost.
