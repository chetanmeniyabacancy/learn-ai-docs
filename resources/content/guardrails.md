## Summary

- A guardrail is a **check in your code** around a model call. A prompt asks; code enforces.
- Check on the way **in** (is this allowed?) and on the way **out** (is this safe to show?).
- Match the check to the damage. A wrong summary is a shrug. A wrong refund is money.
- Cheapest first: **rules and regex**, then a **small classifier**, then a **model as judge**.
- A guardrail that fires must **do** something: block, hide, retry, or send it to a person.

## The problem

Your assistant has been live a month. Then in one week:

- It tells a customer the warranty covers something it does not, and they quote it back at you
- Someone pastes text into a review that makes it print part of its system prompt
- One reply includes another customer's email address, pulled from a support note

None of these threw an error. Every one was a normal 200 response. You heard about all three from customers.

## Four places to put checks

```text
 user types something
     ├── INPUT ──────► block, shorten, or send elsewhere
     ▼                 is it allowed? too long? an attack?
 search + build prompt
     ├── CONTEXT ────► filter before the prompt is built
     ▼                 may this person read this document?
 model call
     ├── OUTPUT ─────► hide, redact, or try again
     ▼                 personal data? invented numbers? safe to render?
 shown to the user
     └── ACTION ─────► propose instead of doing
                       is it reversible? within limits? approved?
```

Not one of these is a sentence in the prompt. That is the whole point of the module.

## Input checks

Start with the cheap ones. They handle most of the volume.

```php
// 1. Length. A 50-page paste is a cost attack, even by accident.
if (mb_strlen($input) > 2000) {
    return $this->tooLong();
}

// 2. Rate. There is no max_spend setting — limits are yours to build.
if (RateLimiter::tooManyAttempts("ai:{$user->id}", 10)) {
    return $this->rateLimited();
}

// 3. Obvious attack words. This catches lazy attempts only, so LOG it and
//    keep going — do not block. A curious employee types this too.
foreach (['ignore all previous', 'developer mode', 'repeat your system prompt'] as $marker) {
    if (str_contains(mb_strtolower($input), $marker)) {
        Log::warning('Injection marker seen', ['user' => $user->id]);
        break;
    }
}
```

When it is worth the cost, add a scope check:

```php
// Haiku, ~40 tokens out. Cheaper than answering something you should not.
$scope = $this->claude->extract(
    system: 'Is this question about the company handbook, the person\'s own records, or neither?',
    input: $input,
    schema: ['type' => 'object', 'properties' => [
        'in_scope' => ['type' => 'boolean'],
    ], 'required' => ['in_scope'], 'additionalProperties' => false],
    model: config('claude.fast_model'),
);

if (! $scope['in_scope']) {
    return $this->politeDecline();     // no expensive call, no wrong answer
}
```

## Context checks

The strongest guardrail in this whole course is a `where` clause, and you already know it:

```php
// Permission is a query, not a request to the model.
$chunks = PolicyChunk::whereIn('visibility', $user->visibilities())->get();
```

Also label retrieved text as **data** in the prompt, every time, and log it when a document contains
instruction-like text. Somebody poisoning a document is worth knowing about, even when it cannot work.

## Output checks

The last line before a human reads it.

```php
$problems = [];

// 1. Personal data that should never appear.
if (preg_match('/\b[\w.+-]+@[\w-]+\.[\w.]+\b/', $answer)) {
    $answer = preg_replace('/\b[\w.+-]+@[\w-]+\.[\w.]+\b/', '[removed]', $answer);
    $problems[] = 'email_removed';
}

// 2. Invented numbers. Every figure in the answer should exist in the passages.
//    Free, deterministic, and it catches the failure that matters most.
foreach ($this->numbersIn($answer) as $number) {
    if (! str_contains(implode(' ', $passages), $number)) {
        $problems[] = "invented_number:{$number}";
    }
}

// 3. Promises the business cannot keep.
foreach (['guarantee', 'we will definitely', 'i promise'] as $phrase) {
    if (str_contains(mb_strtolower($answer), $phrase)) {
        $problems[] = 'promise_language';
    }
}
```

Here is what that looks like in practice:

```text
passage [0]  "Up to 5 unused leave days carry forward and expire on 31 March."

answer A  "You can carry 5 days, they expire on 31 March. [0]"
          numbers 5 and 31 both in [0]              → pass

answer B  "You can carry 7 days, they expire on 31 March. [0]"
          7 is not in [0]                            → invented_number:7
          action: try once more with "quote figures exactly"
```

## Match the check to the damage

| Feature | Worst case | Checks worth paying for |
|---|---|---|
| Internal summary | Someone re-reads the source | Length limit, escaping |
| Employee answer | Wrong info, a complaint | Grounded numbers, citations, refusal |
| Customer reply | A public promise | All of the above, plus human approval |
| Anything with money | Money moves wrongly | Proposal only, limits in code, approval |

Notice the pattern: as the damage grows, the checks move from **checking the text** to **not letting the model do
it at all**.

Log every trigger, then sample them by hand. A guardrail that blocks good answers is a bug that looks like
safety, and it is why teams switch guardrails off.

## Common mistakes

- **Prompt-only guardrails.** "Never reveal salary bands" is a request, not a control.
- **Blocking on keywords.** Fires on real questions and misses anything reworded.
- **A model checking every answer.** Doubles cost to catch what a regex catches.
- **Guardrails with no action.** Logged, ignored, then deleted.
- **Rendering model output as raw HTML.** That is an XSS hole you built yourself.

## You should now be able to

- [ ] Put checks at the four places: input, context, output, action
- [ ] Choose rules, a classifier or a judge based on the damage
- [ ] Check that every number in an answer came from the source
- [ ] Log triggers and sample false positives
- [ ] Explain why permission belongs in the query, not the prompt

## Practice

1. List your AI features and the worst thing each could do. Guardrail from the top down.
2. Add the number check to your most factual feature. Count how often it fires.
3. Put a scope check in front of an expensive feature. Measure how much traffic it turns away.
4. Take twenty attack strings and make them all fail, in CI.
