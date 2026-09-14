<?php

return [
    [
        'question' => 'What is the actual difference between an agent and the Level 1 tool loop?',
        'options' => [
            'Agents use a more capable model',
            'The model decides what step comes next, instead of you writing the sequence',
            'Agents can access the internet',
            'Agents remember previous conversations',
        ],
        'answer' => 1,
        'explanation' => 'The code is nearly identical — the same loop, tools and tool_result messages. What changes is who chooses the order, and that single change is what costs you determinism, predictable cost and easy testing.',
    ],
    [
        'question' => 'Which task is the honest signal that you need an agent?',
        'options' => [
            'Summarise this email and tag it',
            'Answer questions from our handbook',
            'Work out why this invoice does not reconcile, where each step depends on what you just found',
            'Classify 10,000 tickets overnight',
        ],
        'answer' => 2,
        'explanation' => 'If you can write the steps down, write them down — that is a workflow, and it is cheaper, faster and testable. An agent earns its cost only when the sequence genuinely cannot be specified in advance.',
    ],
    [
        'question' => 'Which four ceilings should every agent run have?',
        'options' => [
            'Steps, tokens, spend and wall-clock time',
            'Steps and tokens only',
            'Retries, timeouts, memory and disk',
            'Tool count, model size, temperature and top-p',
        ],
        'answer' => 0,
        'explanation' => 'Each one stops a real failure mode: a loop, a runaway context, a surprise invoice, and a request that never returns. A confused agent inside while(true) is an unbounded invoice.',
    ],
    [
        'question' => 'An agent calls the same tool with the same arguments three times in a row. What is happening?',
        'options' => [
            'It is being thorough and verifying its results',
            'It is stuck: the result does not answer its question and it has no way to tell',
            'The tool is returning cached data',
            'The model is too small for the task',
        ],
        'answer' => 1,
        'explanation' => 'This is the classic loop. The fix is two-part: return clearer failure messages from the tool so the agent knows what went wrong, and detect repeats in code — sending the detection back as a tool result, in language the agent can act on.',
    ],
    [
        'question' => 'You judge a new agent on one impressive run. What is wrong with that?',
        'options' => [
            'Nothing, if the run covered the hard case',
            'A system that varies needs many runs — the spread in steps, cost and outcome is the real behaviour',
            'You should judge it on the cheapest run',
            'One run is fine as long as you check the final answer carefully',
        ],
        'answer' => 1,
        'explanation' => 'Run the same goal ten times and record steps, cost and outcome. The variance is what you will actually ship, and the worst run is what your support team will hear about.',
    ],
];
