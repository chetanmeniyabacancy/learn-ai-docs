## Summary

- An **embedding** turns text into a vector so similar *meanings* land close together.
- **Cosine similarity** measures closeness. Ten lines of PHP.
- **Anthropic has no embeddings endpoint.** Use Voyage AI, OpenAI, Cohere or a local model.
- Two phases: **index** documents offline (queued), **embed the query** online.
- Embeddings are bad at exact identifiers. Use SQL for `ORD-1043`.

## The problem

Your help centre has 400 articles. A customer searches:

> I can't get into my account

Your search runs `WHERE title LIKE '%get into my account%'` and returns nothing. The article that solves it is
called **"Resetting a forgotten password"**. Not one word in common.

So the customer contacts support, and you pay a human to send a link to a page you already published.

## What a vector is

```php
$vector = $embedder->embed('How do I reset my password?');

// [0.0231, -0.0512, 0.1140, ..., 0.0087]  — typically 512 to 1536 floats
```

The numbers mean nothing on their own. Direction is what matters.

| Text | Direction |
|---|---|
| "How do I reset my password?" | ← these two point |
| "I forgot my login details" | ← almost the same way |
| "What are your delivery times?" | somewhere else |

No shared words between the first two. Very close vectors. That is the whole trick.

## Measuring closeness

Cosine similarity — the angle between two vectors. `1.0` = same direction, `0.0` = unrelated.

```php
function cosineSimilarity(array $a, array $b): float
{
    $dot = 0.0; $magA = 0.0; $magB = 0.0;

    foreach ($a as $i => $value) {
        $dot  += $value * $b[$i];
        $magA += $value ** 2;
        $magB += $b[$i] ** 2;
    }

    return $dot / (sqrt($magA) * sqrt($magB));
}
```

That is all the maths in this module.

> Rough guide (varies by model): **> 0.8** same topic, **0.6–0.8** related, **< 0.5** probably noise.
> Calibrate on your own data, not a blog post.

## Where embeddings come from

**Anthropic does not sell an embeddings endpoint.** Claude generates text; it does not vectorise it. Worth
knowing before you design an architecture.

| Option | Notes |
|---|---|
| **Voyage AI** | Anthropic's recommended partner. Good, cheap, hosted. |
| **OpenAI embeddings** | Widely used; separate account and key. |
| **Cohere Embed** | Good multilingual support. |
| **Local (Ollama, ONNX)** | No per-call cost, no data leaves your servers, more ops work. |

Embedding is cheap — a few cents per million tokens, far below generation. Cost is rarely the deciding factor;
data residency and latency usually are.

```php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class Embedder
{
    public function embed(string $text): array
    {
        return $this->embedBatch([$text])[0];
    }

    public function embedBatch(array $texts): array
    {
        $response = Http::withToken(config('services.voyage.key'))
            ->post('https://api.voyageai.com/v1/embeddings', [
                'model' => 'voyage-3',
                'input' => $texts,
            ])
            ->throw()
            ->json();

        return array_column($response['data'], 'embedding');
    }
}
```

Batch your calls. 100 texts in one request is far faster and cheaper than 100 requests.

## The two-phase pattern

**Phase 1 — index (offline, when documents change):**

```php
class EmbedArticle implements ShouldQueue
{
    public function handle(Embedder $embedder): void
    {
        $this->article->update([
            // Title + body: the title carries a lot of signal
            'embedding' => json_encode($embedder->embed(
                $this->article->title."\n\n".$this->article->body
            )),
            'embedding_model' => 'voyage-3',
            'embedded_at' => now(),
        ]);
    }
}
```

Hook it to the model's `saved` event. A stale embedding is a silently wrong search result — the worst kind.

**Phase 2 — search (per query):**

```php
public function search(string $query, int $limit = 5): Collection
{
    $queryVector = $this->embedder->embed($query);

    return Article::whereNotNull('embedding')
        ->get()
        ->map(fn (Article $a) => [
            'article' => $a,
            'score' => cosineSimilarity($queryVector, json_decode($a->embedding, true)),
        ])
        ->sortByDesc('score')
        ->take($limit);
}
```

Yes — this loads every article and scores it in PHP. For a few thousand rows that is fine, and it is the
honest place to start. Module 6 covers what to do when it is not.

## Semantic, keyword, or both

Semantic search does not replace what you have. It is a different tool.

| Query | Best served by |
|---|---|
| "ORD-1043" | Exact match. `WHERE reference = ?` |
| "invoice from Acme in March" | SQL filters |
| "I can't get into my account" | Semantic |
| "refund policy for damaged goods" | **Both** — hybrid |

Hybrid search — run keyword and semantic, merge — beats either alone on most real data. Exact identifiers are
where embeddings are weakest: `ORD-1043` and `ORD-1044` are nearly identical vectors and completely different
orders. Never let vector search answer a question about a specific record. That is what module 4's tools are
for.

## Common mistakes

- **Embedding whole documents.** A 40-page PDF becomes one vector meaning "vaguely about everything". Chunk
  first — module 7.
- **Mixing models.** Vectors from different models are not comparable. Store the model name with the vector.
- **Stale vectors.** The article was edited; the embedding was not.
- **Expecting exact matching.** Product codes and SKUs: use SQL.
- **Ignoring language mix.** Check the model handles your languages before indexing 200,000 rows.

## You should now be able to

- [ ] Explain an embedding in one sentence with no maths
- [ ] Write cosine similarity from memory
- [ ] Say why Anthropic is not in your embeddings pipeline
- [ ] Split work into index-time and query-time
- [ ] Choose semantic, keyword or hybrid per query

## Practice

1. Embed "How do I reset my password?", "I forgot my login details" and "What are your delivery times?".
   Confirm the first two are close.
2. Index 50 rows from a real text table. Compare semantic search against your `LIKE` search.
3. Find a query where keyword search wins. Understanding *why* is the point.
4. On the **Live run** page, panel 4 uses TF-IDF, not embeddings. Ask "I can't get into my account" and watch a
   keyword scorer struggle. Then ask "password reset". That gap is what embeddings close.
