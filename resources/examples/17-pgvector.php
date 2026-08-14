<?php

return [
    'id' => 'pgvector',
    'module' => 'vector-search',
    'title' => 'pgvector: filters and vectors in one query',
    'intro' => 'The argument for pgvector in one statement — metadata filtering and nearest-neighbour search together, in the database you already run and back up.',
    'language' => 'sql',
    'code' => <<<'SQL'
    CREATE EXTENSION IF NOT EXISTS vector;

    ALTER TABLE chunks ADD COLUMN embedding vector(1024);

    -- HNSW: fast approximate nearest neighbour. Build after bulk loading.
    CREATE INDEX ON chunks USING hnsw (embedding vector_cosine_ops);

    -- The query. <=> is cosine DISTANCE, so 1 - distance is similarity.
    SELECT id, heading, 1 - (embedding <=> $1) AS score
    FROM chunks
    WHERE tenant_id  = $2          -- ← multi-tenancy, enforced in SQL
      AND visibility = 'public'    -- ← permissions, enforced in SQL
    ORDER BY embedding <=> $1
    LIMIT 4;
    SQL,
    'output_language' => 'text',
    'output' => <<<'TEXT'
     id  | heading                                    | score
    -----+--------------------------------------------+--------
     318 | Returns and refunds › Returns window        | 0.8912
     402 | Returns and refunds › Condition of goods    | 0.7734
     319 | Returns and refunds › How refunds issued    | 0.7401
     288 | Shipping and delivery › Missing parcels     | 0.6120
    (4 rows)

    Time: 3.1 ms       (over 240,000 chunks)
    TEXT,
    'notes' => [
        'Three milliseconds over a quarter of a million chunks — and the tenant and visibility filters ran in the same statement, under the same transaction.',
        'Those two <code>WHERE</code> clauses are the whole of your access control. A missing <code>tenant_id</code> filter is a data breach that looks like a relevance bug, because the results still seem plausible.',
        'HNSW is <em>approximate</em>. You trade a fraction of a percent of recall for orders of magnitude of speed — nearly always right for search, and worth knowing you made the trade.',
        'Below ~5,000 vectors you do not need any of this. A JSON column and a PHP loop is the honest starting point.',
    ],
    'live' => null,
];
