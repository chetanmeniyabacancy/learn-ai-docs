@extends('layouts.app')

@section('title', $module['title'].' — '.config('curriculum.title'))

@section('content')

    <div class="lg:grid lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-10">

        {{-- Module rail. Sticky, and scrollable on its own — with 20 modules plus
             the page outline it is taller than the viewport. --}}
        <aside class="mb-8 lg:mb-0">
            <nav class="lg:sticky lg:top-20 lg:max-h-[calc(100vh-6rem)] lg:overflow-y-auto lg:overscroll-contain lg:pr-2 lg:pb-6">

                @foreach ($levels as $level)
                    @php
                        $done = $level['modules']->filter(fn ($m) => $completed->contains($m['slug']))->count();
                        $isCurrentLevel = $level['modules']->contains(fn ($m) => $m['slug'] === $module['slug']);
                    @endphp

                    {{-- Collapsed by default unless it holds the lesson you are reading. --}}
                    <details @class(['group', 'mt-4' => ! $loop->first]) @if ($isCurrentLevel) open @endif>
                        <summary class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg px-1 py-1 text-[11px] font-semibold uppercase tracking-wide text-ink-400 transition hover:text-ink-700">
                            <span class="rail-chevron">▸</span>
                            <span class="flex-1 truncate">{{ $level['title'] }}</span>
                            <span class="font-normal normal-case tracking-normal text-ink-400">
                                {{ $done }}/{{ $level['modules']->count() }}
                            </span>
                        </summary>

                        <ol class="mt-1.5 space-y-0.5 text-[13px]">
                            @foreach ($level['modules'] as $item)
                                <li>
                                    <a href="{{ route('lesson.show', $item['slug']) }}"
                                       @class([
                                           'flex items-center gap-2 rounded-lg px-2 py-1.5 transition',
                                           'bg-ink-900 font-semibold text-white' => $item['slug'] === $module['slug'],
                                           'text-ink-600 hover:bg-ink-100' => $item['slug'] !== $module['slug'],
                                       ])>
                                        <span @class([
                                            'w-5 shrink-0 text-center text-[11px]',
                                            'text-emerald-500' => $completed->contains($item['slug']),
                                            'opacity-60' => ! $completed->contains($item['slug']),
                                        ])>
                                            {{ $completed->contains($item['slug']) ? '✓' : $item['code'] }}
                                        </span>
                                        <span class="truncate">{{ $item['title'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ol>
                    </details>
                @endforeach

                @if ($outline)
                    <details class="group mt-4" open>
                        <summary class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg px-1 py-1 text-[11px] font-semibold uppercase tracking-wide text-ink-400 transition hover:text-ink-700">
                            <span class="rail-chevron">▸</span>
                            <span class="flex-1">On this page</span>
                        </summary>

                        <ul class="mt-1.5 space-y-1 border-l border-ink-200 text-[13px]">
                            @foreach ($outline as $heading)
                                <li>
                                    <a href="#{{ $heading['anchor'] }}" data-toc-link
                                       class="-ml-px block border-l border-transparent py-0.5 pl-3 text-ink-500 transition hover:text-ink-900">
                                        {{ $heading['title'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </nav>
        </aside>

        {{-- Lesson --}}
        <article class="min-w-0">
            <div class="rounded-2xl border border-ink-200 bg-white p-6 sm:p-9">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">
                    {{ config('curriculum.levels.'.$module['level'].'.title') }} ·
                    Module {{ $module['code'] }} · {{ $module['minutes'] }} min
                </p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-ink-900">{{ $module['title'] }}</h1>
                <p class="mt-3 text-[15px] leading-relaxed text-ink-600">{{ $module['tagline'] }}</p>

                <div class="lesson prose prose-ink mt-8 max-w-none prose-headings:font-semibold prose-headings:tracking-tight prose-h2:mt-10 prose-h2:border-t prose-h2:border-ink-100 prose-h2:pt-8 prose-h2:text-xl prose-h3:mt-7 prose-h3:text-[17px] prose-p:text-[15px] prose-p:leading-relaxed prose-li:text-[15px] prose-a:text-indigo-600">
                    {!! $body !!}
                </div>
            </div>

            {{-- Examples --}}
            @if ($examples->isNotEmpty())
                <section class="mt-6 rounded-2xl border border-ink-200 bg-white p-6 sm:p-7">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <h2 class="text-lg font-semibold text-ink-900">Examples for this module</h2>
                        <span class="text-xs text-ink-400">code + output · no API key needed</span>
                    </div>
                    <p class="mt-1.5 text-sm text-ink-600">
                        Each one shows the code and exactly what it produces. Open it, read it, press Run.
                    </p>

                    <ul class="mt-4 space-y-2">
                        @foreach ($examples as $example)
                            <li>
                                <a href="{{ route('examples.show', $example['id']) }}"
                                   class="group flex items-start gap-3 rounded-xl border border-ink-200 p-4 transition hover:border-indigo-300 hover:bg-ink-50">
                                    <span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-emerald-100 text-[13px] text-emerald-700">▶</span>
                                    <span class="min-w-0 flex-1">
                                        <span class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                            <span class="font-semibold text-ink-900 group-hover:text-indigo-700">{{ $example['title'] }}</span>
                                            @if ($example['live'] ?? null)
                                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700">
                                                    live version
                                                </span>
                                            @endif
                                        </span>
                                        <span class="mt-1 block text-sm leading-relaxed text-ink-600">{{ $example['intro'] }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Footer actions --}}
            <div class="mt-6 flex flex-wrap items-center gap-3">
                @if ($hasQuiz)
                    <a href="{{ route('lesson.quiz', $module['slug']) }}"
                       class="rounded-xl bg-ink-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-ink-800">
                        Take the quiz →
                    </a>
                @endif

                <form action="{{ route('lesson.complete', $module['slug']) }}" method="POST">
                    @csrf
                    @if ($isComplete)
                        <input type="hidden" name="undo" value="1">
                        <button class="rounded-xl border border-ink-200 px-5 py-2.5 text-sm font-semibold text-ink-600 transition hover:bg-ink-50">
                            ✓ Completed — mark unread
                        </button>
                    @else
                        <button class="rounded-xl border border-ink-200 px-5 py-2.5 text-sm font-semibold text-ink-700 transition hover:bg-ink-50">
                            Mark done &amp; continue
                        </button>
                    @endif
                </form>

                <a href="{{ route('playground.index') }}" class="text-sm font-medium text-indigo-600 hover:underline">
                    Run it live (needs an API key) →
                </a>
            </div>

            {{-- Prev / next --}}
            <nav class="mt-8 grid gap-3 sm:grid-cols-2">
                @if ($module['previous'])
                    <a href="{{ route('lesson.show', $module['previous']['slug']) }}"
                       class="rounded-xl border border-ink-200 bg-white p-4 transition hover:border-indigo-300">
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-ink-400">Previous</span>
                        <span class="mt-1 block font-semibold text-ink-800">{{ $module['previous']['title'] }}</span>
                    </a>
                @else
                    <span></span>
                @endif

                @if ($module['next'])
                    <a href="{{ route('lesson.show', $module['next']['slug']) }}"
                       class="rounded-xl border border-ink-200 bg-white p-4 text-right transition hover:border-indigo-300">
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-ink-400">Next</span>
                        <span class="mt-1 block font-semibold text-ink-800">{{ $module['next']['title'] }}</span>
                    </a>
                @endif
            </nav>
        </article>
    </div>

@endsection
