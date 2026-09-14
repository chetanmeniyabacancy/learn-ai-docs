<?php

return [
    [
        'question' => 'Recall@4 is 0.62 and answers are often wrong. What do you fix first?',
        'options' => [
            'The answer prompt, so it uses the passages better',
            'Retrieval — 0.62 recall means 38% of questions have no chance no matter what the prompt says',
            'Switch to a bigger model for generation',
            'Increase K from 4 to 10 so more passages are included',
        ],
        'answer' => 1,
        'explanation' => 'Recall is your ceiling. If the passage holding the answer is never retrieved, the best prompt in the world cannot recover it. Raising K adds noise and cost without fixing the miss.',
    ],
    [
        'question' => 'Why does a reranker beat a vector distance at judging relevance?',
        'options' => [
            'It uses a larger embedding model',
            'It reads the question and the candidate passage together, instead of comparing two separately-made vectors',
            'It searches more documents',
            'It caches previous judgements',
        ],
        'answer' => 1,
        'explanation' => 'In a vector search the question never meets the document — you compare two independently created vectors. A reranker reads both at once, which is far more accurate and far too slow to run over the whole corpus. That is why you retrieve wide cheaply, then rerank.',
    ],
    [
        'question' => 'A user searches for "ORD-1043". Which retrieval method handles this best?',
        'options' => [
            'Vector search, because it understands meaning',
            'Keyword search — ORD-1043 and ORD-1044 are nearly identical vectors but completely different orders',
            'A reranker on its own',
            'Query rewriting',
        ],
        'answer' => 1,
        'explanation' => 'Exact identifiers are where embeddings are weakest. This is the case for hybrid search: keyword for codes, vectors for meaning, merged with something like reciprocal rank fusion.',
    ],
    [
        'question' => 'What does query rewriting actually do, and why is it cheap?',
        'options' => [
            'It rewrites the answer to be clearer, using the main model',
            'It turns the user\'s wording into better search queries, and a small fast model is enough for the job',
            'It re-embeds the corpus with a better model',
            'It removes stop words before searching',
        ],
        'answer' => 1,
        'explanation' => 'A vague question makes a poor search query. Expanding it into two or three well-worded queries is a Haiku-sized task costing a fraction of a cent, and it often lifts recall more than changing embedding models.',
    ],
    [
        'question' => 'You add reranking and recall rises from 0.86 to 0.94, but cost per query triples. What now?',
        'options' => [
            'Always keep it — recall is the most important metric',
            'Always remove it — tripling cost is never acceptable',
            'Decide with the numbers: what those 8 points are worth to this product against the extra cost and latency',
            'Replace the vector search with the reranker entirely',
        ],
        'answer' => 2,
        'explanation' => 'The point of measuring each technique separately is to make this a business decision rather than a preference. For a legal search tool those 8 points are cheap; for an internal FAQ they may not be. Being able to remove a technique is as important as being able to add one.',
    ],
];
