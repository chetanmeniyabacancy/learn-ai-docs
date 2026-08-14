<?php

return [
    'id' => 'routing-decision',
    'module' => 'classical-vs-llm',
    'title' => 'A hybrid pipeline: LLM only for the hard tail',
    'intro' => 'The best real systems are not "AI" or "not AI". Cheap deterministic steps handle most traffic; the LLM handles what genuinely needs language.',
    'language' => 'php',
    'code' => <<<'PHP'
    public function handle(Message $message): Reply
    {
        // 1. Regex — free, instant, exact
        if (preg_match('/\bORD-\d+\b/', $message->body, $m)) {
            return $this->orderStatus($m[0]);           // SQL lookup
        }

        // 2. Small classifier — ~1ms, effectively free
        if ($this->spamClassifier->predict($message) > 0.9) {
            return Reply::dropped();
        }

        // 3. LLM — structured output, ~$0.0004
        $intent = $this->claude->extract($message->body, IntentSchema::get());

        // 4. Template — free
        if ($template = Template::for($intent->name)) {
            return $template->render($intent);
        }

        // 5. LLM + RAG — the expensive path, ~$0.004
        return $this->knowledgeBase->answer($message->body, $message->user);
    }
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    10,000 messages through this pipeline:

      step                    handled    cost
      ─────────────────────────────────────────────
      1  regex → SQL           3,100     $0.00
      2  spam classifier       1,400     $0.00
      3  LLM intent            5,500     $2.20
      4  template match        3,900     $0.00
      5  LLM + RAG             1,600     $6.40
                              ──────    ──────
                              10,000     $8.60

    Everything through the LLM instead:  ~$52.00
    Same quality. 6× the cost.

    Only 16% of messages ever reached the expensive path.
    TEXT,
    'notes' => [
        'Step 1 is the important one. An order reference is an exact lookup — SQL does it perfectly, free, in under a millisecond. Vector search and LLMs are both wrong tools for identifiers.',
        'The LLM earns its place at steps 3 and 5, where the input is open-ended language. That is its actual strength.',
        'Ask this before building any AI feature: what happens when it is wrong, who notices, and how fast? A drafted reply a human approves is a soft failure. An approved refund is not.',
    ],
    'live' => null,
];
