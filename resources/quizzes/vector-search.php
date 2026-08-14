<?php

return [
    [
        'question' => 'You have 900 help articles to make searchable. What should you reach for first?',
        'options' => [
            'A managed vector database such as Pinecone',
            'A JSON column on the existing table and a scoring loop in PHP',
            'Elasticsearch with a vector plugin',
            'Sharded pgvector across three replicas',
        ],
        'answer' => 1,
        'explanation' => 'Below a few thousand vectors a column and a loop are genuinely fine and ship today with no new infrastructure. Picking the complicated option first is the actual mistake.',
    ],
    [
        'question' => 'What is the strongest argument for pgvector over a dedicated vector database?',
        'options' => [
            'It is faster than every alternative at any scale',
            'Vector search and SQL metadata filters run in one query, with your existing backups and transactions',
            'It does not require an index',
            'It supports more embedding models',
        ],
        'answer' => 1,
        'explanation' => 'Being able to write "nearest vectors WHERE tenant_id = ? AND published = true" in one statement, inside the database you already operate, is why pgvector is right far more often than people assume.',
    ],
    [
        'question' => 'Which metadata field, if missing, turns a relevance bug into a data breach?',
        'options' => [
            'page number',
            'tenant_id',
            'updated_at',
            'section heading',
        ],
        'answer' => 1,
        'explanation' => 'Without a tenant filter, one customer\'s question can retrieve another customer\'s documents. It is hard to notice in testing because the results still look plausible.',
    ],
    [
        'question' => 'Retrieval keeps missing the right passage. Your teammate suggests raising K from 4 to 20. What is the risk?',
        'options' => [
            'None — more context is always better',
            'More tokens, higher cost, and a diluted signal that usually lowers answer quality',
            'The API rejects more than 10 chunks',
            'Vectors become less accurate at higher K',
        ],
        'answer' => 1,
        'explanation' => 'If the right chunk is never in the top 5, the fix is chunking, retrieval or hybrid search. Raising K papers over a retrieval problem while making cost and quality worse.',
    ],
    [
        'question' => 'Where must document permissions be enforced?',
        'options' => [
            'In the system prompt: "only answer if the user is an admin"',
            'In the retrieval query, so unauthorised documents never enter the prompt',
            'In the UI, by hiding the answer',
            'In the embedding model',
        ],
        'answer' => 1,
        'explanation' => 'Anything retrieved is in the prompt, and anything in the prompt can be repeated. Filter in the WHERE clause — prompts are guidance, queries are enforcement.',
    ],
];
