<?php

/*
|--------------------------------------------------------------------------
| Glossary
|--------------------------------------------------------------------------
|
| Every term used in the course. Definitions are written for someone who
| already writes backend code — what it is, and what it changes about yours.
| A small amount of inline HTML is allowed (rendered with {!! !!}).
|
*/

return [

    'Token' => 'The unit an LLM reads and writes — roughly ¾ of an English word, or a few characters of code. You are billed per token, in and out, so token count is your unit of cost <em>and</em> your unit of capacity.',

    'Context window' => 'The maximum number of tokens one request may contain: system prompt + conversation + documents + the answer. Claude Sonnet 5 and Opus 5 hold about 1,000,000 tokens; Haiku 4.5 holds 200,000. Exceeding it is an error, not a silent truncation.',

    'System prompt' => 'The instruction block that sets the rules for the whole conversation — role, tone, constraints, output format. It sits above the user message and carries more authority than anything a user types.',

    'User / assistant message' => 'The conversation itself, as an array. The API is <strong>stateless</strong>: it remembers nothing between calls, so you resend the whole history every time. "Memory" in a chatbot is just your database plus a loop.',

    'Temperature' => 'A sampling knob on older models that traded consistency for variety. On current Claude models (Opus 5, Sonnet 5) it has been removed and sending a non-default value returns a 400. Steer style with the prompt instead.',

    'Hallucination' => 'A fluent, confident, wrong answer. It is not a bug you can patch — it is what a next-token predictor does when it has no grounding. The fixes are structural: give it the data (RAG), give it tools, and let it say "I do not know".',

    'Structured output' => 'Attaching a JSON Schema to the request so the response is guaranteed to parse and to match the shape your code expects. Replaces "respond with only JSON", regex extraction and retry loops.',

    'Tool calling' => 'Also called function calling. You describe functions; the model replies with a request to run one and the arguments to use; <strong>your code runs it</strong> and passes the result back. The model never touches your database directly.',

    'Tool result' => 'The JSON you hand back after running a tool. It is the model\'s entire view of that data — whatever you leave out, it cannot see, which is exactly how you scope its access.',

    'Agent loop' => 'The <code>while (stop_reason === "tool_use")</code> cycle: call the model, run the tools it asks for, feed the results back, repeat until it produces a final answer. Always cap the iterations.',

    'Embedding' => 'A list of numbers (a vector) representing the meaning of a piece of text. Two texts that mean the same thing land close together even with no shared words. Anthropic does not sell an embedding endpoint — use a provider such as Voyage AI, or a local model.',

    'Cosine similarity' => 'The standard way to measure how close two vectors are: 1.0 identical direction, 0.0 unrelated. It is the maths behind "find me the most similar chunk", and it is about ten lines of PHP.',

    'Chunking' => 'Splitting long documents into passages of a few hundred tokens before embedding them. Chunk too big and retrieval returns noise; too small and you cut ideas in half. Overlap the boundaries so a straddling sentence is still findable.',

    'Vector database' => 'Storage that can find the nearest vectors to a query vector quickly. Options range from a MySQL column plus a PHP loop (fine to a few thousand chunks) to pgvector, Qdrant, Pinecone or Meilisearch at scale.',

    'Top-K' => 'How many retrieved chunks you inject into the prompt. 3–5 is the usual starting point. More is not better: it costs tokens and dilutes the signal.',

    'RAG' => 'Retrieval-Augmented Generation. Search your own data first, put the best passages in the prompt, then ask the model to answer using only those passages. The way you build "chat with our documents" without training anything.',

    'Reranking' => 'A second, more expensive scoring pass over the top ~25 retrieved chunks to reorder them before you pick the final 3. The single biggest accuracy win once basic RAG works. (Level 2.)',

    'Grounding' => 'Anchoring the answer in supplied source text, and citing it. A grounded answer can be checked; an ungrounded one has to be trusted.',

    'Prompt injection' => 'Text inside data the model reads — a support ticket, a PDF, a web page — that tries to issue instructions. The defence is architectural: never give the model a tool you would not let a hostile user call.',

    'Guardrail' => 'A rule enforced in <em>your</em> code rather than in the prompt: allowlists, per-user scoping, approval steps for destructive actions, output validation. Prompts are guidance; guardrails are enforcement.',

    'Eval' => 'A test suite for non-deterministic output. A fixed set of inputs plus a way to grade the answers, so you can tell whether a prompt change was an improvement or a regression.',

    'LLM-as-judge' => 'Using a model to grade another model\'s output against a rubric, for qualities that no assertion can express (tone, completeness, faithfulness). Cheap, scalable, and needs to be spot-checked against human judgement.',

    'Golden dataset' => 'The fixed set of realistic inputs with known-good answers that your evals run against. Twenty real examples beat two hundred invented ones.',

    'Streaming' => 'Receiving the response token by token instead of waiting for the whole thing. Same total time, but the user sees words in under a second — the difference between "fast" and "broken".',

    'Prompt caching' => 'Marking a stable prefix of the prompt so the API can reuse it. Cache reads cost about a tenth of normal input tokens. Caching is a <strong>prefix match</strong>: one changed byte early in the prompt invalidates everything after it.',

    'Stop reason' => 'Why generation ended: <code>end_turn</code> (finished), <code>max_tokens</code> (ran out of budget — the answer is cut off), <code>tool_use</code> (wants a tool run), <code>refusal</code> (declined). Branch on it before you read the content.',

    'Refusal' => 'A declined request. It arrives as a normal HTTP 200 with <code>stop_reason: "refusal"</code> and empty content — not an exception. Code that reads <code>content[0]</code> unconditionally renders a blank answer.',

    'Thinking / effort' => 'Current Claude models can reason before answering. <code>thinking: adaptive</code> lets the model decide how much; <code>effort</code> (low → max) sets the ceiling. Higher effort means better answers, more tokens, more latency.',

    'Model ID' => 'The exact string you send, e.g. <code>claude-sonnet-5</code>. Never guess one or append a date — a wrong ID is a 404. Keep it in config, never inline in twenty controllers.',

    'Idempotency' => 'Making a retried operation safe to run twice. Critical the moment a model can trigger an action: a retried "issue refund" must not issue two refunds.',

    'Observability' => 'Logging every call with prompt version, model, tokens, latency, cost, tool calls and outcome. Without it, "the AI got worse this week" is unanswerable.',

];
