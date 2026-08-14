<?php

return [
    'id' => 'four-questions',
    'module' => 'learning-types',
    'title' => 'One table, four kinds of learning',
    'intro' => 'The same customers table, four questions. Getting the kind wrong is how a project fails before any code is written.',
    'language' => 'php',
    'code' => <<<'PHP'
    // The data everyone is arguing about
    Schema::create('customers', function (Blueprint $table) {
        $table->id();
        $table->integer('tenure_months');
        $table->integer('logins_30d');
        $table->integer('support_tickets');
        $table->decimal('monthly_spend');
        $table->boolean('churned')->nullable();   // ← known only for past customers
    });

    // 1. SUPERVISED      — "which customers will cancel?"
    //    inputs + known answers → predict the answer for new rows

    // 2. UNSUPERVISED    — "what natural groups exist?"
    //    inputs only → find structure

    // 3. REINFORCEMENT   — "what email sequence maximises retention?"
    //    actions + rewards → learn a policy

    // 4. SELF-SUPERVISED — "what word comes next?"
    //    raw text labels itself → this is how your LLM was built
    PHP,
    'output_language' => 'text',
    'output' => <<<'TEXT'
    1 · SUPERVISED (classification)
        train on 50,000 past customers where `churned` is known
        → "customer 8812: 0.83 probability of churning"
        tool: gradient boosting.  NOT an LLM.

    2 · UNSUPERVISED (clustering)
        no labels used at all
        → cluster 0: high spend, low frequency   (n=4,102)
          cluster 1: low spend, high frequency   (n=18,340)
          cluster 2: new, barely active          (n=9,455)
          cluster 3: long tenure, high usage     (n=6,203)
        naming them is YOUR job — the algorithm just returns groups

    3 · REINFORCEMENT
        needs a simulator or a lot of expensive real-world trial
        → honestly: run an A/B test instead

    4 · SELF-SUPERVISED
        "The refund window is 30 ___"  → label: "days"
        no human wrote that label — every sentence is a free example
        → this is pretraining. It is why LLMs exist.
    TEXT,
    'notes' => [
        'Only the fourth one produced the model you call. Recognising the other three — and saying "this does not need an LLM" — is worth more to your team than being able to build any of them.',
        'The `churned` column being <code>nullable</code> is the whole story: it is filled in for the past and unknown for the present. That is what makes it a label.',
        'Clustering always returns groups. Whether they mean anything is a judgement call, because there is no right answer to check against.',
    ],
    'live' => null,
];
