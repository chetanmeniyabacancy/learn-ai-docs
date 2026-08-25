<?php

return [
    [
        'question' => 'You read ten agent traces and it does the same five things in the same order every time. What does that tell you?',
        'options' => [
            'The agent is working well and should be left alone',
            'It is not planning — it is rediscovering a fixed sequence every run, and paying for the privilege. Write the workflow.',
            'You need a bigger model',
            'You should add more tools',
        ],
        'answer' => 1,
        'explanation' => 'A stable order is the definition of a workflow. Writing it down makes the system cheaper, faster, testable per step, and fixable when one step degrades.',
    ],
    [
        'question' => 'What is the routing shape mainly for?',
        'options' => [
            'Choosing which model to use for every request',
            'Classifying the request first, then sending it down a specialised path — including paths with no model at all',
            'Distributing load between servers',
            'Retrying failed requests on a different endpoint',
        ],
        'answer' => 1,
        'explanation' => 'Routing is where most real cost savings come from. Order lookups become SQL, password resets become a link, and only the genuinely open-ended tail reaches an expensive generation call.',
    ],
    [
        'question' => 'In the evaluate-and-retry shape, why cap the retries at two?',
        'options' => [
            'API rate limits require it',
            'A third attempt rarely helps and the loop can oscillate between two drafts forever',
            'Because the critic becomes less accurate each time',
            'To stay inside the context window',
        ],
        'answer' => 1,
        'explanation' => 'Quality gains flatten quickly, while cost and latency keep rising. Two attempts, then hand it to a human — an uncapped generate-critique loop is a bill with no ceiling.',
    ],
    [
        'question' => 'In a refund workflow, which part should NOT be done by the model?',
        'options' => [
            'Classifying the ticket',
            'Extracting the order reference and reason',
            'Checking eligibility and computing the amount — those are rules and arithmetic, so they belong in SQL and code',
            'Drafting the reply to the customer',
        ],
        'answer' => 2,
        'explanation' => 'Use the model for classification, extraction and writing. Anything you can express as a rule should be a rule: it is free, instant, exact and explainable — and it cannot be argued into a different answer.',
    ],
    [
        'question' => 'Why make each workflow step its own queued job?',
        'options' => [
            'It is required by Laravel',
            'A failure retries that step only, instead of re-running and re-billing the whole pipeline — and the queue shows you which step is slow',
            'It reduces token usage',
            'It allows the steps to run in any order',
        ],
        'answer' => 1,
        'explanation' => 'Local failure handling is why workflows survive production. You also get observability for free: the queue tells you which step fails, which is slow, and how often.',
    ],
];
