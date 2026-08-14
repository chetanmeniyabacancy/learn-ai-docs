<?php

return [
    'id' => 'vague-vs-specific',
    'module' => 'prompt-engineering',
    'title' => 'Vague prompt vs specific prompt',
    'intro' => 'Same input, same model, two prompts. Every degree of freedom you leave will eventually be exercised — and rarely in your favour.',
    'language' => 'php',
    'code' => <<<'PHP'
    $complaint = "I ordered a laptop stand on the 2nd. It arrived on the 9th, "
        ."three days late, and the box was crushed. The stand itself seems fine "
        ."but I'd like the shipping refunded at least.";

    // ❌ Vague
    $a = $claude->ask(
        system: 'You are helpful.',
        prompt: "Summarise this complaint:\n\n{$complaint}",
    );

    // ✅ Specific
    $b = $claude->ask(
        system: <<<'TXT'
        Summarise customer complaints in exactly three bullet points:
        - What went wrong
        - What the customer wants
        - Whether they mention a refund (yes/no)

        Output only the bullets. No preamble, no closing line.
        TXT,
        prompt: $complaint,
    );
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    ── A: vague prompt ──────────────────────────
    Certainly! Here's a summary of the complaint:

    The customer ordered a laptop stand on the 2nd of the month, which arrived on
    the 9th — approximately three days later than expected. While the product
    itself appears to be undamaged and in working condition, the packaging arrived
    in a crushed state. The customer is requesting a refund of the shipping costs
    as compensation for the delayed delivery and damaged packaging.

    Let me know if you'd like me to help draft a response!

    ── B: specific prompt ───────────────────────
    - Delivery arrived three days late and the box was crushed
    - Customer wants the shipping cost refunded
    - Mentions refund: yes
    TEXT,
    'notes' => [
        'A is not <em>wrong</em>. It is unparseable, four times the tokens, and different in shape every run.',
        '"Output only the bullets. No preamble" is doing real work. Left alone, a chat model opens with a friendly sentence because that is what helpful text looks like.',
        'B is 31 output tokens against A\'s 96 — three times cheaper, on every single call, forever.',
    ],
    'live' => 'chat',
];
