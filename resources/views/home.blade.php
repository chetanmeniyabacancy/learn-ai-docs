@extends('layouts.app')

@section('title', config('curriculum.title'))

@section('content')

    <section class="rounded-2xl border border-ink-200 bg-white p-6 sm:p-9">
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">
            Foundations → AI Application Engineer
        </p>
        <h1 class="mt-3 max-w-2xl text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">
            You already know how to build software. Learn AI from the root.
        </h1>
        <p class="mt-4 max-w-2xl text-[15px] leading-relaxed text-ink-600">
            {{ count($modules) }} modules in two levels. <strong class="text-ink-800">Level 0</strong> is how
            models actually work — learning, neural networks, tokens, attention — with the maths done by hand
            so nothing stays abstract. <strong class="text-ink-800">Level 1</strong> is shipping real features:
            a business problem, the idea that solves it, the code that ships it.
        </p>

        <p class="mt-4 max-w-2xl rounded-xl border border-ink-200 bg-ink-50 px-4 py-3 text-[14px] leading-relaxed text-ink-600">
            <strong class="text-ink-800">Examples are in {{ config('curriculum.language.label') }}.</strong>
            {{ config('curriculum.language.note') }}
        </p>

        <div class="mt-6 flex flex-wrap items-center gap-3">
            <a href="{{ route('lesson.show', $next['slug']) }}"
               class="rounded-xl bg-ink-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-ink-800">
                {{ $percentage > 0 ? 'Continue: '.$next['title'] : 'Start at the beginning' }}
            </a>
            <a href="{{ route('examples.index') }}"
               class="rounded-xl border border-ink-200 px-5 py-2.5 text-sm font-semibold text-ink-700 transition hover:bg-ink-50">
                Browse {{ $exampleCount }} examples
            </a>
        </div>

        <dl class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ([
                ['Modules', count($modules)],
                ['Worked examples', $exampleCount],
                ['Quiz questions', collect($modules)->sum(fn ($m) => count(app(\App\Support\Curriculum::class)->quiz($m['slug'])))],
                ['Your progress', $percentage.'%'],
            ] as [$label, $value])
                <div class="rounded-xl bg-ink-50 px-4 py-3">
                    <dt class="text-[11px] font-semibold uppercase tracking-wide text-ink-400">{{ $label }}</dt>
                    <dd class="mt-1 text-xl font-semibold text-ink-900">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="mt-6">
            <div class="h-2 overflow-hidden rounded-full bg-ink-100">
                <div class="h-full rounded-full bg-indigo-500 transition-all" style="width: {{ max($percentage, 1) }}%"></div>
            </div>
        </div>
    </section>

    @foreach ($levels as $level)
        <section class="mt-10">
            <div class="rounded-2xl border border-ink-200 bg-white p-5">
                <h2 class="text-lg font-semibold text-ink-900">{{ $level['title'] }}</h2>
                <p class="mt-1 text-sm text-ink-600">{{ $level['tagline'] }}</p>
                <p class="mt-2 text-[13px] leading-relaxed text-ink-500">{{ $level['blurb'] }}</p>
                <p class="mt-3 text-[13px] text-ink-400">
                    {{ $level['modules']->count() }} modules ·
                    {{ $level['modules']->sum('minutes') }} minutes ·
                    {{ $level['modules']->filter(fn ($m) => $completed->contains($m['slug']))->count() }} done
                </p>
            </div>

            <ol class="mt-3 space-y-3">
                @foreach ($level['modules'] as $module)
                    @php
                        $done = $completed->contains($module['slug']);
                        $score = $scores[$module['slug']] ?? null;
                    @endphp
                    <li>
                        <a href="{{ route('lesson.show', $module['slug']) }}"
                           class="group flex gap-4 rounded-2xl border border-ink-200 bg-white p-4 transition hover:border-indigo-300 hover:shadow-sm sm:p-5">

                            <span @class([
                                'grid h-9 w-9 shrink-0 place-items-center rounded-xl text-sm font-bold',
                                'bg-emerald-100 text-emerald-700' => $done,
                                'bg-ink-100 text-ink-500' => ! $done,
                            ])>
                                {{ $done ? '✓' : $module['code'] }}
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <span class="font-semibold text-ink-900 group-hover:text-indigo-700">{{ $module['title'] }}</span>
                                    <span class="text-xs text-ink-400">{{ $module['minutes'] }} min</span>
                                    @if ($score)
                                        <span class="rounded-full bg-ink-100 px-2 py-0.5 text-[11px] font-medium text-ink-600">
                                            quiz {{ $score->score }}/{{ $score->total }}
                                        </span>
                                    @endif
                                </span>
                                <span class="mt-1 block text-sm text-ink-600">{{ $module['tagline'] }}</span>
                                <span class="mt-2 block text-[13px] text-ink-400">
                                    <span class="font-medium text-ink-500">Problem:</span> {{ $module['problem'] }}
                                </span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ol>
        </section>
    @endforeach

    <section class="mt-10 grid gap-4 sm:grid-cols-2">
        <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5">
            <h3 class="font-semibold text-ink-900">In a hurry? The fast track</h3>
            <p class="mt-2 text-sm text-ink-600">
                Every lesson opens with a <strong class="text-ink-800">Summary</strong> block holding the whole
                lesson in 5–6 lines. Reading only those takes about an hour.
            </p>
            <ol class="mt-3 space-y-2 text-sm text-ink-600">
                <li><strong class="text-ink-800">1.</strong> Read the <em>Summary</em> block.</li>
                <li><strong class="text-ink-800">2.</strong> Open one example and press Run.</li>
                <li><strong class="text-ink-800">3.</strong> Take the quiz. Wrong answers point you at the section to read properly.</li>
                <li><strong class="text-ink-800">4.</strong> Only read the full lesson where the quiz caught you out.</li>
            </ol>
            <p class="mt-3 text-[13px] text-ink-500">
                Three days at that pace covers both levels. Then spend the rest of the week on the capstone —
                that is where it sticks.
            </p>
        </div>
        <div class="rounded-2xl border border-ink-200 bg-white p-5">
            <h3 class="font-semibold text-ink-900">What this course leaves out</h3>
            <ul class="mt-3 space-y-2 text-sm text-ink-600">
                <li>· Training or fine-tuning your own model. Level 0 explains how it works and why you will
                    not be doing it.</li>
                <li>· University maths. Gradient descent and attention are shown as code you can read, not as
                    proofs.</li>
                <li>· Agents, MCP, multi-agent systems and memory — that is Level 2, and it builds directly on
                    Level 1.</li>
            </ul>
        </div>
    </section>

@endsection
