## Summary

- Treat a prompt like a **specification**. Any freedom you leave in it will be used, and usually not in the way you wanted.
- The **system prompt** holds your rules and stays the same. The **user message** holds the data and changes every time. Keep the two separate.
- Six things that really work: be specific, show examples, allow "I don't know", wrap the data in tags, order your instructions well, and do not shout.
- Improve a prompt by changing **one thing** and running it again on the **same 20 real inputs**.

## The problem

Your email classifier works fine while you test it. Then in production, one reply in twenty looks like this:

```text
Sure! Here's my analysis of this email:

**Category:** Order Status
I'd categorise this as an order status enquiry because…
```

Your parser wanted `Category: order_status` on the first line. Now you are writing a regex, a fallback and a
retry.

The model did nothing wrong. Your instructions allowed this answer, so sometimes you get it.

## Anatomy

```php
$message = $client->messages->create(
    model: 'claude-sonnet-5',
    maxTokens: 1024,

    // WHO it is and WHAT THE RULES ARE — same every request
    system: 'You are a support triage assistant. …',

    messages: [
        // WHAT to do now — the variable part
        ['role' => 'user', 'content' => $ticket->body],
    ],
);
```

**The system prompt** holds the role, the rules and the output format. It is the same on every call. It also
carries more weight than anything a user types, which becomes a security feature in module 9.

**The user message** holds the data for this one request.

Keeping them apart is not about neatness. It is what makes prompt caching possible (module 10), and it stops
user input from rewriting your rules.

## Six techniques that work

### 1. Be specific about the output

```text
❌ Summarise this complaint.

✅ Summarise this complaint in exactly three bullet points:
   - What went wrong
   - What the customer wants
   - Whether they mention a refund (yes/no)
   Output only the bullets. No preamble, no closing line.
```

The words "no preamble" do real work here. If you do not say it, a chat model will start with a friendly
sentence, because friendly sentences are what helpful text normally looks like.

### 2. Show, don't tell

One example teaches more than a paragraph of description. Two or three examples can pin down edge cases that
are very hard to describe in words.

```php
$system = <<<'TXT'
Extract the delivery date. Reply with an ISO date and nothing else.
If there is no date, reply exactly: NONE

Examples:
Input: "It was meant to come on the 3rd of March"
Output: 2026-03-03

Input: "Still waiting, ordered ages ago"
Output: NONE

Input: "Arriving next Tuesday apparently"
Output: NONE
TXT;
```

The third example is the important one. It teaches that "next Tuesday" cannot be turned into a real date, so
the answer is `NONE` instead of a guess. That rule is awkward to write in words and obvious to show with an
example.

### 3. Give it permission to fail

The model was trained to be helpful, and being helpful looks like giving an answer. So if you do not allow it
to say "I don't know", you have quietly demanded a guess.

```text
If the email does not clearly state a category, reply exactly: UNCLEAR
Do not infer a category from tone or from the sender's name.
```

That one line removes a whole family of hallucinations. In most prompts it is the most valuable sentence you
can add.

### 4. Wrap the data in tags

```php
$prompt = <<<TXT
Answer using only the ticket below.

<ticket>
{$ticket->body}
</ticket>

Question: what does the customer want us to do?
TXT;
```

XML-style tags stop two mistakes: the model reading your instructions as content, and the model reading content
as instructions. They also make the security fix in module 9 much easier.

### 5. Order instructions the way they run

The model pays most attention to the start and the end of the system prompt. So put the role first and the
format rules last. A format rule sitting just before the model starts writing has the strongest effect.

### 6. Do not shout

Old prompts are full of lines like `CRITICAL: YOU MUST ALWAYS…`. That style came from older models, which were
harder to steer. Current models follow normal instructions closely. Shouting now causes the opposite problem:
the rule fires too often, in places where it does not belong.

```text
❌ CRITICAL!!! You MUST ALWAYS use the search tool for EVERY question!!!
✅ Use the search tool when the answer depends on information not in the conversation.
```

So if a rule is being applied too widely, turn the volume down, not up.

## A prompt worth copying

```php
namespace App\Prompts;

class TriagePrompt
{
    // Version it. You need to know which prompt produced which answers (module 8).
    public const VERSION = 'triage.v3';

    public static function system(): string
    {
        return <<<'TXT'
        You triage support tickets for an online electronics store.

        For each ticket decide:
        - category: billing | shipping | technical | account | other
        - priority: low | normal | high | urgent
        - summary: one sentence, under 140 characters, no greeting

        Priority rules:
        - urgent: charged incorrectly, or a safety issue
        - high: order more than 5 days late, or a chargeback threat
        - normal: everything else needing a reply
        - low: thanks, feedback, no action

        Use only what the ticket says. Never invent an order number, date or
        amount. If the category is unclear, use "other".

        Reply with the three fields, one per line, and nothing else.
        TXT;
    }
}
```

Four things make it good. The allowed values are a closed list. The priority rules are real tests, not vague
adjectives like "important". There is an explicit "never invent". And there is a clear escape route when the
category is unclear.

## Iterating like an engineer

1. Write the prompt.
2. Run it on **20 real inputs** taken from your database, including the messy ones.
3. Read every single output. Write down each failure and why it happened.
4. Change **one thing**.
5. Run the same 20 inputs again. Is it better or worse?

If you change three things at once and check two examples, you end up with a 400-line prompt that nobody dares
to touch. Module 8 turns step 5 into a proper test suite.

> Keep your prompts in PHP classes, in version control. Do not write them inline in a controller, and do not
> store them in the database. One day you will need to compare versions and roll one back.

## Common mistakes

- **Rules piling up.** Every incident adds one more line, until the rules contradict each other. Delete rules
  whose problem no longer happens.
- **Being polite.** "Please could you kindly…" is just tokens you are paying for.
- **Forgetting it cannot see your app.** "Use the standard format" means nothing to the model. Show the format.
- **Testing only the easy cases.** Your prompt will meet an empty ticket, a 4,000-word angry message, and one
  written in Portuguese.
- **Trusting one good demo.** One good answer proves nothing about a system that gives different answers each
  time.

## You should now be able to

- [ ] Separate rules (system) from data (user), and explain why it matters
- [ ] Use examples to pin down an edge case
- [ ] Write a clear "I don't know" escape route
- [ ] Wrap untrusted input in tags
- [ ] Improve a prompt one change at a time, against a fixed set of inputs

## Practice

1. Run a module-1 prompt on 20 real records. Count how many outputs would break your parser.
2. Add an output-format rule and an `UNCLEAR` escape route. Run it again. Count again.
3. Feed it an empty string, then a 5,000-word document. Fix whatever breaks.
4. On the **Live run** page, delete "at most 3 bullet points" from panel 1 and watch the answer grow long.
