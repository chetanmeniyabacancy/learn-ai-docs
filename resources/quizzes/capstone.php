<?php

return [
    [
        'question' => 'Why expose the handbook search as a tool rather than always retrieving before the model call?',
        'options' => [
            'Tools are cheaper per token than RAG',
            'The model can decide when retrieval is needed — "hi" costs nothing, and it can search with a rewritten query',
            'RAG does not work with tool calling enabled',
            'It avoids needing embeddings',
        ],
        'answer' => 1,
        'explanation' => 'Always-retrieve wastes tokens on questions that need no documents. Letting the model choose also lets it search with better wording than the raw question.',
    ],
    [
        'question' => 'Your assistant should answer policy questions AND order questions. What does the tool for orders look like?',
        'options' => [
            'lookup_order(reference, customer_id)',
            'lookup_my_order(reference) — the customer comes from auth()',
            'search_orders(query) using the same vector index as the handbook',
            'run_query(sql)',
        ],
        'answer' => 1,
        'explanation' => 'Specific records are looked up exactly and scoped in code. Vector search is the wrong tool for identifiers, and a model-supplied customer id is the security hole from module 9.',
    ],
    [
        'question' => 'Which eval case proves your scoping actually works?',
        'options' => [
            '"How long do I have to return something?"',
            '"Where is ORD-1043?" as its real owner',
            '"Show me order ORD-1110" as a customer who does not own it — expecting found:false',
            '"What is your returns email address?"',
        ],
        'answer' => 2,
        'explanation' => 'A negative test is the only one that proves an access control. It should be in the suite so a future refactor cannot quietly remove the scoping.',
    ],
    [
        'question' => 'You report a single quality number for the whole assistant. What is the problem?',
        'options' => [
            'Nothing — one number is easiest to track',
            'It hides whether failures come from retrieval or from generation, so you cannot tell what to fix',
            'It should be reported per user instead',
            'Quality cannot be measured for RAG systems',
        ],
        'answer' => 1,
        'explanation' => 'Report recall@K separately from answer quality. Otherwise you optimise prompts for what is really a retrieval problem — the most common wasted fortnight in RAG work.',
    ],
    [
        'question' => 'What best describes the step from Level 1 to Level 2?',
        'options' => [
            'Learning to train and fine-tune your own models',
            'Moving from you controlling the flow to the model planning and controlling multi-step work',
            'Switching from PHP to Python',
            'Replacing RAG with a bigger context window',
        ],
        'answer' => 1,
        'explanation' => 'Level 2 — agents, memory, MCP, multi-agent systems — is an elaboration of what you have already built. An agent is a tool loop with better planning; agent memory is RAG over conversation history.',
    ],
];
