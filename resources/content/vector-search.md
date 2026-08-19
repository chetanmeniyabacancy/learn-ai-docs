## Summary

- Choose your storage by **how many rows you have**, not by what is popular. Under 5,000 vectors, a JSON column and a PHP loop is enough.
- Between 5,000 and 1 million, use **pgvector**. You get filters and vector search in one SQL statement.
- Save metadata next to every vector, especially `tenant_id` and `visibility`.
- **Permission checks belong in the query**, never in the prompt.
- **Keep K between 3 and 5.** More chunks means more cost and usually worse answers.
- If you change the embedding model, every old vector must be rebuilt. Write the reindex command on day one.

## The problem

The search you built in module 5 works nicely on 50 articles. Then the legal team asks you to make 100,000
pages of contracts searchable.

Now `Article::all()` loads 100,000 rows and 150 million floats into PHP memory on every keystroke.

## Pick by scale

| Vectors | Use | Why |
|---|---|---|
| < 5,000 | MySQL/SQLite JSON column + PHP loop | No new infrastructure. Ships today. |
| 5k – 1M | **PostgreSQL + pgvector** | Real index, SQL filters and vectors in one query. |
| 1M+ | Qdrant, Pinecone, Weaviate | Purpose-built ANN indexes, horizontal scale. |
| Any, already running it | Meilisearch / Elasticsearch | Hybrid keyword + vector in a tool you operate. |

Most Laravel apps stay in the first two rows forever. Be honest about which row you are actually in.

## Level 1: a column and a loop

```php
Schema::table('articles', function (Blueprint $table) {
    $table->json('embedding')->nullable();
    $table->string('embedding_model')->nullable();
    $table->timestamp('embedded_at')->nullable();
});
```

```php
public function search(string $query, int $limit = 5): Collection
{
    $queryVector = $this->embedder->embed($query);

    return Article::query()
        ->whereNotNull('embedding')
        ->where('published', true)          // filter in SQL — cheap, shrinks the loop
        ->get(['id', 'title', 'embedding'])  // never SELECT *
        ->map(fn ($a) => [
            'id' => $a->id,
            'title' => $a->title,
            'score' => cosineSimilarity($queryVector, json_decode($a->embedding, true)),
        ])
        ->sortByDesc('score')
        ->take($limit);
}
```

Two small details give you a lot of extra room. First, select only the columns you need. Second, apply every SQL
filter *before* you start scoring. Cutting 100,000 rows down to 2,000 and then looping is a completely different
situation from looping over all of them.

## Level 2: pgvector

```sql
CREATE EXTENSION IF NOT EXISTS vector;

ALTER TABLE articles ADD COLUMN embedding vector(1024);

-- HNSW: fast approximate nearest neighbour. Build after bulk loading.
CREATE INDEX ON articles USING hnsw (embedding vector_cosine_ops);
```

```php
$vector = '['.implode(',', $this->embedder->embed($query)).']';

$results = DB::select('
    SELECT id, title, 1 - (embedding <=> ?) AS score
    FROM articles
    WHERE published = true
      AND team_id = ?
    ORDER BY embedding <=> ?
    LIMIT 5
', [$vector, $teamId, $vector]);
```

The `<=>` operator gives cosine distance, so `1 - distance` gives you similarity. Look closely at
`AND team_id = ?` sitting in the same statement. **You get metadata filtering and vector search in one query,
with your normal transactions and backups.** That is why pgvector is the right answer far more often than people
expect.

| Index | Build | Query | Use when |
|---|---|---|---|
| `ivfflat` | Fast | Good | Bulk-loaded, rebuilt occasionally |
| `hnsw` | Slower, more memory | Faster, more accurate | Most production work |

Both indexes are *approximate*. You lose a tiny fraction of a percent of accuracy and gain a huge amount of
speed. For search that trade is almost always correct, but you should know you made it.

## Metadata

A vector store is useless if you cannot say *which* vectors to search. So store your filters from the very
first day:

```php
[
    'embedding' => [...],
    'metadata' => [
        'tenant_id' => 42,          // multi-tenancy: non-negotiable
        'document_id' => 118,
        'source' => 'handbook.pdf',
        'page' => 14,
        'section' => 'Returns',
        'visibility' => 'public',   // never surface internal docs to customers
    ],
]
```

The two fields that cause real incidents when missing are `tenant_id` and `visibility`. A vector search that
crosses between tenants is a data breach that looks exactly like a relevance bug. It is very hard to notice in
testing, because the wrong results still look reasonable.

**Permissions must be enforced right here**, in the `WHERE` clause. Not in the prompt. If a user cannot open a
document inside your app, that document must never enter their prompt.

## What K should be

K means how many chunks you put into the prompt.

- **K = 3 to 5** is normal.
- More chunks cost more tokens and water down the useful signal, so answers get worse.
- If the correct chunk never appears in your top 5, the fix is better chunking or better retrieval. Raising K is
  not the fix.

Measure it properly. Take 20 real questions and check whether the passage containing the answer is in the top 5.
That percentage is called recall@5, and it is the most useful number in RAG. Module 8 shows how to track it.

## Reindexing

**If you change the embedding model, every existing vector becomes meaningless.** The new model uses a different
space, so comparing old and new vectors returns nonsense instead of an error. That silence is what makes it
dangerous.

So do three things:

- Store the model name next to every vector.
- Make reindexing a normal artisan command.
- Reindex into a new column, check the results, and only then switch over.

```bash
php artisan embeddings:reindex --model=voyage-3 --chunk=200
```

Write that command on the same day you build the feature.

## Common mistakes

- **Reaching for Pinecone when you have 800 documents.** A JSON column would have been enough.
- **Using `SELECT *` in the scoring loop.** You just loaded every body column in order to sort by one float.
- **No tenant filter.** This leaks data between customers, quietly.
- **Filtering after retrieval.** You ask for 5 chunks, throw 4 away for permission reasons, and answer from 1.
  Filter first.
- **Two models mixed in one table.** Half your searches are comparing numbers that mean different things.
- **No way to reindex.** Every model upgrade turns into a project.

## You should now be able to

- [ ] Choose storage based on your real row count
- [ ] Write a pgvector query with metadata filters
- [ ] Explain why permissions belong in retrieval, not in the prompt
- [ ] Choose a sensible K and measure recall@K
- [ ] Ship a reindex command together with the feature

## Practice

1. Count the rows you would need to embed. Pick a row from the table above and write down why it fits.
2. Add `tenant_id` and `visibility` to your metadata now, while there is no data to migrate.
3. Take 20 real questions and check that the right passage is in the top 5. Write the number down.
4. If you have Postgres, install pgvector and move your PHP loop into SQL. Compare the timings at 10,000 rows.
