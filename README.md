# AI Engineer

A self-hosted course site that takes a working backend engineer from the foundations to shipping real AI
systems. It runs as two tracks — **pick the one that matches what you have to build.**

## Choose your level

| [**Level 1 — AI Application Engineer**](#level-1--ai-application-engineer) | [**Level 2 — AI / LLM Engineer**](#level-2--ai--llm-engineer) |
|---|---|
| Build features on top of a model somebody else trained. | Systems that plan their own steps, act on real data, and keep working when they fail. |
| **12 modules** · prompting, structured output, tools, RAG, evaluation, security, production | **11 modules** · advanced retrieval, agents, workflows, MCP, memory, multi-agent, observability, guardrails, scaling |
| You control the flow. The model fills in a step you could not write by hand. | The model controls the flow. Everything that makes that safe is engineering. |
| Start here if you have never shipped an AI feature. | Start here if you have, and it now needs to do more than one call. |

Both are readable end to end with no API key. **[Level 0 — Foundations](#level-0--foundations)** sits under
both: eight short modules on what a model actually is, with the maths done by hand. Optional for shipping,
not optional for debugging.

### Level 1 — AI Application Engineer

*Build features on top of a model somebody else trained.*

The working half. Prompting, structured output, tools, RAG, evaluation, security and production — each one a
business problem, the idea that solves it, and the code that ships it.

| | |
|---|---|
| 0 | Orientation & Setup |
| 1 | LLM Basics |
| 2 | Prompt Engineering |
| 3 | Structured Output |
| 4 | Tool / Function Calling |
| 5 | Embeddings & Semantic Search |
| 6 | Vector Storage & Search |
| 7 | RAG — Retrieval Augmented Generation |
| 8 | Evaluation |
| 9 | AI Security |
| 10 | Production AI |
| 11 | Capstone Project |

### Level 2 — AI / LLM Engineer

*Systems that plan their own steps, act on real data, and keep working when they fail.*

In Level 1 you controlled the flow. Here the model does, and everything that makes that safe is engineering:
better retrieval, memory, orchestration, budgets, tracing, guardrails and the discipline to know when a plain
workflow beats an agent.

| | |
|---|---|
| 12 | Advanced RAG |
| 13 | AI Agents |
| 14 | Agentic Workflows |
| 15 | MCP — Model Context Protocol |
| 16 | Agent Memory |
| 17 | Multi-Agent Systems |
| 18 | Advanced AI Evaluation |
| 19 | AI Observability / LLMOps |
| 20 | AI Guardrails |
| 21 | AI Architecture & Scaling |
| 22 | Production Agent Systems |

### Level 0 — Foundations

*What AI, machine learning and generative AI actually are, and how a model learns anything at all.*

You can ship an AI feature without this. You cannot debug one, cost one, or argue with a vendor about one.

| | |
|---|---|
| F1 | What AI Actually Is |
| F2 | How Machines Learn: The Four Kinds |
| F3 | Inside Learning: Weights, Loss, Gradient Descent |
| F4 | Neural Networks |
| F5 | Turning Text Into Numbers |
| F6 | Transformers & Attention |
| F7 | How an LLM Is Trained |
| F8 | When NOT to Use an LLM |

The concepts are language-neutral — same API, same JSON, same patterns whatever you write them in. The course
shows them in **Laravel/PHP** because that is where you are already fast; when you move to Python later, only
the syntax changes.

## What is in it

| | |
|---|---|
| **31 lessons** | 8 foundations · 12 on shipping features · 11 on agents, retrieval and operations |
| **55 worked examples** | Code **and** the output it produces. No API key, no network — they work offline, forever. |
| **155 quiz questions** | 5 per module, with an explanation on every answer. 70% marks a module complete. |
| **4 live demos** | The same ideas run for real against the API with your own input. This is the only part that needs a key. |
| **Progress tracking** | Anonymous, cookie-based. No signup, so the site can just be shared as a link. |
| **Glossary** | Every term, defined for someone who writes backend code |

Each lesson follows the same shape: a business problem, the idea in one paragraph, how it works, the code,
where it goes wrong, and an exercise.

## Read → Example → Quiz → Live run

The site separates *seeing how it works* from *running it yourself*, so nothing is gated behind an API key
until you actually want one:

- **Examples** (`/examples`) — a code panel, a **Run this example ▶** button, and the recorded output. Nothing
  is sent anywhere; the output is stored with the example. 18 of the 28 also carry an *"Open in the
  playground"* link for when you want the real thing.
- **Live run** (`/playground`) — the same four ideas against the real API with your own input. Needs a key.

## Running it

```bash
composer install
npm install && npm run build

cp .env.example .env          # if you do not already have one
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed

php artisan serve
```

Open <http://localhost:8000>.

The course is fully readable with no API key. To use the playground, either set a key in `.env`:

```env
ANTHROPIC_API_KEY=sk-ant-...
CLAUDE_MODEL=claude-sonnet-5
```

…or leave it unset and let each visitor paste their own key into the box on the playground page. A
visitor-supplied key is held in their server-side session only and is never written to the database.

Get a key from the [Anthropic Console](https://console.anthropic.com/settings/keys). Working through the whole
course costs well under a dollar in tokens.

## Sharing it

The site is anonymous and read-only apart from quiz progress, so it is safe to put behind any URL.

- **Do not ship your own API key** on a public deployment — leave `ANTHROPIC_API_KEY` empty and let visitors
  supply their own, or the playground becomes free compute on your bill.
- Run `php artisan config:cache route:cache view:cache` in production.
- SQLite is fine for this; point `DB_*` at MySQL or Postgres if you prefer.

## Where things live

```
config/curriculum.php              module list, order, metadata, language label
resources/content/{slug}.md        the 31 lesson bodies
resources/examples/*.php           worked examples: code + recorded output
resources/quizzes/{slug}.php       quiz questions and explanations
resources/glossary.php             glossary terms

app/Support/Curriculum.php         reads content + quizzes, builds the outline
app/Support/Progress.php           completion and quiz scores
app/Services/Claude.php            thin wrapper over the Anthropic PHP SDK
app/Services/Retriever.php         chunking + TF-IDF retrieval for the RAG demo
app/Http/Controllers/PlaygroundController.php    the four live demos
```

### Editing the course

- **Change a lesson:** edit `resources/content/{slug}.md`. Markdown, with `##` headings becoming the
  in-page table of contents automatically.
- **Add a module:** add an entry to `config/curriculum.php`, then create the matching `.md` and quiz file.
- **Change a quiz:** edit `resources/quizzes/{slug}.php`. `answer` is the zero-based index into `options`.
- **Add an example:** drop a file in `resources/examples/`. Files are ordered by filename; the array needs
  `id`, `module`, `title`, `intro`, `language`, `code`, `output`, and optionally `output_language`, `notes[]`
  and `live` (one of `chat`, `extract`, `tools`, `rag`). It appears on its module's lesson page and in the
  examples index automatically — no registration step.

### Adding a second language later

When you want Python examples alongside the PHP ones, the seam is already there: `config/curriculum.php`
holds the language label and note, and each example declares its own `language`. Add `language => 'python'`
examples and a filter on the index page — the lessons, quizzes and glossary need no changes, because none of
them are PHP-specific.

## A note on the RAG demo

`app/Services/Retriever.php` scores chunks with TF-IDF (keyword overlap), not embeddings, so the playground
needs only one API key. The pipeline — chunk, score, take top-K, inject into the prompt — is identical to a
production system; only the scoring step changes. Modules 5 and 6 explain exactly what you swap and what stays
the same, and the demo says so on the page.

Anthropic does not sell an embeddings endpoint. For real semantic search you add a second provider (Voyage AI,
OpenAI, Cohere, or a local model) — module 5 covers the options.

## Accuracy

Model IDs, prices and API shapes were current when this was written and are pinned in `config/claude.php`.
Check the [Anthropic docs](https://platform.claude.com/docs) before relying on a number — that page moves
faster than any course does.
