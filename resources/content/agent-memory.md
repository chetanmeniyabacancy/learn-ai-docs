## Summary

- The API remembers nothing. Every kind of memory is something **you** build.
- Four kinds: **working** (this chat), **episodic** (past chats), **semantic** (facts about the person), **procedural** (how your company does things).
- The real skill is **forgetting**. Remembering everything is slow, costly and less accurate.
- For long chats: keep the last 10 turns, plus one summary of everything older.
- Anything you remember about a person is personal data. It needs a delete button.

## The problem

Your assistant helped a customer for twenty minutes yesterday. It worked out the order was lost, noted they had
moved house, and agreed a replacement.

Today they write again. It knows nothing. The customer explains everything a second time.

So you keep the whole history. Two weeks later, one conversation is 40,000 tokens. Every reply costs 30 times
more, and answers get **worse**, because the useful part is buried in two weeks of small talk.

Both extremes are wrong. The answer is in the middle.

## Four kinds of memory

| Kind | What it holds | Where it lives |
|---|---|---|
| **Working** | This conversation | The `messages` array |
| **Episodic** | What happened in past chats | A table, searched by relevance |
| **Semantic** | Facts about this person | A few fixed columns |
| **Procedural** | How your company works | Prompts in git |

People say "it needs memory" and mean one of these four. Naming which one makes the job small.

## Working memory: keep 10 turns plus a summary

Start here. Most products never need more.

```php
public function messagesFor(Conversation $conversation): array
{
    // Recent detail matters most, and this is the simplest possible rule.
    $recent = $conversation->messages()->latest('id')->limit(10)->get()->sortBy('id');

    $messages = [];

    // Everything older becomes one summary, refreshed as the chat grows.
    if ($conversation->summary) {
        $messages[] = ['role' => 'user',
            'content' => "Summary of the earlier part of this chat:\n{$conversation->summary}"];
    }

    foreach ($recent as $message) {
        $messages[] = ['role' => $message->role, 'content' => $message->content];
    }

    return $messages;
}
```

The summary prompt matters more than the code:

```php
$conversation->update(['summary' => $this->claude->ask(
    system: 'Summarise this part of a support chat for a colleague taking over. '
          . 'Keep every reference number, date, amount and decision exactly as '
          . 'written. Remove greetings and small talk. Under 200 words.',
    prompt: $old->map(fn ($m) => "{$m->role}: {$m->content}")->implode("\n"),
    model: config('claude.fast_model'),
)]);
```

Here is what that buys you on a 60-turn conversation:

```text
                          tokens sent   cost/reply   quality
full history                 41,200      $0.1236    falling — answer is buried
last 10 turns only            3,100      $0.0093    good, but forgets ORD-1043
summary + last 10 turns       3,600      $0.0108    good, and remembers ORD-1043
```

92% cheaper than the full history, and better than either extreme.

## Episodic memory: past conversations

Store one small record per session — not the whole transcript.

```php
// At the end of a session.
ConversationMemory::create([
    'user_id' => $user->id,
    'summary' => 'Order ORD-1043 lost, replacement agreed, customer moved house',
    'embedding' => $this->embed($summary),   // so you can find it by meaning
]);
```

Next time, fetch only what relates to the new question — **filtered to this user, in the query**:

```php
$relevant = ConversationMemory::where('user_id', $user->id)   // never skip this
    ->get()
    ->map(fn ($m) => [$m, $this->cosine($questionVector, $m->embedding)])
    ->filter(fn ($pair) => $pair[1] > 0.6)
    ->sortByDesc(fn ($pair) => $pair[1])
    ->take(3);
```

Three relevant memories beat thirty. Do not add everything "just in case" — memory is tokens, time and noise.

## Semantic memory: facts

Some things are not events, they are lasting facts: preferred language, accessibility needs, a new address.

```php
UserFact::updateOrCreate(
    ['user_id' => $user->id, 'key' => 'preferred_language'],
    ['value' => 'Hindi', 'learned_at' => now()],
);
```

Two rules save you pain later. Use a **fixed list of keys** — free-form memory becomes a junk drawer nobody
trusts within a month. And **store when you learned it**, so old facts can fade instead of becoming wrong
answers.

## What to forget

This is where the real work is.

| Forget | Why |
|---|---|
| Greetings and small talk | Costs tokens, gives nothing |
| Old facts that changed | An old address that beats the new one is a wrong answer |
| "Not found" tool results | Noise the model keeps reacting to |
| Anything the user asked you to forget | Not optional |
| Old transcripts | A privacy risk that grows by itself |

```php
public function forget(User $user, string $key): void
{
    UserFact::where('user_id', $user->id)->where('key', $key)->delete();
}
```

Also give memory a time limit (90 days is a fair default), let people see what you remember, and make sure
"forget everything about me" is a method you have actually tested.

## Common mistakes

- **Resending the whole history** and calling it memory. It is just an expensive prompt.
- **A summary that loses the order number.** Then it looks like context but the useful fact is gone.
- **Never forgetting.** Memory that only grows gets slower and worse every week.
- **Free-form keys.** You cannot search, expire or delete them properly.
- **Forgetting the `where user_id`.** That is one customer reading another one's memory.

## You should now be able to

- [ ] Name the four kinds and say which one a request needs
- [ ] Build a 10-turn window with a summary
- [ ] Save and fetch past-session memories, scoped to one user
- [ ] Decide what to forget and write the code that forgets it
- [ ] Give memory a time limit and a delete path

## Practice

1. Take your longest chat. Count its tokens. Price one reply at full history versus a 10-turn window.
2. Add the window and summary. Check the reference numbers survived.
3. Write three past-session memories by hand, then ask a question that should find one.
4. Build "forget everything about me" and run it. Check every table is clean.
