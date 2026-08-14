<?php

return [
    'id' => 'vector-column',
    'module' => 'vector-search',
    'title' => 'Start with a column and a loop',
    'intro' => 'Below a few thousand vectors you do not need a vector database, an extension, or a new vendor. You need a JSON column. Picking the complicated option first is the actual mistake.',
    'language' => 'php',
    'code' => <<<'PHP'
    Schema::table('articles', function (Blueprint $table) {
        $table->json('embedding')->nullable();
        $table->string('embedding_model')->nullable();   // so you know when to reindex
        $table->timestamp('embedded_at')->nullable();
    });

    public function search(string $query, int $limit = 5): Collection
    {
        $queryVector = $this->embedder->embed($query);

        return Article::query()
            ->whereNotNull('embedding')
            ->where('tenant_id', auth()->user()->tenant_id)   // filter FIRST — in SQL
            ->where('published', true)                        // shrinks the loop
            ->get(['id', 'title', 'embedding'])               // never SELECT *
            ->map(fn ($a) => [
                'title' => $a->title,
                'score' => cosineSimilarity($queryVector, json_decode($a->embedding, true)),
            ])
            ->sortByDesc('score')
            ->take($limit);
    }
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    900 articles, filtered to 340 published in this tenant:

      0.887  Resetting a forgotten password
      0.731  Two-factor authentication problems
      0.688  Why is my account locked?

      scored 340 vectors in PHP      18 ms
      total request                  94 ms   (74 ms of that was embedding the query)

    ─────────────────────────────────────────────────────────
    Rough guide to when you outgrow this:

      < 5,000 vectors     JSON column + PHP loop      ← you are here
      5k – 1M             PostgreSQL + pgvector
      1M+                 Qdrant / Pinecone / Weaviate
    TEXT,
    'notes' => [
        'Most of the request is the embedding API call, not the scoring. That stays true until you are well past ten thousand vectors.',
        'Two details buy a lot of headroom: select only the columns you need, and apply every SQL filter <em>before</em> scoring. Filtering 100,000 rows to 2,000 and then looping is a completely different proposition.',
        'The <code>tenant_id</code> filter is not an optimisation. A missing one is a cross-customer data leak that looks like a relevance bug.',
    ],
    'live' => null,
];
