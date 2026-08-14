<?php

return [
    'id' => 'capstone-wiring',
    'module' => 'capstone',
    'title' => 'The whole assistant, wired together',
    'intro' => 'Every Level 1 idea in one request. Notice how small it is once each piece is doing its own job — and that RAG is a tool here rather than something you always run.',
    'language' => 'php',
    'code' => <<<'PHP'
    class Assistant
    {
        public function answer(User $user, string $question): string
        {
            $messages = [['role' => 'user', 'content' => $question]];

            for ($turn = 0; $turn < 5; $turn++) {                    // module 4: capped loop
                $message = $this->client->messages->create(
                    model: config('claude.model'),
                    maxTokens: 2048,
                    system: [[                                       // module 10: cached prefix
                        'type' => 'text',
                        'text' => AssistantPrompt::system(),
                        'cacheControl' => ['type' => 'ephemeral'],
                    ]],
                    tools: [
                        // module 7: RAG as a tool, so "hi" costs nothing
                        AssistantTools::searchHandbook(),
                        // module 9: "my", not "by customer id"
                        AssistantTools::lookupMyOrder(),
                    ],
                    messages: $messages,
                );

                $this->log($user, $message);                         // module 10: ai_calls row

                if ($message->stopReason === 'refusal') {            // module 1: check first
                    return __('assistant.refused');
                }

                if ($message->stopReason !== 'tool_use') {
                    return Claude::text($message);
                }

                $messages[] = ['role' => 'assistant', 'content' => $message->content];
                $messages[] = ['role' => 'user', 'content' => $this->runTools($message, $user)];
            }

            return __('assistant.handover');                         // graceful give-up
        }
    }
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    Q: "my order ORD-1043 is late — can I get the shipping refunded?"

    turn 1  tool_use  lookup_my_order(reference: "ORD-1043")
            → {"found":true,"status":"shipped","expected_on":"2026-08-14",
               "carrier":"DHL Express"}

    turn 2  tool_use  search_handbook(query: "refund shipping cost late delivery")
            → [0] Returns policy › Return shipping costs
              [1] Shipping and delivery › Missing parcels

    turn 3  end_turn
            "Your order ORD-1043 shipped and DHL Express expects to deliver it on
             14 August, so it is still in transit rather than lost. We refund the
             original delivery charge when the delay is our fault [0]; a domestic
             parcel is only treated as lost after 7 working days without tracking
             movement [1]. I've raised this with support to check which applies."

    ─────────────────────────────────────────────────────────
    3 API calls    input 8,940 (8,412 cached)    output 214
    cost $0.0059   p95 latency 2.9s
    TEXT,
    'notes' => [
        'Two tools, one loop, one cached system prompt. Everything else — chunking, embeddings, retrieval, scoping — is behind those two tool implementations.',
        'The model chose to look up the order <em>first</em> and then search the policy. You did not write that sequence; you made both capabilities available and it planned the order.',
        '8,412 of 8,940 input tokens came from cache, which is why three API calls cost half a cent instead of three.',
        'This is the boundary with Level 2: an agent is this loop with better planning and more tools. There is nothing new to learn, only more of it.',
    ],
    'live' => 'tools',
];
