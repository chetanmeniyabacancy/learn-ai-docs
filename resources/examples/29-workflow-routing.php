<?php

return [
    'id' => 'workflow-routing',
    'module' => 'agentic-workflows',
    'title' => 'Routing: most traffic should never reach an expensive model',
    'intro' => 'The cheapest possible answer to "how do we reduce our AI bill" is usually "stop sending it questions that do not need it". Classify first, then send each request down the narrowest path that can answer it.',
    'language' => 'php',
    'code' => <<<'PHP'
    public function handle(string $message, User $user): Response
    {
        // Tier 0 — no model at all. Free, instant, exact.
        if ($reference = $this->extractOrderReference($message)) {
            return $this->orderStatus($reference, $user);       // SQL
        }

        // Tier 1 — the same question was asked 40 minutes ago.
        if ($cached = $this->cache->lookup($message, $user)) {
            return $cached;
        }

        // Tier 2 — a cheap model classifies. ~40 output tokens.
        $intent = $this->classify($message);                    // Haiku

        // Tier 3 — a written answer, rendered with their data. No generation.
        if ($template = $this->templateFor($intent)) {
            return $template->render($user);
        }

        // Tier 4 — the good model, for questions that genuinely need it.
        return $this->assistant($message, $user);               // Sonnet
    }
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    200 real messages, routed

    tier 0  order lookups, statuses, links        62 msgs   31%   $0.0000
    tier 1  cache hits                            36 msgs   18%   $0.0000
    tier 2+3 classified, then templated           48 msgs   24%   $0.0002 each
    tier 4  open-ended questions                  54 msgs   27%   $0.0110 each

    blended cost per message   $0.0031
    everything on tier 4       $0.0110      →  72% cheaper, same answers
    TEXT,
    'notes' => [
        'Quality does not drop, because the hard questions still reach the good model. What changes is that the easy ones stop paying for it.',
        'Tier 0 is worth doing first and is often the biggest single slice. An order lookup answered by SQL is faster, exact, and explainable.',
        'Do this measurement before optimising anything else: take 200 real messages and sort them into these tiers by hand. That share is your saving.',
    ],
    'live' => null,
];
