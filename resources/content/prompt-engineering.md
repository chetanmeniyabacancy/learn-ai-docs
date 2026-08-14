## Summary

- A prompt is a **specification**. Any freedom you leave will be used, usually against you.
- **System prompt** = rules (stable). **User message** = data (changes). Keep them separate.
- Six things that work: be specific, show examples, allow "I don't know", delimit data, order instructions,
  do not shout.
- Iterate by changing **one thing** and re-running the **same 20 real inputs**.

## The problem

Your email classifier works in testing. In production, one in twenty replies looks like this:

```text
Sure! Here's my analysis of this email:

**Category:** Order Status
I'd categorise this as an order status enquiry because…
```

Your parser expected `Category: order_status` on line one. Now you have a regex, a fallback and a retry.

The model was not wrong. Your instructions allowed that answer.

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

**System prompt:** role, rules, output format. Same every call. Carries more authority than anything a user
types — which becomes a security property in module 9.

**User message:** the data for this request.

Keeping them separate is not style. It is what makes prompt caching work (module 10) and stops user input
rewriting your rules.

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

"No preamble" does real work. Left alone, a chat model opens with a friendly sentence, because that is what
helpful text looks like.

### 2. Show, don't tell

One example beats a paragraph of description. Two or three pin down edge cases prose cannot express.

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

The third example is the important one. It teaches that an unresolvable relative date is `NONE`, not a guess —
fiddly to state, obvious to demonstrate.

### 3. Give it permission to fail

Models are trained to be helpful, and helpfulness looks like an answer. Without permission to say "I don't
know", you have required a guess.

```text
If the email does not clearly state a category, reply exactly: UNCLEAR
Do not infer a category from tone or from the sender's name.
```

This one line removes a whole class of hallucination. Highest-value sentence in most prompts.

### 4. Delimit the data

```php
$prompt = <<<TXT
Answer using only the ticket below.

<ticket>
{$ticket->body}
</ticket>

Question: what does the customer want us to do?
TXT;
```

XML-style tags stop the model reading your instructions as content, or content as instructions. They also make
the module 9 security fix straightforward.

### 5. Order instructions the way they run

Models weight the start and end of the system prompt most. Role first, format rules last — right before the
model starts writing is where a format rule lands hardest.

### 6. Do not shout

Old prompts are full of `CRITICAL: YOU MUST ALWAYS…`. That style existed because older models were less
steerable. Current models follow plain instructions closely, and shouting causes *over*-triggering — rules
applied where they do not belong.

```text
❌ CRITICAL!!! You MUST ALWAYS use the search tool for EVERY question!!!
✅ Use the search tool when the answer depends on information not in the conversation.
```

If a rule is over-applied, turn the volume down, not up.

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

What makes it good: closed enums, priority rules that are decision criteria not adjectives, an explicit "never
invent", and a defined escape hatch.

## Iterating like an engineer

1. Write the prompt.
2. Run it on **20 real inputs** — from your database, including the awkward ones.
3. Read every output. Note each failure and why.
4. Change **one thing**.
5. Re-run the same 20. Better or worse?

Changing three things and eyeballing two examples is how teams end up with 400-line prompts nobody dares
touch. Module 8 turns step 5 into a test suite.

> Keep prompts in PHP classes under version control. Never inline in a controller, never in the database. You
> will want to diff and roll back.

## Common mistakes

- **Rule pile-up.** Every incident adds a line until rules contradict. Delete rules whose failure no longer
  reproduces.
- **Politeness.** "Please could you kindly…" is tokens you pay for.
- **Forgetting it cannot see your app.** "Use the standard format" means nothing. Show the format.
- **Only testing happy paths.** Your prompt will meet an empty ticket, a 4,000-word rant, and one in
  Portuguese.
- **Trusting one good demo.** One answer proves nothing about a probabilistic system.

## You should now be able to

- [ ] Split rules (system) from data (user) and say why
- [ ] Use examples to pin an edge case
- [ ] Write an explicit "I don't know" escape hatch
- [ ] Delimit untrusted input
- [ ] Improve a prompt one change at a time against fixed inputs

## Practice

1. Run a module-1 prompt on 20 real records. Count outputs your parser would choke on.
2. Add an output-format rule and an `UNCLEAR` escape hatch. Re-run. Count again.
3. Feed it an empty string and a 5,000-word document. Fix what breaks.
4. In the **Live run** page, delete "at most 3 bullet points" from panel 1 and watch the answer sprawl.
