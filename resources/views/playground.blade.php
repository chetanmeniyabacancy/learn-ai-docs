@extends('layouts.app')

@section('title', 'Playground — '.config('curriculum.title'))

@section('content')

    @php
        $modelSelect = function (string $id) use ($models, $defaultModel) {
            return view('partials.model-select', compact('models', 'defaultModel', 'id'));
        };
    @endphp

    <div class="mx-auto max-w-4xl">

        <h1 class="text-3xl font-bold tracking-tight text-ink-900">Live run</h1>
        <p class="mt-3 max-w-2xl text-[15px] leading-relaxed text-ink-600">
            The four Level 1 ideas, running for real against the Anthropic API with <em>your</em> input. Every
            panel is backed by the controller method named beside it — open
            <code class="rounded bg-ink-100 px-1.5 py-0.5 text-[13px]">app/Http/Controllers/PlaygroundController.php</code>
            and read the exact code that produced the output.
        </p>
        <p class="mt-3 max-w-2xl rounded-xl border border-ink-200 bg-ink-50 px-4 py-3 text-[14px] leading-relaxed text-ink-600">
            <strong class="text-ink-800">This page needs an API key.</strong>
            If you just want to see how each idea works, the
            <a href="{{ route('examples.index') }}" class="font-medium text-indigo-600 hover:underline">examples</a>
            show the same code with its output and need nothing at all.
        </p>

        {{-- API key --}}
        <section class="mt-8 rounded-2xl border border-ink-200 bg-white p-5">
            <h2 class="font-semibold text-ink-900">API key</h2>

            @if ($hasKey)
                <p class="mt-2 text-sm text-emerald-700">
                    ✓ A key is configured{{ $usingOwnKey ? ' (yours, held in this browser session only)' : ' (from the server .env file)' }}.
                </p>
                @if ($usingOwnKey)
                    <form action="{{ route('playground.key.forget') }}" method="POST" class="mt-3">
                        @csrf @method('DELETE')
                        <button class="rounded-lg border border-ink-200 px-3 py-1.5 text-sm font-medium text-ink-600 hover:bg-ink-50">
                            Forget my key
                        </button>
                    </form>
                @endif
            @else
                <p class="mt-2 text-sm text-ink-600">
                    Paste a key from the
                    <a class="text-indigo-600 hover:underline" href="https://console.anthropic.com/settings/keys">Anthropic Console</a>.
                    It is kept in your server-side session and never written to the database — but it is still a
                    secret in someone else's app, so use a key you are happy to rotate afterwards.
                </p>
                <form action="{{ route('playground.key') }}" method="POST" class="mt-3 flex flex-wrap gap-2">
                    @csrf
                    <input type="password" name="api_key" placeholder="sk-ant-…" required
                           class="min-w-0 flex-1 rounded-lg border border-ink-200 px-3 py-2 font-mono text-sm focus:border-indigo-400 focus:outline-none">
                    <button class="rounded-lg bg-ink-900 px-4 py-2 text-sm font-semibold text-white">Use this key</button>
                </form>
                @error('api_key')
                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            @endif
        </section>

        @unless ($hasKey)
            <p class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                The panels below will return an authentication error until a key is set. The code is still worth reading.
            </p>
        @endunless

        {{-- 1. Messages --}}
        <section id="chat" class="mt-8 scroll-mt-20 rounded-2xl border border-ink-200 bg-white p-5 sm:p-6">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-lg font-semibold text-ink-900">1 · A plain message</h2>
                <code class="text-[12px] text-ink-400">PlaygroundController@chat</code>
            </div>
            <p class="mt-2 text-sm text-ink-600">
                Module 1–2. A system prompt sets the rules; the user message carries the task. Watch the token
                counts: that is your bill.
            </p>

            <form data-endpoint="{{ route('playground.chat') }}" data-output="#out-chat" data-render="chat" class="mt-4 space-y-3">
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-ink-400">System prompt</label>
                    <textarea name="system" rows="3"
                              class="mt-1 w-full rounded-lg border border-ink-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none">You are a support triage assistant for an online store. Answer in at most 3 short bullet points. Never invent order numbers or dates.</textarea>
                </div>
                <div>
                    <label class="text-xs font-semibold uppercase tracking-wide text-ink-400">User message</label>
                    <textarea name="prompt" id="chat-prompt" rows="4" required
                              class="mt-1 w-full rounded-lg border border-ink-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none">My package was supposed to arrive on Tuesday and it's now Friday. This is the second time. I want to know where it is and whether I can get the shipping refunded.</textarea>
                </div>
                <div class="flex flex-wrap items-end gap-3">
                    {!! $modelSelect('chat-model') !!}
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-wide text-ink-400">Max tokens</label>
                        <input type="number" name="max_tokens" value="1024" min="64" max="8000"
                               class="mt-1 w-28 rounded-lg border border-ink-200 px-3 py-2 text-sm">
                    </div>
                    <button type="submit" class="ml-auto rounded-xl bg-ink-900 px-5 py-2.5 text-sm font-semibold text-white">
                        Send
                    </button>
                </div>
            </form>

            <div id="out-chat" class="mt-4"></div>
        </section>

        {{-- 2. Structured output --}}
        <section id="extract" class="mt-6 scroll-mt-20 rounded-2xl border border-ink-200 bg-white p-5 sm:p-6">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-lg font-semibold text-ink-900">2 · Structured output</h2>
                <code class="text-[12px] text-ink-400">PlaygroundController@extract</code>
            </div>
            <p class="mt-2 text-sm text-ink-600">
                Module 3. Same input, but a JSON Schema is attached to the request. The response is guaranteed to
                match the schema — no regex, no "please respond with only JSON", no retry loop.
            </p>

            <form data-endpoint="{{ route('playground.extract') }}" data-output="#out-extract" data-render="extract" class="mt-4 space-y-3">
                <textarea name="text" rows="4" required
                          class="w-full rounded-lg border border-ink-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none">Hi — I was charged twice for order ORD-1043 last week and nobody has replied to my two emails. I need the duplicate charge back today or I'm disputing it with my bank.</textarea>
                <div class="flex flex-wrap items-end gap-3">
                    {!! $modelSelect('extract-model') !!}
                    <button type="submit" class="ml-auto rounded-xl bg-ink-900 px-5 py-2.5 text-sm font-semibold text-white">
                        Extract
                    </button>
                </div>
            </form>

            <div id="out-extract" class="mt-4"></div>
        </section>

        {{-- 3. Tool calling --}}
        <section id="tools" class="mt-6 scroll-mt-20 rounded-2xl border border-ink-200 bg-white p-5 sm:p-6">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-lg font-semibold text-ink-900">3 · Tool calling against a real table</h2>
                <code class="text-[12px] text-ink-400">PlaygroundController@tools</code>
            </div>
            <p class="mt-2 text-sm text-ink-600">
                Module 4. Claude is given two functions and no data. It decides which to call and with what
                arguments; your Laravel code runs the query and hands back JSON. The trace shows every step.
            </p>

            @if ($orders->isNotEmpty())
                <details class="mt-3 rounded-lg bg-ink-50 p-3">
                    <summary class="cursor-pointer text-sm font-medium text-ink-700">
                        The demo_orders table ({{ $orders->count() }} rows)
                    </summary>
                    <div class="mt-3 overflow-x-auto">
                        <table class="w-full text-left text-[13px]">
                            <thead class="text-ink-400">
                                <tr>
                                    <th class="py-1 pr-4 font-medium">Reference</th>
                                    <th class="py-1 pr-4 font-medium">Customer</th>
                                    <th class="py-1 pr-4 font-medium">Email</th>
                                    <th class="py-1 pr-4 font-medium">Status</th>
                                    <th class="py-1 font-medium">Expected</th>
                                </tr>
                            </thead>
                            <tbody class="text-ink-700">
                                @foreach ($orders as $order)
                                    <tr class="border-t border-ink-200">
                                        <td class="py-1 pr-4 font-mono">{{ $order->reference }}</td>
                                        <td class="py-1 pr-4">{{ $order->customer_name }}</td>
                                        <td class="py-1 pr-4 font-mono text-[12px]">{{ $order->customer_email }}</td>
                                        <td class="py-1 pr-4">{{ $order->status }}</td>
                                        <td class="py-1">{{ $order->expected_on?->format('M j') ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endif

            <form data-endpoint="{{ route('playground.tools') }}" data-output="#out-tools" data-render="tools" class="mt-4 space-y-3">
                <input type="text" name="question" id="tools-question" required
                       value="Where is my order ORD-1043?"
                       class="w-full rounded-lg border border-ink-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none">

                <div class="flex flex-wrap gap-1.5">
                    @foreach ([
                        'Where is my order ORD-1043?',
                        'I forgot my order number, my email is priya@example.com. What have I got coming?',
                        'Has ORD-1099 shipped yet?',
                        'What is the status of order ORD-9999?',
                    ] as $sample)
                        <button type="button" data-fill="#tools-question" data-value="{{ $sample }}"
                                class="rounded-full bg-ink-100 px-3 py-1 text-[12px] text-ink-600 transition hover:bg-ink-200">
                            {{ Str::limit($sample, 42) }}
                        </button>
                    @endforeach
                </div>

                <div class="flex flex-wrap items-end gap-3">
                    {!! $modelSelect('tools-model') !!}
                    <button type="submit" class="ml-auto rounded-xl bg-ink-900 px-5 py-2.5 text-sm font-semibold text-white">
                        Ask
                    </button>
                </div>
            </form>

            <div id="out-tools" class="mt-4"></div>
        </section>

        {{-- 4. RAG --}}
        <section id="rag" class="mt-6 scroll-mt-20 rounded-2xl border border-ink-200 bg-white p-5 sm:p-6">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-lg font-semibold text-ink-900">4 · RAG over your own text</h2>
                <code class="text-[12px] text-ink-400">PlaygroundController@rag</code>
            </div>
            <p class="mt-2 text-sm text-ink-600">
                Module 7. Leave the box empty to search the seeded company handbook, or paste your own policy,
                README or documentation. The retrieved chunks are shown underneath the answer — that is
                <em>everything</em> the model was allowed to read.
            </p>
            <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-[13px] text-amber-900">
                This demo retrieves with TF-IDF (keyword overlap) so it needs only one API key. Production RAG
                swaps that scorer for embeddings — module 5 explains exactly what changes and what does not.
            </p>

            <form data-endpoint="{{ route('playground.rag') }}" data-output="#out-rag" data-render="rag" class="mt-4 space-y-3">
                <input type="text" name="question" id="rag-question" required
                       value="How long do I have to return something?"
                       class="w-full rounded-lg border border-ink-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none">

                <div class="flex flex-wrap gap-1.5">
                    @foreach ([
                        'How long do I have to return something?',
                        'Do you ship internationally and how much does it cost?',
                        'What happens if my item arrives damaged?',
                        'Can I change the address after ordering?',
                        'What is the CEO\'s home address?',
                    ] as $sample)
                        <button type="button" data-fill="#rag-question" data-value="{{ $sample }}"
                                class="rounded-full bg-ink-100 px-3 py-1 text-[12px] text-ink-600 transition hover:bg-ink-200">
                            {{ Str::limit($sample, 40) }}
                        </button>
                    @endforeach
                </div>

                <details>
                    <summary class="cursor-pointer text-sm font-medium text-ink-700">Use my own document instead</summary>
                    <textarea name="documents" rows="6" placeholder="Paste any policy, handbook, README or docs page here…"
                              class="mt-2 w-full rounded-lg border border-ink-200 px-3 py-2 font-mono text-[13px] focus:border-indigo-400 focus:outline-none"></textarea>
                </details>

                <div class="flex flex-wrap items-end gap-3">
                    {!! $modelSelect('rag-model') !!}
                    <button type="submit" class="ml-auto rounded-xl bg-ink-900 px-5 py-2.5 text-sm font-semibold text-white">
                        Ask the documents
                    </button>
                </div>
            </form>

            <div id="out-rag" class="mt-4"></div>
        </section>

    </div>

@endsection
