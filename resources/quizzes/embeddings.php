<?php

return [
    [
        'question' => 'What is an embedding?',
        'options' => [
            'A compressed copy of the text',
            'A vector of numbers positioned so that texts with similar meaning are close together',
            'A hash used to deduplicate documents',
            'The model\'s summary of a document',
        ],
        'answer' => 1,
        'explanation' => 'Meaning becomes geometry. "I forgot my login" and "reset your password" share no words but point in almost the same direction, which is what makes semantic search work.',
    ],
    [
        'question' => 'Which query is embeddings the WRONG tool for?',
        'options' => [
            '"I cannot get into my account"',
            '"refund policy for damaged goods"',
            '"ORD-1043"',
            '"how do I change my delivery address"',
        ],
        'answer' => 2,
        'explanation' => 'Exact identifiers are where vectors are weakest: ORD-1043 and ORD-1044 are nearly identical vectors and completely different orders. Use SQL — or a tool call — for specific records.',
    ],
    [
        'question' => 'Where do you get embeddings when you are using Claude?',
        'options' => [
            'The same Anthropic Messages endpoint with a flag',
            'A separate provider such as Voyage AI, OpenAI or a local model — Anthropic does not sell an embeddings endpoint',
            'Any Claude model with maxTokens set to 0',
            'They are generated automatically when you use RAG',
        ],
        'answer' => 1,
        'explanation' => 'Claude generates text; it does not vectorise it. This surprises people planning an architecture, and it is much better to know before you design one.',
    ],
    [
        'question' => 'You switch embedding model to improve quality. What must happen to existing vectors?',
        'options' => [
            'Nothing — vectors are portable across models',
            'Every vector must be regenerated; vectors from different models are not comparable',
            'Only new documents need the new model',
            'Rescale the old vectors to the new dimension count',
        ],
        'answer' => 1,
        'explanation' => 'Different models mean different spaces. Comparing across them returns nonsense rather than an error, which is why you store the model name with the vector and keep a reindex command ready.',
    ],
    [
        'question' => 'At what point do you embed text in a typical system?',
        'options' => [
            'Both documents and queries are embedded on every request',
            'Documents at write time (queued), the query at search time',
            'Everything at search time for freshness',
            'Documents only; queries are matched with SQL',
        ],
        'answer' => 1,
        'explanation' => 'Two phases: index offline when documents change, embed only the query online. Re-embedding your corpus on every search would be slow and pointlessly expensive.',
    ],
];
