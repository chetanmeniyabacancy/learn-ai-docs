<?php

return [
    [
        'question' => 'What do the three training stages each produce?',
        'options' => [
            'Three separate models that vote on the answer',
            'Pretraining creates capability, supervised fine-tuning creates form, preference tuning creates character',
            'Increasingly compressed versions of the same model',
            'A base model, a safety filter and an API layer',
        ],
        'answer' => 1,
        'explanation' => 'Knowledge and reasoning come from pretraining; answering-a-question-with-an-answer comes from SFT; tone, calibration and refusals come from preference tuning.',
    ],
    [
        'question' => 'A base model, asked "What is the capital of France?", replies with more quiz questions and an answer key. Why?',
        'options' => [
            'It is broken',
            'It was only trained to continue text, and that line looks like it came from a worksheet',
            'The temperature is too high',
            'It has not been given a system prompt',
        ],
        'answer' => 1,
        'explanation' => 'Not wrong, exactly — it correctly continued the document. Supervised fine-tuning is what teaches it that a question deserves an answer.',
    ],
    [
        'question' => 'Your company wants the assistant to know its internal handbook. What should you recommend?',
        'options' => [
            'Fine-tune an open model on the handbook',
            'RAG — retrieve the relevant passages and put them in the request',
            'Pretrain a small model from scratch on company documents',
            'Paste the entire handbook into every system prompt',
        ],
        'answer' => 1,
        'explanation' => 'Facts learned in fine-tuning are diffuse and uncitable, your handbook changes, and you cannot permission or delete a weight. RAG handles all four problems and costs cents.',
    ],
    [
        'question' => 'When IS fine-tuning genuinely the right call?',
        'options' => [
            'Whenever you have company-specific data',
            'For a very specific output format, a domain style, or a narrow high-volume task where a small tuned model beats a large prompted one on cost',
            'When you need the model to cite sources',
            'When data must be deletable on request',
        ],
        'answer' => 1,
        'explanation' => 'All three are about *how* the model behaves, not *what* it knows. Facts belong in the context; behaviour can live in the weights.',
    ],
    [
        'question' => 'Which is true of knowledge in the weights versus knowledge in the context?',
        'options' => [
            'Weights are fresher because they are trained on more data',
            'Context can be cited, permissioned, updated and deleted; weights can be none of those',
            'Context knowledge is cheaper because it is not billed',
            'Both can be updated without retraining',
        ],
        'answer' => 1,
        'explanation' => 'This table is the reason Level 1 exists. Prompting, tools and RAG are all mechanisms for getting the right knowledge into the second column at the right moment.',
    ],
];
