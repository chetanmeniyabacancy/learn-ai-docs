## Summary

- Build **Helpdesk AI**: RAG over a handbook, tools over a real orders table, triage, guardrails, evals.
- Make RAG a **tool** so the model decides when to search. "hi" then costs nothing.
- Ten boxes in "Definition of done". Tick them honestly.
- Two to three focused days. Do not read this module — build it.

## The brief

A support assistant for an online store, in Laravel, using everything in Level 1.

It must:

1. Answer policy questions from a handbook, with citations (**RAG**)
2. Answer "where is my order?" from the database (**tools**)
3. Triage inbound tickets into structured fields (**structured output**)
4. Refuse to answer what it does not know
5. Never expose one customer's data to another
6. Be measurable, and cost something you can state

## Why build it

Eleven modules of understanding is not one working system. The integration is where the lessons are:
retrieval that looked fine returns the wrong chunk for real questions, the tool loop and RAG compete for
context, and your first cost estimate is wrong by 4×.

You also end up with something to show. "I understand RAG" and "here is an assistant I built, here are its
evals, here is the cost per conversation" are very different sentences.

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

One decision up front: **make RAG a tool.** Rather than always retrieving, give the model `search_handbook`
alongside `lookup_my_order`. It decides which it needs — and for "hi" it uses neither.

## Seven steps

### 1 · Foundations

```bash
composer require anthropic-ai/sdk
php artisan make:model Article -m
php artisan make:model Conversation -m
php artisan make:model AiCall -m
```

- `config/claude.php` with model and key. No `env()` outside config.
- `App\Services\Claude` — `ask()`, `extract()`, `text()`, `usage()`.
- Seed 15–20 handbook articles and 20 orders across **3 customers**. Three matters — that is how you test
  scoping in step 6.

### 2 · Structured triage (module 3)

A queued job: ticket in, `category` / `priority` / `summary` / `order_reference` out, validated and written to
columns. Ship this first — smallest complete win, no UI needed.

### 3 · Retrieval (modules 5–7)

- Chunk articles at ~900 characters with overlap, **keeping the heading in the chunk text**.
- Embed with your chosen provider (Anthropic has none — Voyage AI, OpenAI, or local).
- Store vectors plus metadata: `visibility`, `article_id`, `heading`, `embedding_model`.
- `Retriever::search($query, $filters, $limit)`.
- `php artisan handbook:reindex` — write it now, not later.

> To defer choosing an embedding provider, start with the TF-IDF retriever from this site
> (`app/Services/Retriever.php`). The pipeline is identical; swap the scorer later.

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

Note it is `lookup_my_order`, not `lookup_order(customer_id)`. The customer comes from `auth()`. That is all
of module 9 in one naming decision.

### 5 · The agent loop (modules 4 + 10)

Capped at 5 turns. Handles `tool_use`, `refusal`, `max_tokens`. Streams to the browser. Persists the
conversation. Logs an `ai_calls` row per API call.

### 6 · Guardrails (module 9)

- Every order query scoped by `auth()->id()`
- Retrieval filtered to `visibility = 'public'`
- Handbook text delimited and labelled as data
- Rate limit: 10/min and 200/day per user
- Output scanned for email addresses
- **Then attack it yourself for twenty minutes.** Ask for another customer's order. Paste an injection into a
  ticket. Ask it to print its system prompt.

### 7 · Evals (module 8)

Thirty cases minimum:

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

Report **recall@4 separately** from answer quality, or you tune the wrong half.

## Definition of done

- [ ] Answers policy questions with citations pointing at real passages
- [ ] Answers order questions from the database, and says so when the order does not exist
- [ ] Refuses cleanly instead of inventing
- [ ] Customer A cannot see customer B's data by any prompt you can devise
- [ ] Triage writes validated structured fields from free text
- [ ] Streams — first words in under a second
- [ ] Every call logged with model, tokens, cost, latency, prompt version
- [ ] An eval suite you run in one command, with a number
- [ ] You can state the cost per conversation to three decimals
- [ ] It degrades to a search box and contact form when the API is down

## Stretch goals

- **Hybrid retrieval** — merge keyword and vector results; measure the recall@4 change.
- **Conversation memory** — persist turns, summarise beyond 10 to control token growth.
- **Confidence routing** — below a threshold, hand to a human.
- **Cost dashboard** — spend by feature by day from `ai_calls`.
- **Model routing** — Haiku for triage, Sonnet for the assistant. Measure the saving.
- **Feedback loop** — 👍/👎 on every answer; every 👎 becomes an eval case.

## What comes after

| Level 1 (this) | Level 2 (next) |
|---|---|
| You control the flow | The model plans and controls the flow |
| One or two tools, read-mostly | Many tools, writes, multi-step plans |
| Basic RAG | Reranking, query rewriting, hybrid retrieval |
| Stateless requests | Agent memory across sessions |
| One call per step | Multi-agent systems, MCP |

Every one builds on what you have. An agent is a tool loop with better planning. Agent memory is RAG over
conversation history. MCP is tool calling with a standard wire format. Nothing in Level 2 is not an
elaboration of Level 1 — which is why doing this properly matters more than rushing ahead.

## Finally

The gap between people who "know about AI" and people who ship it is not knowledge. It is having built the
thing once and solved the real problems.

Build Helpdesk AI. Then rebuild the nearest equivalent inside your own company, with real data and real users.
