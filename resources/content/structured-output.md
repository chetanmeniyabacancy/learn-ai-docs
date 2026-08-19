## Summary

- Attach a **JSON Schema** to the request and the reply is guaranteed to match that shape. Your parser becomes a single `json_decode()`.
- Use **enums** wherever you can. Every free-text field is a field you will keep cleaning up forever.
- The model reads the `description` you write on each field, so use it to give the rule.
- Say clearly what "not present" looks like, otherwise the model will invent a value.
- The schema guarantees the **shape**, not the **truth**. Still validate, and still check anything it claims about your data.

## The problem

Your triage prompt returns this:

```text
Category: billing
Priority: high
Summary: Customer charged twice for order ORD-1043
```

So you write a parser for it. Then one day it returns `Category: Billing`. Then `**Category:** billing`. Then a
polite sentence before the fields. Your parser grows a `str_replace`, then a regex, then a retry loop. Now it is
60 lines long and the most fragile code in your app.

The real problem is that you are parsing prose. Stop doing that.

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

Now remove any "reply with JSON only" line from your prompt. The schema is the instruction.

## Writing schemas that behave

**Enums are your best friend.** A free-text field is something you will normalise again and again. An enum maps
directly onto a database column or a PHP enum.

```php
'status' => ['type' => 'string', 'enum' => array_column(TicketStatus::cases(), 'value')],
```

**The `description` is a prompt.** The model reads it, so keep each rule next to the field it belongs to.

```php
'summary' => [
    'type' => 'string',
    'description' => 'One sentence, under 140 characters. No greeting. Present tense.',
],
```

**Use `additionalProperties: false` and list every field in `required`.** If you do not, some fields become
optional, and optional fields mean null checks scattered through your code.

**Say explicitly what a missing value looks like:**

```php
'order_reference' => [
    'type' => ['string', 'null'],
    'description' => 'The order reference exactly as written, or null if none is mentioned. Never invent one.',
],
```

That description is doing the real work. Without it, a required string field pushes the model to produce
*something*, and that something is how an invented order number ends up in your database.

### Supported, and not

Supported: objects, arrays, `string`, `integer`, `number`, `boolean`, `null`, `enum`, `const`, `anyOf`,
`$ref`, `additionalProperties: false`, and formats like `date`, `email`, `uri`, `uuid`.

**Not supported:** schemas that refer to themselves, `minimum`/`maximum`, `minLength`/`maxLength`, and
complicated array rules. So a rule like "under 140 characters" goes in the `description`, and you check it in
PHP afterwards.

## Validate anyway

The shape is guaranteed. The content is not. The `summary` may still be 200 characters long, and the category
may simply be the wrong one.

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

There are two layers here. The schema makes the reply **readable by code**. The validation makes it
**believable**. That `exists` rule is the important one. It is the difference between "the model said this order
exists" and "this order actually exists".

## Where this pays off

| Job | Schema returns |
|---|---|
| Invoice extraction from PDF | supplier, number, date, line items[], total |
| CV screening | years experience, skills[], seniority, red flags[] |
| Meeting notes → tasks | tasks[] with owner, due date, priority |
| Product feed cleanup | title, brand, category enum, attributes{} |
| Review sentiment + routing | rating, aspects[], team enum |

Every one of these is a queued job that writes rows into a table. There is no chat window anywhere in the list.

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

That `max_tokens` check matters more here than anywhere else. When prose gets cut off, you just get a shorter
paragraph. When JSON gets cut off, you get a parse error at 3am.

## Common mistakes

- **`maxTokens` too low for the schema.** A big schema produces a big object.
- **Free text where an enum belongs.** You will get `"Billing Issue"` on Monday and `"billing"` on Tuesday.
- **Trusting the values.** A neatly formatted but wrong `order_reference` is still wrong. Look it up.
- **Leaving "respond with only JSON" in the prompt.** It is unnecessary, and it competes with the schema.
- **One huge schema for six different jobs.** Small schemas, one per task, are more accurate and cheaper.

## You should now be able to

- [ ] Attach a JSON Schema and delete your regex parser
- [ ] Choose enums instead of free text
- [ ] Describe a missing value without inviting the model to invent one
- [ ] Validate the values after the shape is already guaranteed
- [ ] See that most useful AI features have no chat interface at all

## Practice

1. On the **Live run** page, panel 2: send the schema an angry email, a one-word email, and one in another
   language. Watch the shape stay the same.
2. Take a text column in your database and write a schema that turns it into three structured columns.
3. Add a nullable field for something that is often missing, and get its `description` right.
4. Put it into a queued job, run it over 100 rows, and check the cost.
