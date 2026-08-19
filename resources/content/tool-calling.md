## Summary

- You describe your functions to the model. The model asks for one by name, with arguments. **Your code is what actually runs it.**
- The model never touches your database. It can only reach the functions you chose to describe.
- The loop is: send the request → get `stopReason: tool_use` → run the tool → send back a `tool_result` → repeat until `end_turn`.
- Four rules: send the assistant's turn back unchanged, return one result per call with the matching `toolUseID`, put all results in **one** user message, and always **limit the loop**.
- **Never let the model choose whose data to read.** The user comes from `auth()`.

## The problem

A customer types:

> Where is my order ORD-1043?

The model has never seen your `orders` table. So it has three choices: refuse to answer, tell the customer to
check their account, or invent a delivery date. Two of those are useless and one is dangerous.

Meanwhile the real answer is one Eloquent query away.

## The loop

```text
User: "Where is my order ORD-1043?"
   ↓
Claude → stopReason: "tool_use"
         lookup_order(reference: "ORD-1043")
   ↓
YOUR CODE runs the query
   ↓
tool_result → {"status":"shipped","expected_on":"2026-08-14","carrier":"DHL"}
   ↓
Claude → "Your order shipped and DHL expects it on 14 August."
```

That is two API calls, one database query, and one natural answer. The model picked the tool and the argument.
Your code kept full control of the data.

## Defining a tool

A tool is three things: a name, a description, and a JSON Schema for its arguments.

```php
$tools = [
    [
        'name' => 'lookup_order',
        'description' => 'Look up one order by its reference (e.g. ORD-1043). '
            .'Returns status, totals and delivery dates. '
            .'Use this whenever the customer names an order reference.',
        'inputSchema' => [
            'type' => 'object',
            'properties' => [
                'reference' => ['type' => 'string', 'description' => 'The order reference, like ORD-1043.'],
            ],
            'required' => ['reference'],
        ],
    ],
];
```

**The description is the entire interface.** It is everything the model knows about your function. If the
description is vague, the tool gets called at the wrong moments with the wrong arguments, and no amount of
system prompt will fix that.

```text
❌ "Gets order info."
✅ "Look up one order by reference. Returns status, totals and delivery dates.
    Use whenever the customer names an order reference. Returns found:false if
    no such order exists — do not retry with a guessed reference."
```

## The agent loop

```php
public function answer(string $question): string
{
    $messages = [['role' => 'user', 'content' => $question]];

    // Always cap. A confused model plus while(true) is an unbounded invoice.
    for ($turn = 0; $turn < 5; $turn++) {
        $message = $this->client->messages->create(
            model: config('claude.model'),
            maxTokens: 2048,
            system: $this->systemPrompt(),
            tools: $this->tools(),
            messages: $messages,
        );

        if ($message->stopReason !== 'tool_use') {
            return Claude::text($message);       // final answer
        }

        // Echo the assistant turn VERBATIM — it carries the tool_use blocks
        $messages[] = ['role' => 'assistant', 'content' => $message->content];

        $results = [];

        foreach ($message->content as $block) {
            if ($block->type !== 'tool_use') {
                continue;
            }

            $results[] = [
                'type' => 'tool_result',
                'toolUseID' => $block->id,        // must match the request
                'content' => json_encode($this->run($block->name, $block->input)),
            ];
        }

        // ALL results for a turn go back in ONE user message
        $messages[] = ['role' => 'user', 'content' => $results];
    }

    return 'I could not work that out — let me pass you to a colleague.';
}
```

Four rules are hidden in that code. Break any one of them and you get a confusing 400 error:

1. Add the assistant message back **exactly as you received it**, including the tool blocks.
2. Send one `tool_result` for each `tool_use`, with the same `toolUseID`.
3. Put all results from one turn in a **single** user message. If you split them, the model slowly stops making
   parallel calls.
4. Limit the number of loops.

## Executing the tool

This function is the security boundary for the whole feature. Treat it exactly like a controller handling a
public request, because that is what it is.

```php
private function run(string $name, array $input): array
{
    return match ($name) {
        'lookup_order' => $this->lookupOrder($input),
        'find_orders_by_email' => $this->findByEmail($input),
        default => ['error' => "Unknown tool: {$name}"],
    };
}

private function lookupOrder(array $input): array
{
    $reference = strtoupper(trim((string) ($input['reference'] ?? '')));

    $order = Order::query()
        // Customer comes from YOUR session — never from the model's arguments
        ->where('customer_id', $this->customer->id)
        ->where('reference', $reference)
        ->first();

    if (! $order) {
        return ['found' => false];
    }

    // A deliberate projection. This is the model's whole view of the row:
    // no cost price, no internal notes, no other customers.
    return [
        'found' => true,
        'reference' => $order->reference,
        'status' => $order->status,
        'total' => $order->total.' '.$order->currency,
        'expected_on' => $order->expected_on?->toDateString(),
        'carrier' => $order->carrier,
    ];
}
```

> **The rule that matters most:** decide the customer in *your* code, using the logged-in user. Never take it
> from an argument the model sent. If `lookup_order` accepts a `customer_id`, then anybody who types "look up
> order 500 for customer 12" has just read another person's data. Module 9 covers this in detail, but learn it
> now.

## Designing the tool surface

- **Keep the tools few and clearly different.** If two descriptions could both answer the same question, merge
  them or make each one sharper.
- **Start read-only.** Most support assistants are already useful with lookups alone.
- **Return small results.** Every tool result becomes input tokens on all the later turns. Returning fifty
  fields when three would do costs money and lowers accuracy.
- **Return errors as results, not exceptions.** `['found' => false]` lets the model recover and explain. A
  thrown exception just kills the conversation.
- **Dangerous actions need a human.** Let the model call `request_refund()`, which creates a *pending* record.
  Never let it call `issueRefund()`.

## Common mistakes

- **Vague descriptions.** This is the number one reason for both "it never calls my tool" and "it calls my tool
  constantly".
- **Trusting ids that came from the model** for permission checks. That is user input wearing a disguise.
- **A missing or wrong `toolUseID`**, which gives you a 400.
- **Splitting tool results across several messages**, after which parallel calls quietly stop happening.
- **No limit on the loop**, so one bad prompt burns credit at machine speed.
- **A "run any SQL" tool.** It sounds clever. It is a data breach with extra steps.

## You should now be able to

- [ ] Explain why the model never touches your database directly
- [ ] Write a description clear enough that the tool is called at the right time
- [ ] Build the loop with correct ids and a hard limit
- [ ] Filter every query by the logged-in user
- [ ] Decide which actions must wait for human approval

## Practice

1. On the **Live run** page, panel 3: ask "Where is my order ORD-1043?" and read the tool trace.
2. Ask "I forgot my order number, my email is priya@example.com". A different tool runs, with no extra code
   from you.
3. Ask about `ORD-9999`, which does not exist. Watch it say so honestly.
4. In your own app, write one read-only tool over a real table and get the loop working.
5. Now try to break it. Ask for somebody else's order. If that works, stop and fix your scoping first.
