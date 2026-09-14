<?php

return [
    [
        'question' => 'What problem does MCP actually solve?',
        'options' => [
            'It makes models better at using tools',
            'It gives tools a standard shape, so one integration works with any client that speaks the protocol',
            'It provides authentication and authorisation for AI tools',
            'It reduces the token cost of tool definitions',
        ],
        'answer' => 1,
        'explanation' => 'It is plumbing, not intelligence. Without it, twelve tools across four applications is 48 definitions to maintain, each with its own argument names and its own drift.',
    ],
    [
        'question' => 'What is the difference between an MCP tool and an MCP resource?',
        'options' => [
            'Tools are local, resources are remote',
            'A tool does something; a resource is something you read, addressed by URI, and reading it should be safe and repeatable',
            'Resources are faster',
            'Tools return JSON, resources return text',
        ],
        'answer' => 1,
        'explanation' => 'lookup_order is an action with arguments. handbook://returns-policy is a thing you read. Keeping the distinction means a client can fetch context without triggering side effects.',
    ],
    [
        'question' => 'Does MCP handle authorisation for you?',
        'options' => [
            'Yes, the protocol includes a permission model',
            'No — it standardises transport and discovery. Identity, scoping and audit are still entirely yours',
            'Yes, if you use HTTP transport',
            'Only for resources, not tools',
        ],
        'answer' => 1,
        'explanation' => 'A tool that accepts a user_id argument is exactly as wrong over MCP as it was over HTTP. Identity comes from the session or token that reached your server, and every call should be logged with the caller.',
    ],
    [
        'question' => 'When is a plain function the better choice than an MCP server?',
        'options' => [
            'Whenever the tool touches a database',
            'When exactly one application uses the tool and nothing else ever will',
            'When the tool is read-only',
            'When you are writing PHP rather than Python',
        ],
        'answer' => 1,
        'explanation' => 'A single-consumer tool does not need a protocol. MCP earns its keep when two or more clients need the same capability, or when you want Claude Code or a desktop client to reach your systems.',
    ],
    [
        'question' => 'You enable a third-party MCP server in your agent. What is the new risk?',
        'options' => [
            'Higher latency only',
            'You did not write those tools, and their descriptions and results now flow into your prompt — including any injected text',
            'Your API key is shared with them',
            'The model will ignore your own tools',
        ],
        'answer' => 1,
        'explanation' => 'Their tool descriptions become part of your prompt, and their results are untrusted text your agent will read. Read what the tools do before letting an agent call them — "filesystem access" is a sentence that should slow you down.',
    ],
];
