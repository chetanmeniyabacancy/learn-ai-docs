@extends('layouts.app')

@section('title', 'Examples — '.config('curriculum.title'))

@section('content')

    <div class="mx-auto max-w-3xl">

        <h1 class="text-3xl font-bold tracking-tight text-ink-900">Examples</h1>
        <p class="mt-3 text-[15px] leading-relaxed text-ink-600">
            {{ $total }} worked examples: the code, and the output that code produces. Open one, read it, press
            <strong>Run</strong> to see the result. Nothing here calls an API, so every example works with no
            key, offline, forever.
        </p>
        <p class="mt-3 rounded-xl border border-ink-200 bg-white px-4 py-3 text-[14px] text-ink-600">
            Examples are in {{ config('curriculum.language.label') }}.
            {{ config('curriculum.language.note') }}
        </p>

        @foreach ($groups as $group)
            <section class="mt-9">
                <div class="flex items-baseline gap-2">
                    <h2 class="text-lg font-semibold text-ink-900">
                        <a href="{{ route('lesson.show', $group['module']['slug']) }}" class="hover:text-indigo-700">
                            {{ $group['module']['code'] }} · {{ $group['module']['title'] }}
                        </a>
                    </h2>
                    <span class="text-xs text-ink-400">{{ $group['examples']->count() }} examples</span>
                </div>

                <ul class="mt-3 space-y-2">
                    @foreach ($group['examples'] as $example)
                        <li>
                            <a href="{{ route('examples.show', $example['id']) }}"
                               class="group block rounded-xl border border-ink-200 bg-white p-4 transition hover:border-indigo-300 hover:shadow-sm">
                                <span class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <span class="font-semibold text-ink-900 group-hover:text-indigo-700">{{ $example['title'] }}</span>
                                    <span class="rounded-full bg-ink-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-ink-500">
                                        {{ $example['language'] }}
                                    </span>
                                    @if ($example['live'] ?? null)
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700">
                                            live version
                                        </span>
                                    @endif
                                </span>
                                <span class="mt-1 block text-sm leading-relaxed text-ink-600">{{ $example['intro'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>

@endsection
