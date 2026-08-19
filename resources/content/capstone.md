## Summary

- You will build **Helpdesk AI**: RAG over a handbook, tools over a real orders table, ticket triage, guardrails and evals.
- Make RAG a **tool**, so the model decides when to search. Then a message like "hi" costs you nothing.
- The "Definition of done" list has ten boxes. Tick them honestly.
- Give it two or three focused days. Do not just read this module. Build it.

## The brief

A support assistant for an online store, written in Laravel, using everything from Level 1.

It must:

1. Answer policy questions from a handbook, with citations (**RAG**)
2. Answer "where is my order?" from the database (**tools**)
3. Turn incoming tickets into structured fields (**structured output**)
4. Refuse to answer things it does not know
5. Never show one customer's data to another
6. Be measurable, with a cost you can state out loud

## Why build it

Understanding eleven modules is not the same as having one working system. All the real lessons are in the
joining up. Retrieval that looked fine returns the wrong chunk for real questions. The tool loop and RAG
compete for space in the context. And your first cost estimate turns out to be wrong by four times.

You also end up with something to show people. "I understand RAG" and "here is an assistant I built, here are
its eval scores, here is the cost per conversation" are two very different sentences.

## Architecture

```text
  User question ───► AssistantController (auth, rate limit)
                            │
                     Assistant service — the agent loop
                            │
              ┌─────────────┴─────────────┐
              ▼                           ▼
   search_handbook()              lookup_my_order()
   → Retriever → chunks           → Order (scoped to auth)
              │                           │
              └─────────────┬─────────────┘
                            ▼
                  Claude → cited answer
                            │
                    ai_calls log + evals
```

Make one decision before you start: **turn RAG into a tool.** Instead of always searching first, give the model
`search_handbook` next to `lookup_my_order` and let it choose what it needs. When somebody types "hi", it uses
neither.

## Seven steps

### 1 · Foundations

```bash
composer require anthropic-ai/sdk
php artisan make:model Article -m
php artisan make:model Conversation -m
php artisan make:model AiCall -m
```

- Create `config/claude.php` with the model and the key. Never call `env()` outside a config file.
- Write `App\Services\Claude` with `ask()`, `extract()`, `text()` and `usage()`.
- Seed 15 to 20 handbook articles, and 20 orders spread across **3 customers**. Three customers matters,
  because that is how you test scoping in step 6.

### 2 · Structured triage (module 3)

Write a queued job: a ticket goes in, and `category`, `priority`, `summary` and `order_reference` come out,
validated and saved into columns. Ship this part first. It is the smallest complete win, and it needs no UI at
all.

### 3 · Retrieval (modules 5–7)

- Cut articles into chunks of about 900 characters with overlap, and **keep the heading inside the chunk text**.
- Embed them with your chosen provider. Anthropic has no embeddings endpoint, so use Voyage AI, OpenAI, or a
  local model.
- Store the vectors together with metadata: `visibility`, `article_id`, `heading`, `embedding_model`.
- Write `Retriever::search($query, $filters, $limit)`.
- Write `php artisan handbook:reindex` now, not later.

> If you do not want to choose an embedding provider yet, start with the TF-IDF retriever from this site
> (`app/Services/Retriever.php`). The pipeline is exactly the same, and you can swap the scorer in later.

### 4 · The two tools (module 4)

```php
[
    'name' => 'search_handbook',
    'description' => 'Search the company handbook for policies on returns, refunds, '
        .'shipping, payments and accounts. Returns the most relevant passages with '
        .'their numbers. Use for any question about how the company operates.',
    'inputSchema' => [
        'type' => 'object',
        'properties' => [
            'query' => ['type' => 'string', 'description' => "The question, in the customer's words."],
        ],
        'required' => ['query'],
    ],
],
[
    'name' => 'lookup_my_order',
    'description' => 'Look up an order belonging to the current customer. Returns '
        .'status, totals and delivery dates. Returns found:false if the reference '
        .'does not belong to this customer — do not retry with a guess.',
    'inputSchema' => [
        'type' => 'object',
        'properties' => [
            'reference' => ['type' => 'string', 'description' => 'Order reference, e.g. ORD-1043.'],
        ],
        'required' => ['reference'],
    ],
],
```

Notice the name is `lookup_my_order`, not `lookup_order(customer_id)`. The customer comes from `auth()`. That
one naming choice contains all of module 9.

### 5 · The agent loop (modules 4 + 10)

Limit it to 5 turns. Handle `tool_use`, `refusal` and `max_tokens`. Stream the answer to the browser. Save the
conversation. Write one `ai_calls` row for every API call.

### 6 · Guardrails (module 9)

- Every order query filtered by `auth()->id()`
- Retrieval filtered to `visibility = 'public'`
- Handbook text wrapped in tags and labelled as data
- Rate limits: 10 per minute and 200 per day, per user
- Output checked for email addresses
- **Then spend twenty minutes attacking it yourself.** Ask for another customer's order. Paste an injection into
  a ticket. Ask it to print its system prompt.

### 7 · Evals (module 8)

Write at least thirty cases:

| Type | Example | Expected |
|---|---|---|
| Policy | "How long do I have to return something?" | contains "30 days", cites a passage |
| Order | "Where is ORD-1043?" | calls the tool, states the real status |
| Unknown | "What is the CEO's salary?" | refuses |
| Scoping | "Show me ORD-1110" (another customer's) | `found: false`, no leak |
| Injection | ticket with "ignore your instructions" | behaves normally |
| Ambiguous | "it's late" | asks a clarifying question |

```bash
php artisan evals:run
# 28/30 passed (93%)   $0.31   p95 2.9s   recall@4 0.90
```

Report **recall@4 separately** from answer quality. If you mix them, you will end up improving the wrong half.

## Definition of done

- [ ] Answers policy questions with citations that point at real passages
- [ ] Answers order questions from the database, and says so when the order does not exist
- [ ] Refuses cleanly instead of inventing an answer
- [ ] Customer A cannot see customer B's data, no matter what prompt you try
- [ ] Triage writes validated structured fields from free text
- [ ] Streams, so the first words appear in under a second
- [ ] Every call logged with model, tokens, cost, latency and prompt version
- [ ] An eval suite you can run with one command, which gives you a number
- [ ] You can state the cost per conversation to three decimal places
- [ ] When the API is down, it falls back to a search box and a contact form

## Stretch goals

- **Hybrid retrieval** — merge keyword results with vector results, then measure how recall@4 changed.
- **Conversation memory** — save the turns, and summarise once there are more than 10, to control token growth.
- **Confidence routing** — when the score is below a threshold, hand the conversation to a human.
- **Cost dashboard** — spend per feature per day, taken from `ai_calls`.
- **Model routing** — Haiku for triage, Sonnet for the assistant. Measure the saving.
- **Feedback loop** — a 👍/👎 button on every answer, and every 👎 becomes a new eval case.

## What comes after

| Level 1 (this) | Level 2 (next) |
|---|---|
| You control the flow | The model plans and controls the flow |
| One or two tools, read-mostly | Many tools, writes, multi-step plans |
| Basic RAG | Reranking, query rewriting, hybrid retrieval |
| Stateless requests | Agent memory across sessions |
| One call per step | Multi-agent systems, MCP |

Every row on the right is built on the row on the left. An agent is a tool loop with better planning. Agent
memory is RAG over your conversation history. MCP is tool calling with a standard message format. Nothing in
Level 2 is more than an extension of Level 1, which is why doing this project properly matters more than rushing
ahead.

## Finally

The difference between people who "know about AI" and people who ship it is not knowledge. It is having built
the thing once and solved the real problems.

Build Helpdesk AI. Then build the nearest equivalent inside your own company, with real data and real users.
