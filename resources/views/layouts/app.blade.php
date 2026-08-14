<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('curriculum.title'))</title>
    <meta name="description" content="{{ config('curriculum.subtitle') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-ink-50 font-sans text-ink-800 antialiased">

<header class="sticky top-0 z-40 border-b border-ink-200 bg-white/85 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center gap-4 px-4 py-3">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5">
            <span class="grid h-8 w-8 place-items-center rounded-lg bg-ink-900 text-sm font-bold text-white">AI</span>
            <span class="text-[15px] font-semibold tracking-tight">{{ config('curriculum.title') }}</span>
            <span class="hidden rounded-full bg-ink-100 px-2 py-0.5 text-[11px] font-medium text-ink-500 sm:inline">Level 1</span>
        </a>

        <nav class="ml-auto flex items-center gap-1 text-sm">
            <a href="{{ route('home') }}"
               class="rounded-lg px-3 py-1.5 font-medium transition hover:bg-ink-100 {{ request()->routeIs('home') ? 'bg-ink-100 text-ink-900' : 'text-ink-600' }}">
                Curriculum
            </a>
            <a href="{{ route('examples.index') }}"
               class="rounded-lg px-3 py-1.5 font-medium transition hover:bg-ink-100 {{ request()->routeIs('examples.*') ? 'bg-ink-100 text-ink-900' : 'text-ink-600' }}">
                Examples
            </a>
            <a href="{{ route('playground.index') }}"
               class="rounded-lg px-3 py-1.5 font-medium transition hover:bg-ink-100 {{ request()->routeIs('playground.*') ? 'bg-ink-100 text-ink-900' : 'text-ink-600' }}">
                Live run
            </a>
            <a href="{{ route('glossary') }}"
               class="hidden rounded-lg px-3 py-1.5 font-medium transition hover:bg-ink-100 sm:block {{ request()->routeIs('glossary') ? 'bg-ink-100 text-ink-900' : 'text-ink-600' }}">
                Glossary
            </a>
        </nav>
    </div>
</header>

@if (session('status'))
    <div class="mx-auto mt-4 max-w-6xl px-4">
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('status') }}
        </div>
    </div>
@endif

<main class="mx-auto max-w-6xl px-4 py-8 sm:py-10">
    @yield('content')
</main>

<footer class="mt-16 border-t border-ink-200 bg-white">
    <div class="mx-auto max-w-6xl px-4 py-8 text-sm text-ink-500">
        <p class="font-medium text-ink-700">{{ config('curriculum.title') }}</p>
        <p class="mt-1 max-w-2xl">
            A Level 1 track for backend engineers: enough AI to ship a real feature, and nothing you would
            not use in the next three months. The ideas are language-neutral; Level 1 shows them in
            {{ config('curriculum.language.label') }}. Model names, prices and API shapes were current at
            build time — check the Anthropic docs before you rely on a number.
        </p>
        <p class="mt-3">
            <a class="text-indigo-600 hover:underline" href="https://platform.claude.com/docs">Anthropic docs</a>
            <span class="mx-2 text-ink-300">·</span>
            <a class="text-indigo-600 hover:underline" href="{{ route('examples.index') }}">Examples</a>
            <span class="mx-2 text-ink-300">·</span>
            <a class="text-indigo-600 hover:underline" href="{{ route('glossary') }}">Glossary</a>
            <span class="mx-2 text-ink-300">·</span>
            <form action="{{ route('progress.reset') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="text-ink-500 hover:text-rose-600 hover:underline">Reset my progress</button>
            </form>
        </p>
    </div>
</footer>

</body>
</html>
