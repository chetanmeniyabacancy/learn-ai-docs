@extends('layouts.app')

@section('title', $example['title'].' — Example')

@section('content')

    <div class="mx-auto max-w-3xl">

        <p class="text-sm">
            <a href="{{ route('examples.index') }}" class="font-medium text-indigo-600 hover:underline">Examples</a>
            <span class="mx-1.5 text-ink-300">/</span>
            <a href="{{ route('lesson.show', $module['slug']) }}" class="text-ink-500 hover:underline">
                {{ $module['title'] }}
            </a>
        </p>

        <h1 class="mt-3 text-2xl font-bold tracking-tight text-ink-900 sm:text-3xl">{{ $example['title'] }}</h1>
        <p class="mt-3 text-[15px] leading-relaxed text-ink-600">{{ $example['intro'] }}</p>

        {{-- Code --}}
        <div class="mt-7 overflow-hidden rounded-2xl border border-ink-200 bg-white">
            <div class="flex items-center justify-between border-b border-ink-200 bg-ink-50 px-4 py-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-ink-500">Example</span>
                <span class="rounded-full bg-ink-200 px-2 py-0.5 text-[11px] font-medium text-ink-600">
                    {{ strtoupper($example['language']) }}
                </span>
            </div>
            <div class="lesson">
                <pre class="!my-0 !rounded-none !border-0"><code class="language-{{ $example['language'] }}">{{ $example['code'] }}</code></pre>
            </div>
        </div>

        {{-- Run --}}
        <div class="mt-4">
            <button type="button" data-run-example
                    class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                Run this example ▶
            </button>
            <span class="ml-3 text-[13px] text-ink-500">No API key needed.</span>
        </div>

        {{-- Output --}}
        <div data-example-output class="mt-4 hidden">
            <div class="overflow-hidden rounded-2xl border border-ink-200 bg-white">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-ink-200 bg-ink-50 px-4 py-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-ink-500">Output</span>
                    <span class="text-[11px] text-ink-500">Recorded output — nothing was sent to the API</span>
                </div>
                <div class="lesson">
                    <pre class="!my-0 !rounded-none !border-0"><code class="language-{{ $example['output_language'] ?? 'text' }}">{{ $example['output'] }}</code></pre>
                </div>
            </div>
        </div>

        {{-- Notes --}}
        @if (! empty($example['notes']))
            <div class="mt-7 rounded-2xl border border-ink-200 bg-white p-5">
                <h2 class="font-semibold text-ink-900">What to notice</h2>
                <ul class="mt-3 space-y-2.5">
                    @foreach ($example['notes'] as $note)
                        <li class="flex gap-2.5 text-[15px] leading-relaxed text-ink-600">
                            <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-400"></span>
                            <span>{!! $note !!}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Live --}}
        @if ($example['live'] ?? null)
            <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <h2 class="font-semibold text-amber-900">Run it for real</h2>
                <p class="mt-1.5 text-[15px] leading-relaxed text-amber-900/85">
                    The output above is recorded so this page always works. To send this to the API and see
                    what <em>your</em> input produces, open the playground — that one needs an API key.
                </p>
                <a href="{{ route('playground.index') }}#{{ $example['live'] }}"
                   class="mt-3 inline-block rounded-xl bg-amber-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-800">
                    Open in the playground →
                </a>
            </div>
        @endif

        {{-- Nav --}}
        <nav class="mt-8 grid gap-3 sm:grid-cols-2">
            @if ($example['previous'])
                <a href="{{ route('examples.show', $example['previous']['id']) }}"
                   class="rounded-xl border border-ink-200 bg-white p-4 transition hover:border-indigo-300">
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-ink-400">Previous example</span>
                    <span class="mt-1 block font-semibold text-ink-800">{{ $example['previous']['title'] }}</span>
                </a>
            @else
                <span></span>
            @endif

            @if ($example['next'])
                <a href="{{ route('examples.show', $example['next']['id']) }}"
                   class="rounded-xl border border-ink-200 bg-white p-4 text-right transition hover:border-indigo-300">
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-ink-400">Next example</span>
                    <span class="mt-1 block font-semibold text-ink-800">{{ $example['next']['title'] }}</span>
                </a>
            @endif
        </nav>

        <p class="mt-6 text-sm text-ink-500">
            This example belongs to
            <a href="{{ route('lesson.show', $module['slug']) }}" class="font-medium text-indigo-600 hover:underline">
                Module {{ $module['code'] }} · {{ $module['title'] }}</a>.
        </p>
    </div>

@endsection
