<?php

return [
    'id' => 'idempotent-action',
    'module' => 'production-agents',
    'title' => 'Making a refund safe to attempt twice',
    'intro' => 'Retries, duplicate queue deliveries and resumed runs all cause the same action to be attempted again. The first duplicate refund is a bug. The second is a pattern, and a customer asking why they were paid twice.',
    'language' => 'php',
    'code' => <<<'PHP'
    public function issueRefund(int $orderId, int $amount, string $stepKey): array
    {
        // Derived from run + step, so the same logical action always produces
        // the same key — even after a retry or a resume.
        $key = "refund:{$this->run->id}:{$stepKey}";

        return DB::transaction(function () use ($key, $orderId, $amount) {
            // A unique index on idempotency_key does the real work here.
            if ($existing = AgentAction::where('idempotency_key', $key)->first()) {
                return ['already_done' => true, 'reference' => $existing->reference];
            }

            // Write the INTENT before doing the thing. A row saying "about to
            // refund" is recoverable; a refund with no row is not.
            $action = AgentAction::create([
                'agent_run_id' => $this->run->id,
                'idempotency_key' => $key,
                'type' => 'refund',
                'payload' => ['order_id' => $orderId, 'amount' => $amount],
                'status' => 'pending_review',      // still not done — a human decides
            ]);

            return ['created' => true, 'reference' => $action->reference];
        });
    }
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    the failure this prevents

    14:22:01  run 4471 step 6   issueRefund(ORD-1043, 2400)   → created REF-881
    14:22:04  worker crashed before marking the ticket resolved
    14:32:00  orphan detector finds run 4471 stuck in "running"

    without idempotency
      14:32:01  resume replays step 6  → SECOND refund of 2400 issued
      next day  customer asks why they were paid twice, nobody can say

    with idempotency
      14:32:01  resume replays step 6
                key refund:4471:6 already exists
                → {"already_done": true, "reference": "REF-881"}
      and because an action was in flight at the crash, this run is marked
      failed for a human to check rather than resumed at all
    TEXT,
    'notes' => [
        'Note the last line: a run with an action in flight is <strong>not</strong> auto-resumed. A human decides, because the alternative is charging someone twice to save a support ticket.',
        'Pass the idempotency key to the payment provider too, when they support one. Then even a duplicated HTTP call is only one refund.',
        'The status is <code>pending_review</code>, not <code>done</code>. An agent\'s most powerful verb should be "propose".',
    ],
    'live' => null,
];
