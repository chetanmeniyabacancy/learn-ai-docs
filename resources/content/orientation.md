## Summary

- An LLM is just a function: text goes in, text comes out, over HTTP. It feels like calling Stripe.
- It is **stateless** (it remembers nothing), it has **no access** to your data, and it is **probabilistic** (the same input can give different answers).
- AI features come in three shapes: **Transform**, **Answer**, and **Act**.
- `content` comes back as an array of blocks, not a string. Always loop over it and check the `type`.
- `maxTokens` is a hard stop, not a suggestion. Always check `stopReason`.

## The mental model

An LLM is a function with this signature:

```php
function llm(string $instructions, string $input): string
```

Text in, text out. That is the entire interface. Everything else in this course is about controlling what goes
in and checking what comes out.

Three properties matter most, and people get all three wrong.

**Stateless.** The API remembers nothing between calls. A "conversation" only exists because you resend the
whole history every time. Chat memory is really just a `messages` table and a loop.

**No access.** It cannot see your database, your files, or even today's date, unless you put that information
in the request. Every ability beyond writing text is something you wire up yourself, in modules 4 to 7.

**Probabilistic.** The same input can produce a different answer next time. This breaks the testing habits you
already have, which is exactly why module 8 exists.

## Three shapes of AI feature

| Shape | What it does | Modules |
|---|---|---|
| **Transform** | Text in, text or JSON out. Summarise, classify, extract. | 1–3 |
| **Answer** | Answer from *your* documents. | 5–7 (RAG) |
| **Act** | Read or change real data through your code. | 4 (tools) |

"Summarise this email and tag it" is Transform. "What does our refund policy say?" is Answer. "Where is my
order?" is Act. A support assistant needs all three, which is why it is the capstone project.

## Setup

You need PHP 8.2 or newer, Laravel, and an API key from the
[Anthropic Console](https://console.anthropic.com/settings/keys). Everything in Level 1 costs less than a cup
of coffee.

```bash
composer require anthropic-ai/sdk
```

```env
ANTHROPIC_API_KEY=sk-ant-...
```

> Never commit the key, and never send it to the browser. Every call must go from your server to Anthropic. A
> key in your JavaScript is a key on your bill.

Read the key through a config file instead of calling `env()` everywhere. Once configs are cached in production,
`env()` returns `null`.

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

Run it inside `php artisan tinker`.

Three things trip up almost everyone:

**`content` is an array of blocks, not a string.** One response can contain text, thinking, and tool requests.
So `$message->content[0]->text` works today and breaks the day you turn on thinking or tools. Loop over the
blocks and check the `type`, every time.

**`maxTokens` is a hard limit.** Set it too low and the answer stops in the middle of a wor — and `stopReason`
will say `max_tokens`. Nothing throws an exception, so you must check it yourself.

**Named arguments are camelCase.** The PHP SDK uses `maxTokens`, `outputConfig` and `toolChoice`. The JSON sent
over the wire uses `max_tokens`. Python and curl examples show the snake_case names, which is confusing at
first.

## Where it lives in Laravel

Nowhere special. It is an ordinary service class.

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

Bind it in a service provider:

```php
$this->app->singleton(Client::class, fn () => new Client(apiKey: config('claude.api_key')));
```

Now you can inject `Claude` anywhere. It is easy to test, mock and replace. Your architecture does not change at
all. You have simply added one more outbound integration.

## Cost

You are billed per token, for both input and output. One token is about ¾ of a word.

| Model | Input / 1M | Output / 1M | Use for |
|---|---|---|---|
| Haiku 4.5 | $1 | $5 | High-volume classification |
| Sonnet 5 | $3 | $15 | Almost everything — the default |
| Opus 5 | $5 | $25 | Hard reasoning, long agent work |

Summarising a 500-word email on Sonnet costs about $0.002. Ten thousand of them costs about $20. That small
calculation decides whether the feature is worth building, so do it before you write any code.

> Keep the model name in config so you can change it in one place. Model IDs copied into twenty controllers is
> a migration you will end up doing by hand.

## You should now be able to

- [ ] Explain why "the AI remembers our conversation" is false
- [ ] Make a call from tinker and read the text out of the response correctly
- [ ] Say whether a feature is Transform, Answer or Act
- [ ] Estimate a feature's monthly cost before building it

## Practice

1. Get the call above working in tinker.
2. Set `maxTokens: 20`, ask for a long answer, then look at `$message->stopReason`.
3. Print `inputTokens` and `outputTokens`, and multiply them by the rates. That is your first cost estimate.
4. Ask "What was my previous question?" and confirm that it has no idea.
