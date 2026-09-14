<?php

return [
    'id' => 'grounding-check',
    'module' => 'guardrails',
    'title' => 'A free, deterministic hallucination detector',
    'intro' => 'Before reaching for a model to judge every answer, try this: every number in the answer should appear in the passages you gave it. A few lines of PHP, no cost, same result every time — and it catches the failure that matters most in a factual product.',
    'language' => 'php',
    'code' => <<<'PHP'
    public function inspect(string $answer, array $passages): OutputVerdict
    {
        $problems = [];
        $context = implode(' ', $passages);

        // 1. Every figure in the answer must exist in the source.
        foreach ($this->numbersIn($answer) as $number) {
            if (! str_contains($context, $number)) {
                $problems[] = "ungrounded_number:{$number}";
            }
        }

        // 2. Personal data that should never be in an answer.
        if (preg_match('/\b[\w.+-]+@[\w-]+\.[\w.]+\b/', $answer)) {
            $answer = preg_replace('/\b[\w.+-]+@[\w-]+\.[\w.]+\b/', '[redacted]', $answer);
            $problems[] = 'email_redacted';
        }

        // 3. Promises the business cannot keep.
        foreach (['guarantee', 'we will definitely', 'i promise'] as $phrase) {
            if (str_contains(mb_strtolower($answer), $phrase)) {
                $problems[] = 'commitment_language';
            }
        }

        return new OutputVerdict($answer, $problems);
    }

    private function numbersIn(string $text): array
    {
        preg_match_all('/\b\d[\d,]*(?:\.\d+)?\b/', $text, $matches);

        return array_unique($matches[0]);
    }
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    passage [0]  "Up to 5 unused annual leave days carry forward and expire on 31 March."

    answer A   "You can carry forward up to 5 days, and they expire on 31 March. [0]"
               numbers found: 5, 31        both present in [0]        → pass

    answer B   "You can carry forward up to 7 days, and they expire on 31 March. [0]"
               numbers found: 7, 31        7 is NOT in [0]            → ungrounded_number:7
               action: regenerate once with "quote figures exactly"

    answer C   "Contact priya.sharma@company.com about your balance."
               → email_redacted, and the trigger is logged

    3,412 answers checked over one week · 47 ungrounded numbers · 41 fixed by
    one regeneration · 6 withheld and routed to a human
    TEXT,
    'notes' => [
        'What to do when it fires is the design decision. An ungrounded number earns one regeneration with a sharper instruction; a second failure should withhold rather than guess again.',
        'Sample the triggers by hand. A guardrail blocking good answers is a bug that looks like safety, and it is why teams eventually turn checks off.',
        'This is the cheapest tier of a three-tier ladder: rules, then a small classifier, then a model as judge. Most traffic never needs the last one.',
    ],
    'live' => null,
];
