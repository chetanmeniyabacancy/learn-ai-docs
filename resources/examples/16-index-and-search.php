<?php

return [
    'id' => 'index-and-search',
    'module' => 'embeddings',
    'title' => 'The two-phase pattern',
    'intro' => 'Everything built on embeddings has this shape: index offline when documents change, embed only the query online.',
    'language' => 'php',
    'code' => <<<'PHP'
    // ── Phase 1: index (queued, on save) ──────────────────
    class EmbedArticle implements ShouldQueue
    {
        public function handle(Embedder $embedder): void
        {
            $this->article->update([
                // Title + body: the title carries a lot of signal
                'embedding' => json_encode($embedder->embed(
                    $this->article->title."\n\n".$this->article->body
                )),
                'embedding_model' => 'voyage-3',   // so you know when to reindex
                'embedded_at' => now(),
            ]);
        }
    }

    // ── Phase 2: search (per request) ─────────────────────
    $queryVector = $embedder->embed('I cannot get into my account');

    $results = Article::whereNotNull('embedding')
        ->where('published', true)
        ->get(['id', 'title', 'embedding'])          // never SELECT *
        ->map(fn ($a) => [
            'title' => $a->title,
            'score' => cosineSimilarity($queryVector, json_decode($a->embedding, true)),
        ])
        ->sortByDesc('score')
        ->take(3);
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    Query: "I cannot get into my account"

      0.887  Resetting a forgotten password
      0.731  Two-factor authentication problems
      0.688  Why is my account locked?

    For comparison, the old query on the same corpus:
      WHERE title LIKE '%cannot get into my account%'   →  0 rows
    TEXT,
    'notes' => [
        'Zero rows to three good answers, and the top result shares not one word with the query.',
        'Yes, this loads every article and scores it in PHP. Below a few thousand rows that is genuinely fine and ships today — module 6 covers what to do when it is not.',
        'Store <code>embedding_model</code> alongside the vector. Change the model and every existing vector becomes meaningless, silently.',
    ],
    'live' => 'rag',
];
