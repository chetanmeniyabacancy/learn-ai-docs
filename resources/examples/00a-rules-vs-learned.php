<?php

return [
    'id' => 'rules-vs-learned',
    'module' => 'what-is-ai',
    'title' => 'Written rules vs learned rules',
    'intro' => 'The boundary where ordinary programming becomes machine learning. Same problem, two completely different places for the logic to live.',
    'language' => 'php',
    'code' => <<<'PHP'
    // ── Rule-based: YOU write the logic ───────────────────
    public function isSpam(Email $email): bool
    {
        if (str_contains(strtolower($email->subject), 'viagra')) return true;
        if (str_contains($email->body, 'CLICK HERE NOW')) return true;
        if (substr_count($email->body, '!') > 10) return true;
        if ($email->from_domain === 'known-spammer.biz') return true;

        return false;
    }

    // ── Learned: you supply EXAMPLES, the logic is numbers ─
    $trainingData = [
        ['features' => $this->extract($email1), 'label' => 'spam'],
        ['features' => $this->extract($email2), 'label' => 'not_spam'],
        // …50,000 more, all previously sorted by humans
    ];

    $model = $trainer->fit($trainingData);      // finds the numbers

    $model->predict($this->extract($newEmail));
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    Test message: "V1AGRA!! c.l.i.c.k here now!!!"

      rule-based    → not spam     ← every rule dodged
      learned       → spam (0.94)

    ─────────────────────────────────────────────────────────
    What the trained model actually contains — no rules anywhere:

      exclamation_density   +2.31
      caps_ratio            +1.87
      digit_in_word         +1.44     ← "V1AGRA". Nobody wrote this.
      punctuation_in_word   +1.29     ← "c.l.i.c.k"
      sender_age_days       -0.92
      … 4,995 more weights
    TEXT,
    'notes' => [
        'Nobody wrote "a digit inside a word is suspicious". Training found it, along with thousands of patterns no human would have thought to look for.',
        'The trade is real, not free: the rule-based version explains every decision and never surprises you. The learned one is more capable and much harder to audit.',
        'The test to apply: <strong>are the rules knowable?</strong> If you can write them down, write them down. Module F8 is entirely about resisting the temptation not to.',
    ],
    'live' => null,
];
