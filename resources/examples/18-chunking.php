<?php

return [
    'id' => 'chunking',
    'module' => 'rag',
    'title' => 'Chunking, and why headings matter',
    'intro' => 'The cheapest retrieval improvement there is: keep each chunk\'s heading inside the chunk. This site\'s own RAG demo got noticeably better the moment it started doing this.',
    'language' => 'php',
    'code' => <<<'PHP'
    // ❌ Naive: split on length alone
    $chunks = str_split($handbook, 900);

    // ✅ Split on structure, carry the heading, overlap the boundary
    foreach ($this->sections($handbook) as [$heading, $body]) {
        $label = "{$documentTitle} › {$heading}";

        foreach ($this->split($body, target: 700, overlap: 150) as $part) {
            $chunks[] = "{$label}:\n{$part}";
        }
    }
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    ❌ naive chunk #4 (cut mid-word, no context about what it is)

       "nk; we have no way to speed that up once the refund is released. Store
        credit is issued instantly if the customer prefers it. Return shipping
        costs Return shipping is free for customers in the United Kingdom…"

    ✅ structured chunk #4

       "Returns and refunds policy › How refunds are issued:
        Refunds are issued to the original payment method. Once the returned
        parcel arrives at our warehouse we inspect it within 2 working days…"

    ─────────────────────────────────────────────────────────────
    Retrieval for "what happens if my item arrives damaged?"

       naive        0.18   Returns and refunds (some chunk)
       structured   0.40   Returns and refunds › Damaged or faulty items
    TEXT,
    'notes' => [
        'Overlap matters because an idea can straddle a boundary: "within 30 days" in one chunk and "returns must be unused" in the next means neither answers the question alone.',
        'Never cut mid-word or mid-sentence. Split on headings first, then paragraphs, then sentences.',
        'Sensible starting numbers: 500–1,000 characters per chunk, 10–15% overlap. Tune with recall@K (module 8), not by feel.',
    ],
    'live' => 'rag',
];
