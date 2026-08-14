<?php

namespace App\Services;

/**
 * A small, dependency-free retriever used by the RAG playground.
 *
 * IMPORTANT — read this before you copy it into production:
 *
 * This scores chunks with TF-IDF + cosine similarity over *words*. That is
 * lexical search: it finds chunks that share vocabulary with the question.
 * A production RAG system replaces the scoring step with vector similarity
 * over *embeddings*, which finds chunks that share meaning even when they
 * share no words at all ("I forgot my login" vs "reset your password").
 *
 * The pipeline shape — chunk, score, take top-K, inject into the prompt — is
 * identical either way, which is why this demo teaches the pattern honestly
 * while only needing one API key. Modules 5 and 6 cover the real swap.
 */
class Retriever
{
    /**
     * Split text into chunks, one per section where possible.
     *
     * Two details here matter more than the chunk size, and module 7 explains
     * why:
     *
     *  1. Every chunk carries its document title and heading. A passage that
     *     starts "Refunds are issued to the original payment method" is far
     *     easier to retrieve as "Returns policy › How refunds are issued: …".
     *  2. Long sections are split with overlap, cut on a word boundary, so an
     *     idea spanning the split still exists intact somewhere.
     *
     * @return array<int, string>
     */
    public function chunk(string $text, ?string $title = null, int $targetChars = 700, int $overlapChars = 150): array
    {
        $chunks = [];

        foreach ($this->sections($text) as [$heading, $body]) {
            $label = trim(implode(' › ', array_filter([$title, $heading])));
            $prefix = $label === '' ? '' : $label.":\n";

            foreach ($this->split($body, $targetChars, $overlapChars) as $part) {
                $chunks[] = $prefix.$part;
            }
        }

        return $chunks;
    }

    /**
     * Break a document into [heading, body] pairs.
     *
     * A heading is a short standalone line with no closing punctuation — which
     * is what headings look like in handbooks, READMEs and policy pages alike.
     *
     * @return array<int, array{0: ?string, 1: string}>
     */
    private function sections(string $text): array
    {
        $blocks = preg_split('/\n\s*\n/', trim($text)) ?: [];

        $sections = [];
        $heading = null;
        $body = '';

        foreach ($blocks as $block) {
            $block = trim($block);

            if ($block === '') {
                continue;
            }

            if ($this->looksLikeHeading($block)) {
                if (trim($body) !== '') {
                    $sections[] = [$heading, trim($body)];
                }

                $heading = ltrim($block, '# ');
                $body = '';

                continue;
            }

            $body .= $block."\n\n";
        }

        if (trim($body) !== '') {
            $sections[] = [$heading, trim($body)];
        }

        // A document with no headings at all is still one section.
        return $sections === [] ? [[null, trim($text)]] : $sections;
    }

    private function looksLikeHeading(string $block): bool
    {
        if (str_contains($block, "\n") || mb_strlen($block) > 70) {
            return false;
        }

        // Markdown headings are unambiguous.
        if (str_starts_with($block, '#')) {
            return true;
        }

        // Otherwise: a short line that does not end like a sentence.
        return ! preg_match('/[.!?;:,]$/', $block);
    }

    /**
     * Split one section into overlapping parts, never mid-word.
     *
     * @return array<int, string>
     */
    private function split(string $body, int $targetChars, int $overlapChars): array
    {
        if (mb_strlen($body) <= $targetChars) {
            return [$body];
        }

        $sentences = preg_split('/(?<=[.!?])\s+/', $body) ?: [$body];

        $parts = [];
        $current = '';

        foreach ($sentences as $sentence) {
            if (mb_strlen($current) + mb_strlen($sentence) > $targetChars && $current !== '') {
                $parts[] = trim($current);
                $current = $this->tail($current, $overlapChars).' ';
            }

            $current .= $sentence.' ';
        }

        if (trim($current) !== '') {
            $parts[] = trim($current);
        }

        return $parts;
    }

    /** The last ~N characters, trimmed forward to the next word boundary. */
    private function tail(string $text, int $length): string
    {
        $tail = mb_substr($text, -$length);
        $space = mb_strpos($tail, ' ');

        return $space === false ? $tail : trim(mb_substr($tail, $space + 1));
    }

    /**
     * Return the best-matching chunks for a question, highest score first.
     *
     * @param  array<int, array{title: string, text: string}>  $chunks
     * @return array<int, array{title: string, text: string, score: float}>
     */
    public function topK(string $query, array $chunks, int $k = 4, float $minimumScore = 0.05): array
    {
        if ($chunks === []) {
            return [];
        }

        $documents = array_map(fn (array $chunk) => $this->tokenize($chunk['text']), $chunks);
        $idf = $this->inverseDocumentFrequency($documents);
        $queryVector = $this->weight($this->tokenize($query), $idf);

        $scored = [];

        foreach ($chunks as $index => $chunk) {
            $score = $this->cosine($queryVector, $this->weight($documents[$index], $idf));

            // A floor is what lets the system say "I could not find that"
            // instead of confidently answering from the least-bad chunk.
            if ($score >= $minimumScore) {
                $scored[] = $chunk + ['score' => round($score, 4)];
            }
        }

        usort($scored, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $k);
    }

    /** @return array<int, string> */
    private function tokenize(string $text): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // Very common words carry almost no signal about which chunk is relevant.
        $stopWords = [
            'the', 'a', 'an', 'and', 'or', 'but', 'if', 'of', 'to', 'in', 'on',
            'for', 'is', 'are', 'was', 'were', 'be', 'been', 'it', 'this', 'that',
            'with', 'as', 'at', 'by', 'from', 'we', 'you', 'your', 'our', 'i',
            'do', 'does', 'did', 'can', 'will', 'would', 'how', 'what', 'my',
            'have', 'has', 'get', 'got', 'me', 'us', 'they', 'their', 'there',
            'not', 'no', 'so', 'any', 'all', 'much', 'many', 'long', 'happens',
        ];

        $tokens = [];

        foreach ($words as $word) {
            if (in_array($word, $stopWords, true)) {
                continue;
            }

            $tokens[] = $this->stem($word);
        }

        return $tokens;
    }

    /**
     * A deliberately crude stemmer, so "shipping" matches "ship" and
     * "internationally" matches "international".
     *
     * Real embeddings make this unnecessary — meaning is captured directly,
     * which is one more thing you stop hand-rolling once you switch.
     */
    private function stem(string $word): string
    {
        if (mb_strlen($word) <= 4) {
            return $word;
        }

        foreach (['ally', 'ing', 'ies', 'ed', 'ly', 'es', 's'] as $suffix) {
            if (str_ends_with($word, $suffix) && mb_strlen($word) - mb_strlen($suffix) >= 3) {
                $stem = mb_substr($word, 0, -mb_strlen($suffix));

                // "shipping" → "shipp" → "ship"
                if (preg_match('/(.)\1$/', $stem)) {
                    $stem = mb_substr($stem, 0, -1);
                }

                return $suffix === 'ies' ? $stem.'y' : $stem;
            }
        }

        return $word;
    }

    /**
     * @param  array<int, array<int, string>>  $documents
     * @return array<string, float>
     */
    private function inverseDocumentFrequency(array $documents): array
    {
        $total = count($documents);
        $appearances = [];

        foreach ($documents as $tokens) {
            foreach (array_unique($tokens) as $token) {
                $appearances[$token] = ($appearances[$token] ?? 0) + 1;
            }
        }

        $idf = [];

        foreach ($appearances as $token => $count) {
            $idf[$token] = log(1 + ($total / $count));
        }

        return $idf;
    }

    /**
     * @param  array<int, string>  $tokens
     * @param  array<string, float>  $idf
     * @return array<string, float>
     */
    private function weight(array $tokens, array $idf): array
    {
        $vector = [];

        foreach ($tokens as $token) {
            $vector[$token] = ($vector[$token] ?? 0) + 1;
        }

        foreach ($vector as $token => $count) {
            // Sub-linear term frequency: the tenth mention of "refund" adds
            // much less than the second.
            $vector[$token] = (1 + log($count)) * ($idf[$token] ?? 0.0);
        }

        return $vector;
    }

    /**
     * The same cosine similarity you will use on real embedding vectors —
     * only the numbers going in are different.
     *
     * @param  array<string, float>  $a
     * @param  array<string, float>  $b
     */
    private function cosine(array $a, array $b): float
    {
        $dot = 0.0;

        foreach ($a as $token => $value) {
            $dot += $value * ($b[$token] ?? 0.0);
        }

        $magnitudeA = sqrt(array_sum(array_map(fn ($v) => $v ** 2, $a)));
        $magnitudeB = sqrt(array_sum(array_map(fn ($v) => $v ** 2, $b)));

        return ($magnitudeA * $magnitudeB) == 0.0 ? 0.0 : $dot / ($magnitudeA * $magnitudeB);
    }
}
