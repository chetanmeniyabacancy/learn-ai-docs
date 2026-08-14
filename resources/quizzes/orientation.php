<?php

return [
    [
        'question' => 'You build a chatbot and the user asks "what did I just say?". What actually makes that work?',
        'options' => [
            'The model keeps a session on Anthropic\'s servers',
            'Your code stores the conversation and resends the whole history on every call',
            'You enable the memory flag on the request',
            'The API returns a conversation ID you pass back',
        ],
        'answer' => 1,
        'explanation' => 'The Messages API is stateless. It remembers nothing between calls, so "memory" is a messages table plus a loop in your application.',
    ],
    [
        'question' => 'Why is reading $message->content[0]->text a bug waiting to happen?',
        'options' => [
            'It is slower than looping',
            'content is a list of blocks — the first one may be a thinking or tool_use block, not text',
            'The SDK returns content as a JSON string',
            'It only works when maxTokens is above 1024',
        ],
        'answer' => 1,
        'explanation' => 'A response is an array of typed blocks. Enable thinking or tools and the first block is no longer text. Always filter on type and concatenate.',
    ],
    [
        'question' => '"Summarise this ticket and tag it with a category" — which shape of AI feature is that?',
        'options' => [
            'Answer — it needs RAG over your documents',
            'Act — it needs tool calling',
            'Transform — text in, structured text out',
            'None of these; it needs a fine-tuned model',
        ],
        'answer' => 2,
        'explanation' => 'Everything needed is already in the input text. No retrieval, no database access — just a transformation, which is the cheapest and most reliable shape.',
    ],
    [
        'question' => 'Where does the Anthropic API key belong?',
        'options' => [
            'In a JavaScript config so the browser can call the API directly',
            'On the server, in .env, read through a config file, never sent to the client',
            'In the database so you can rotate it per tenant',
            'Hard-coded in the service class for simplicity',
        ],
        'answer' => 1,
        'explanation' => 'Every call goes server → Anthropic. A key in the browser is a key on your bill. Use config() rather than env() directly, because env() returns null once configs are cached.',
    ],
    [
        'question' => 'You set maxTokens: 50 and the answer stops mid-sentence. What happened?',
        'options' => [
            'The SDK threw an exception you swallowed',
            'The request returned normally with stopReason "max_tokens" — the answer is truncated',
            'The model decided the answer was complete',
            'You hit a rate limit',
        ],
        'answer' => 1,
        'explanation' => 'maxTokens is a hard ceiling on the output. Hitting it is not an error; the response comes back with stopReason "max_tokens" and truncated content. Check it, especially when the output is JSON.',
    ],
];
