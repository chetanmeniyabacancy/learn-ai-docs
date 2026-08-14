## Summary

- Choose storage by **row count**, not fashion. Under 5,000 vectors: a JSON column and a PHP loop.
- 5k–1M: **pgvector**. Filters and vector search in one SQL statement.
- Store metadata with every vector — especially `tenant_id` and `visibility`.
- **Permissions belong in the query**, never in the prompt.
- **K = 3–5.** More chunks means more cost and worse answers.
- Change the embedding model and every vector must be rebuilt. Write the reindex command on day one.

## The problem

The module 5 search works on 50 articles. Then legal asks you to make 100,000 contract pages searchable.

`Article::all()` now loads 100,000 rows and 150 million floats into PHP memory on every keystroke.

## Pick by scale

| Vectors | Use | Why |
|---|---|---|
| < 5,000 | MySQL/SQLite JSON column + PHP loop | No new infrastructure. Ships today. |
| 5k – 1M | **PostgreSQL + pgvector** | Real index, SQL filters and vectors in one query. |
| 1M+ | Qdrant, Pinecone, Weaviate | Purpose-built ANN indexes, horizontal scale. |
| Any, already running it | Meilisearch / Elasticsearch | Hybrid keyword + vector in a tool you operate. |

Most Laravel apps live in the first two rows forever. Be honest about which you are in.

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

Two details buy a lot of headroom: select only the columns you need, and apply every SQL filter *before*
scoring. Filtering 100,000 rows down to 2,000 then looping is a different proposition entirely.

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

`<=>` is cosine distance, so `1 - distance` is similarity. Notice `AND team_id = ?` in the same statement:
**metadata filtering and vector search in one query, with your normal transactions and backups.** That is why
pgvector is the right answer more often than people assume.

| Index | Build | Query | Use when |
|---|---|---|---|
| `ivfflat` | Fast | Good | Bulk-loaded, rebuilt occasionally |
| `hnsw` | Slower, more memory | Faster, more accurate | Most production work |

Both are *approximate*. You trade a fraction of a percent of recall for a huge speed gain — nearly always
right for search, and worth knowing you made the trade.

## Metadata

A vector store is useless if you cannot say *which* vectors to search. Store filters from day one:

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

`tenant_id` and `visibility` cause the incidents when missing. A cross-tenant vector search is a data breach
that looks like a relevance bug — hard to spot in testing, because the results still look plausible.

**Permissions must be enforced here**, in the `WHERE` clause. Not in the prompt. If a user cannot read a
document in your app, it must never enter their prompt.

## What K should be

K = how many chunks you put in the prompt.

- **K = 3–5** is normal.
- More chunks cost more tokens and dilute the signal, lowering quality.
- If the right chunk is never in your top 5, fix chunking or retrieval — not K.

Measure it: for 20 real questions, is the passage containing the answer in the top 5? That number (recall@5)
is the most useful metric in RAG. Module 8 shows how to track it.

## Reindexing

**Change the embedding model and every existing vector becomes meaningless.** They live in a different space;
comparing them returns nonsense rather than an error.

So:

- Store the model name with the vector.
- Make reindexing a routine artisan command.
- Reindex into a new column, verify, then switch.

```bash
php artisan embeddings:reindex --model=voyage-3 --chunk=200
```

Write that command the day you build the feature.

## Common mistakes

- **Reaching for Pinecone at 800 documents.** A JSON column would have done.
- **`SELECT *` in the scoring loop.** You loaded every body column to sort by one float.
- **No tenant filter.** Silent cross-customer leakage.
- **Filtering after retrieval.** Ask for 5, throw 4 away for permissions, answer from 1. Filter first.
- **Mixed models in one table.** Half your search compares incomparable numbers.
- **No reindex path.** Every model upgrade becomes a project.

## You should now be able to

- [ ] Choose storage from your actual row count
- [ ] Write a pgvector query with metadata filters
- [ ] Explain why permissions belong in retrieval, not the prompt
- [ ] Pick a sensible K and measure recall@K
- [ ] Ship a reindex command with the feature

## Practice

1. Count the rows you would embed. Pick a row from the table and write down why.
2. Add `tenant_id` and `visibility` to your metadata now, before there is data to migrate.
3. Take 20 real questions and check the right passage is in the top 5. Record the number.
4. If you have Postgres, install pgvector and port your PHP loop to SQL. Compare timings at 10,000 rows.
