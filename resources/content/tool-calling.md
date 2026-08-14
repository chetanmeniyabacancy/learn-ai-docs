## Summary

- You describe functions. The model asks for one by name with arguments. **Your code runs it.**
- The model never touches your database and can only reach the functions you gave it.
- The loop: call → `stopReason: tool_use` → run tool → send `tool_result` → repeat until `end_turn`.
- Four rules: echo the assistant turn unchanged, one result per call with the matching `toolUseID`, all
  results in **one** user message, **cap the loop**.
- **Never let the model choose whose data to read.** Identity comes from `auth()`.

## The problem

A customer types:

> Where is my order ORD-1043?

The model has never seen your `orders` table. Its options: refuse, tell them to check their account, or invent
a delivery date. Two are useless, one is dangerous.

The answer is one Eloquent query away.

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

Two API calls, one query, one natural answer. The model chose the tool and the argument; you kept the data.

## Defining a tool

A name, a description, and a JSON Schema for the arguments.

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

**The description is the whole interface.** It is all the model knows about your function. A vague description
means the tool gets called at the wrong times with the wrong arguments, and no system prompt fixes it.

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

Four rules hide there. Break any and you get a confusing 400:

1. Append the assistant message **unchanged**, including tool blocks.
2. One `tool_result` per `tool_use`, with the matching `toolUseID`.
3. All results from one turn in a **single** user message. Splitting them trains the model to stop making
   parallel calls.
4. Cap the loop.

## Executing the tool

This is the security boundary of the whole feature. Treat it like a controller receiving a public request —
because it is.

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

> **The rule that matters most:** scope by the authenticated user in *your* code, never by an argument the
> model supplies. If `lookup_order` takes a `customer_id`, then anyone who types "look up order 500 for
> customer 12" has read someone else's data. Module 9 goes deeper; internalise this now.

## Designing the tool surface

- **Few tools, clearly separated.** If two descriptions could both answer a question, merge or sharpen them.
- **Read-only first.** Most support assistants are valuable with lookups alone.
- **Return small results.** A tool result is input tokens on every later turn. Fifty fields where three would
  do is a cost and accuracy problem.
- **Errors are results, not exceptions.** `['found' => false]` lets the model recover. A thrown exception ends
  the conversation.
- **Destructive actions get a human.** Have the model call `request_refund()`, which creates a *pending*
  record. Never `issueRefund()`.

## Common mistakes

- **Vague descriptions** — the top cause of both "it never calls my tool" and "it calls it constantly".
- **Trusting model-supplied identifiers** for authorisation. That is user input in a costume.
- **Missing or mismatched `toolUseID`** → 400.
- **Splitting tool results across messages** → parallel calls quietly stop.
- **No loop cap** → a bad prompt burns credit at machine speed.
- **A "run SQL" tool.** Sounds elegant; it is a data breach with extra steps.

## You should now be able to

- [ ] Explain why the model never touches your database
- [ ] Write a description precise enough to be called at the right time
- [ ] Implement the loop with correct ids and a hard cap
- [ ] Scope every query by the session user
- [ ] Decide which actions need human approval

## Practice

1. **Live run** page, panel 3: ask "Where is my order ORD-1043?" and read the tool trace.
2. Ask "I forgot my order number, my email is priya@example.com" — different tool, no extra code.
3. Ask about `ORD-9999`, which does not exist. Watch it report that honestly.
4. In your own app, write one read-only tool over a real table and get the loop working.
5. Then try to break it: ask for someone else's order. If it works, fix your scoping first.
