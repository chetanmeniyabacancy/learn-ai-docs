<?php

return [
    'id' => 'rag-vs-finetune',
    'module' => 'training-an-llm',
    'title' => '"Can we train it on our data?"',
    'intro' => 'The four ways to make a model know your business, side by side. Most people asking for the first one need the third.',
    'language' => 'text',
    'code' => <<<'TEXT'
    The request:
      "Make the assistant know our internal handbook."

    Four possible answers:

      1. Pretrain from scratch
      2. Fine-tune an open model on the handbook
      3. RAG — retrieve the right passage, put it in the request
      4. Prompting — put the rules in the system prompt
    TEXT,
    'output_language' => 'text',
    'output' => <<<'TEXT'
                    cost         time      right when
    ──────────────────────────────────────────────────────────────
    1  Pretrain     $10M+        months    you are an AI lab
    2  Fine-tune    $100–$10k    days      style, format, narrow task
    3  RAG          cents/query  hours     ← facts, documents, policies
    4  Prompting    cents/query  minutes   behaviour, tone, output shape

    Why RAG beats fine-tuning for FACTS:

      handbook changed?      RAG: next request     fine-tune: retrain
      show the source?       RAG: cite passage 2   fine-tune: impossible
      delete a customer?     RAG: delete a row     fine-tune: impossible
      per-tenant access?     RAG: WHERE clause     fine-tune: impossible
      accuracy on facts?     RAG: high             fine-tune: unreliable

    Fine-tuning IS right for: a fixed output format prompting can't
    reach, a domain writing style, or a narrow task run millions of
    times where a small tuned model is cheaper.
    All three are about HOW, not WHAT.
    TEXT,
    'notes' => [
        'There are four orders of magnitude between neighbouring rows. When someone proposes row 1, it is usually a misunderstanding rather than a plan.',
        'Facts learned by fine-tuning are diffuse and uncitable. The model blends them with half-remembered pretraining and you cannot tell which is which.',
        'Have this answer ready. The question comes up in nearly every company adopting AI, and a clear one-minute reply is worth a lot.',
    ],
    'live' => null,
];
