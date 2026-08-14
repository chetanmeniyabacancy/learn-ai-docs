## Summary

- An LLM is a function: text in, text out, over HTTP. Like calling Stripe.
- It is **stateless** (no memory), has **no access** to your data, and is **probabilistic**.
- Three shapes of AI feature: **Transform**, **Answer**, **Act**.
- `content` is an array of blocks, not a string. Always loop and filter on `type`.
- `maxTokens` is a hard cut-off, not a suggestion. Check `stopReason`.

## The mental model

An LLM is a function with this signature:

```php
function llm(string $instructions, string $input): string
```

Text in, text out. That is the whole interface. Everything else in this course is controlling what goes in and
what comes out.

Three properties matter, and people get all three wrong:

**Stateless.** The API remembers nothing between calls. A "conversation" is you resending the history every
time. Chat memory is a `messages` table and a loop.

**No access.** It cannot see your database, files, or today's date unless you put it in the request. Every
capability beyond generating text is something you wire up (modules 4–7).

**Probabilistic.** Same input, different output. This breaks your testing instincts, which is why module 8
exists.

## Three shapes of AI feature

| Shape | What it does | Modules |
|---|---|---|
| **Transform** | Text in, text or JSON out. Summarise, classify, extract. | 1–3 |
| **Answer** | Answer from *your* documents. | 5–7 (RAG) |
| **Act** | Read or change real data through your code. | 4 (tools) |

"Summarise this email and tag it" = Transform. "What does our refund policy say?" = Answer. "Where is my
order?" = Act. A support assistant is all three — that is the capstone.

## Setup

You need PHP 8.2+, Laravel, and a key from the
[Anthropic Console](https://console.anthropic.com/settings/keys). Level 1 costs less than a coffee.

```bash
composer require anthropic-ai/sdk
```

```env
ANTHROPIC_API_KEY=sk-ant-...
```

> Never commit the key. Never send it to the browser. Every call goes server → Anthropic. A key in JavaScript
> is a key on your bill.

Use a config file, not `env()` everywhere — `env()` returns `null` once configs are cached in production.

```php
// config/claude.php
return [
    'api_key' => env('ANTHROPIC_API_KEY'),
    'model' => env('CLAUDE_MODEL', 'claude-sonnet-5'),
];
```

## Your first call

```php
use Anthropic\Client;

$client = new Client(apiKey: config('claude.api_key'));

$message = $client->messages->create(
    model: config('claude.model'),
    maxTokens: 1024,
    messages: [
        ['role' => 'user', 'content' => 'In one sentence: what is Laravel?'],
    ],
);

foreach ($message->content as $block) {
    if ($block->type === 'text') {
        echo $block->text;
    }
}
```

Run it in `php artisan tinker`.

Three things trip everyone up:

**`content` is an array of blocks, not a string.** A response can hold text, thinking and tool requests.
`$message->content[0]->text` works today and breaks the moment you enable thinking or tools. Loop and filter
on `type`, every time.

**`maxTokens` is a hard ceiling.** Set it too low and you get a sentence that stops mid-w — with `stopReason`
set to `max_tokens`. Nothing throws.

**Named arguments are camelCase.** The PHP SDK uses `maxTokens`, `outputConfig`, `toolChoice`. The JSON on the
wire uses `max_tokens`. Python and curl docs show the snake_case names.

## Where it lives in Laravel

Nowhere special. It is a service class.

```php
namespace App\Services;

use Anthropic\Client;
use Anthropic\Messages\Message;

class Claude
{
    public function __construct(private Client $client) {}

    public function ask(string $system, string $prompt, int $maxTokens = 1024): string
    {
        $message = $this->client->messages->create(
            model: config('claude.model'),
            maxTokens: $maxTokens,
            system: $system,
            messages: [['role' => 'user', 'content' => $prompt]],
        );

        return $this->text($message);
    }

    private function text(Message $message): string
    {
        $parts = [];

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $parts[] = $block->text;
            }
        }

        return trim(implode("\n", $parts));
    }
}
```

Bind it:

```php
$this->app->singleton(Client::class, fn () => new Client(apiKey: config('claude.api_key')));
```

Now inject `Claude` anywhere. Testable, mockable, swappable. Your architecture does not change — you added one
outbound integration.

## Cost

Billed per token, in and out. A token is about ¾ of a word.

| Model | Input / 1M | Output / 1M | Use for |
|---|---|---|---|
| Haiku 4.5 | $1 | $5 | High-volume classification |
| Sonnet 5 | $3 | $15 | Almost everything — the default |
| Opus 5 | $5 | $25 | Hard reasoning, long agent work |

Summarising a 500-word email on Sonnet costs about $0.002. Ten thousand of them is about $20. That arithmetic
decides whether a feature is viable — do it before writing code.

> Put the model in config and change it in one place. Model IDs scattered across twenty controllers is a
> migration you will do by hand.

## You should now be able to

- [ ] Explain why "the AI remembers our conversation" is false
- [ ] Make a call from tinker and read the text out correctly
- [ ] Classify a feature as Transform, Answer or Act
- [ ] Estimate a feature's monthly cost before building it

## Practice

1. Get the call above working in tinker.
2. Set `maxTokens: 20`, ask for a long answer, and inspect `$message->stopReason`.
3. Print `inputTokens` and `outputTokens`, multiply by the rates. That is your first cost estimate.
4. Ask "What was my previous question?" and confirm it has no idea.
