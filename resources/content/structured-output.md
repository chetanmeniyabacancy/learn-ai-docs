## Summary

- Attach a **JSON Schema** and the response is guaranteed to match it. Your parser becomes `json_decode()`.
- Use **enums** wherever possible. Every free-text field is a field you normalise forever.
- The `description` on a field is read by the model. Use it.
- Model "not present" explicitly, or the model will invent something.
- The schema guarantees **shape**, not **truth**. Still validate, and verify anything it claims about your data.

## The problem

Your triage prompt returns:

```text
Category: billing
Priority: high
Summary: Customer charged twice for order ORD-1043
```

So you write a parser. Then it returns `Category: Billing`. Then `**Category:** billing`. Then a polite
preamble. Your parser grows a `str_replace`, then a regex, then a retry loop — 60 lines and the most fragile
code in the app.

You are parsing prose. Stop.

## Before and after

```php
// ❌ Hoping
$system = 'Reply with JSON only. No markdown. No explanation.';
$json = json_decode($text, true);
if (! $json) {
    // …retry? log? guess?
}
```

```php
// ✅ Guaranteeing
$message = $client->messages->create(
    model: 'claude-sonnet-5',
    maxTokens: 1024,
    system: 'You triage customer support tickets.',
    messages: [['role' => 'user', 'content' => $ticket->body]],
    outputConfig: [
        'format' => [
            'type' => 'json_schema',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'category' => ['type' => 'string',
                        'enum' => ['billing', 'shipping', 'technical', 'account', 'other']],
                    'priority' => ['type' => 'string',
                        'enum' => ['low', 'normal', 'high', 'urgent']],
                    'summary' => ['type' => 'string',
                        'description' => 'One sentence, under 140 characters.'],
                ],
                'required' => ['category', 'priority', 'summary'],
                'additionalProperties' => false,
            ],
        ],
    ],
);

$data = json_decode(Claude::text($message), true);
// $data['category'] is one of your five values. Guaranteed.
```

Delete any "reply with JSON only" instruction. The schema is the instruction now.

## Writing schemas that behave

**Enums are your best friend.** Free text is a field you normalise forever. An enum maps straight onto a
database column or PHP enum.

```php
'status' => ['type' => 'string', 'enum' => array_column(TicketStatus::cases(), 'value')],
```

**`description` is a prompt.** The model reads it. Put the rule with the field.

```php
'summary' => [
    'type' => 'string',
    'description' => 'One sentence, under 140 characters. No greeting. Present tense.',
],
```

**`additionalProperties: false` and a full `required` list.** Otherwise you get optional fields, and optional
fields mean null checks everywhere.

**Model absence explicitly:**

```php
'order_reference' => [
    'type' => ['string', 'null'],
    'description' => 'The order reference exactly as written, or null if none is mentioned. Never invent one.',
],
```

That description carries the weight. Without it, a required string field pressures the model to produce
*something* — and something is how an invented order number reaches your database.

### Supported, and not

Supported: objects, arrays, `string`, `integer`, `number`, `boolean`, `null`, `enum`, `const`, `anyOf`,
`$ref`, `additionalProperties: false`, and formats like `date`, `email`, `uri`, `uuid`.

**Not supported:** recursive schemas, `minimum`/`maximum`, `minLength`/`maxLength`, complex array constraints.
So "under 140 characters" goes in the `description`, and you validate it in PHP.

## Validate anyway

The shape is guaranteed. The content is not. `summary` may be 200 characters; the category may be wrong.

```php
$data = $this->claude->extract(
    system: TriagePrompt::system(),
    input: $ticket->body,
    schema: TriagePrompt::schema(),
);

$validated = Validator::make($data, [
    'category' => ['required', Rule::enum(TicketCategory::class)],
    'priority' => ['required', Rule::enum(TicketPriority::class)],
    'summary'  => ['required', 'string', 'max:200'],
    'order_reference' => ['nullable', 'regex:/^ORD-\d+$/', Rule::exists('orders', 'reference')],
])->validate();
```

Two layers: the schema makes it **parseable**, validation makes it **believable**. That `exists` rule is the
important one — it is the difference between "the model said this order exists" and "this order exists".

## Where this pays off

| Job | Schema returns |
|---|---|
| Invoice extraction from PDF | supplier, number, date, line items[], total |
| CV screening | years experience, skills[], seniority, red flags[] |
| Meeting notes → tasks | tasks[] with owner, due date, priority |
| Product feed cleanup | title, brand, category enum, attributes{} |
| Review sentiment + routing | rating, aspects[], team enum |

Each is a queued job writing rows to a table. No chat interface anywhere.

## A reusable helper

```php
public function extract(string $system, string $input, array $schema, ?string $model = null): array
{
    $message = $this->client->messages->create(
        model: $model ?? config('claude.model'),
        maxTokens: 2048,
        system: $system,
        messages: [['role' => 'user', 'content' => $input]],
        outputConfig: ['format' => ['type' => 'json_schema', 'schema' => $schema]],
    );

    if ($message->stopReason === 'max_tokens') {
        // Truncated JSON is invalid JSON. Fail loudly.
        throw new ExtractionFailed('Hit the token ceiling; raise maxTokens.');
    }

    return json_decode(Claude::text($message), true, flags: JSON_THROW_ON_ERROR);
}
```

That `max_tokens` check matters more here than anywhere. Truncated prose is shorter. Truncated JSON is a parse
error at 3am.

## Common mistakes

- **`maxTokens` too low for the schema.** Big schemas make big objects.
- **Free text where an enum belongs.** `"Billing Issue"` on Monday, `"billing"` on Tuesday.
- **Trusting the values.** A well-formed wrong `order_reference` is still wrong. Look it up.
- **Leaving "respond with only JSON" in the prompt.** Redundant, and it competes with the schema.
- **One giant schema for six jobs.** Small schemas per task are more accurate and cheaper.

## You should now be able to

- [ ] Attach a JSON Schema and delete your regex parser
- [ ] Choose enums over free text
- [ ] Model "not present" without inviting invention
- [ ] Validate values after the shape is guaranteed
- [ ] See that most useful AI features have no chat UI

## Practice

1. In the **Live run** page, panel 2: feed the schema an angry email, a one-word email, and one in another
   language. Watch the shape hold.
2. Take a text column in your database and write a schema turning it into three structured columns.
3. Add a nullable field for something often absent, and get the `description` right.
4. Wire it into a queued job over 100 rows and check the cost.
