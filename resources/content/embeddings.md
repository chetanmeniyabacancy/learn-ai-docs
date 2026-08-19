## Summary

- An **embedding** turns text into a list of numbers, arranged so that texts with a similar *meaning* end up close together.
- **Cosine similarity** measures how close two of them are. It is about ten lines of PHP.
- **Anthropic has no embeddings endpoint.** You need Voyage AI, OpenAI, Cohere, or a local model.
- There are two phases: **index** your documents in the background, and **embed the question** when the user asks it.
- Embeddings are bad at exact codes. For `ORD-1043`, use SQL.

## The problem

Your help centre has 400 articles. A customer searches for:

> I can't get into my account

Your search runs `WHERE title LIKE '%get into my account%'` and finds nothing. But the article that solves this
problem is called **"Resetting a forgotten password"**. The two texts share no words at all.

So the customer contacts support, and you pay a human to send a link to a page you already wrote.

## What a vector is

```php
$vector = $embedder->embed('How do I reset my password?');

// [0.0231, -0.0512, 0.1140, ..., 0.0087]  — typically 512 to 1536 floats
```

Each number on its own means nothing. What matters is the direction the whole list points in.

| Text | Direction |
|---|---|
| "How do I reset my password?" | ← these two point |
| "I forgot my login details" | ← almost the same way |
| "What are your delivery times?" | somewhere else |

The first two sentences share no words, but their vectors point almost the same way. That is the whole trick.

## Measuring closeness

Cosine similarity measures the angle between two vectors. `1.0` means they point the same way, and `0.0` means
they are unrelated.

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

> A rough guide, which changes from model to model: **above 0.8** means the same topic, **0.6 to 0.8** means
> related, and **below 0.5** is probably noise. Check these numbers against your own data, not against a blog
> post.

## Where embeddings come from

**Anthropic does not sell an embeddings endpoint.** Claude writes text. It does not turn text into vectors. It
is worth knowing this before you design your architecture.

| Option | Notes |
|---|---|
| **Voyage AI** | Anthropic's recommended partner. Good, cheap, hosted. |
| **OpenAI embeddings** | Widely used; separate account and key. |
| **Cohere Embed** | Good multilingual support. |
| **Local (Ollama, ONNX)** | No per-call cost, no data leaves your servers, more ops work. |

Embedding is cheap, a few cents per million tokens, which is far less than generating text. So cost is rarely
what decides your choice. Usually it comes down to where your data is allowed to live, and how fast you need
the response.

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

Always send your texts in batches. Sending 100 texts in one request is much faster and cheaper than making 100
separate requests.

## The two-phase pattern

**Phase 1 — index your documents.** This runs in the background, whenever a document changes:

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

Trigger this from the model's `saved` event. An out-of-date embedding gives you a wrong search result with no
error message, and that is the worst kind of bug.

**Phase 2 — search.** This runs each time somebody asks a question:

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

Yes, this loads every article and scores it inside PHP. For a few thousand rows that is perfectly fine, and it
is an honest place to start. Module 6 explains what to do when it stops being fine.

## Semantic, keyword, or both

Semantic search does not replace the search you already have. It is a different tool for a different job.

| Query | Best served by |
|---|---|
| "ORD-1043" | Exact match. `WHERE reference = ?` |
| "invoice from Acme in March" | SQL filters |
| "I can't get into my account" | Semantic |
| "refund policy for damaged goods" | **Both** — hybrid |

Hybrid search means running keyword search and semantic search together and merging the results. On real data
it usually beats either one alone. Exact codes are the weakest spot for embeddings: `ORD-1043` and `ORD-1044`
produce almost identical vectors but are completely different orders. So never let vector search answer a
question about one specific record. That is exactly what the tools in module 4 are for.

## Common mistakes

- **Embedding a whole document as one vector.** A 40-page PDF becomes a single vector that means "vaguely about
  everything". Cut it into chunks first — that is module 7.
- **Mixing models.** Vectors from two different models cannot be compared. Always store the model name next to
  the vector.
- **Out-of-date vectors.** Somebody edited the article, but nothing re-embedded it.
- **Expecting exact matching.** For product codes and SKUs, use SQL.
- **Forgetting about languages.** Check that the model handles your languages before you index 200,000 rows.

## You should now be able to

- [ ] Explain an embedding in one sentence, with no maths
- [ ] Write cosine similarity from memory
- [ ] Say why Anthropic is not part of your embeddings pipeline
- [ ] Split the work into index-time and query-time
- [ ] Choose semantic, keyword or hybrid search for a given query

## Practice

1. Embed "How do I reset my password?", "I forgot my login details" and "What are your delivery times?". Check
   that the first two are close together.
2. Index 50 rows from a real text table. Compare semantic search with your existing `LIKE` search.
3. Find a query where keyword search wins. Understanding *why* it wins is the point of the exercise.
4. On the **Live run** page, panel 4 uses TF-IDF instead of embeddings. Ask "I can't get into my account" and
   watch a keyword scorer struggle. Then ask "password reset". That gap is what embeddings close.
