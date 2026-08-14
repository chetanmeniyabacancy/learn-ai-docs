@extends('layouts.app')

@section('title', 'Quiz — '.$module['title'])

@section('content')

    <div class="mx-auto max-w-3xl">

        <a href="{{ route('lesson.show', $module['slug']) }}" class="text-sm font-medium text-indigo-600 hover:underline">
            ← Back to {{ $module['title'] }}
        </a>

        <h1 class="mt-3 text-2xl font-bold tracking-tight text-ink-900">
            Quiz · Module {{ $module['code'] }}
        </h1>
        <p class="mt-2 text-sm text-ink-600">
            {{ count($questions) }} questions. Score 70% or more and the module is marked complete.
            Every answer comes with an explanation, so a wrong answer is still worth something.
        </p>

        @if ($result)
            <div @class([
                'mt-6 rounded-2xl border p-5',
                'border-emerald-200 bg-emerald-50' => $result['passed'],
                'border-amber-200 bg-amber-50' => ! $result['passed'],
            ])>
                <p class="text-lg font-semibold {{ $result['passed'] ? 'text-emerald-800' : 'text-amber-900' }}">
                    {{ $result['score'] }} / {{ $result['total'] }} — {{ $result['percentage'] }}%
                    {{ $result['passed'] ? '· Module complete' : '· Not quite' }}
                </p>
                <p class="mt-1 text-sm {{ $result['passed'] ? 'text-emerald-700' : 'text-amber-800' }}">
                    @if ($result['passed'])
                        Read the explanations below for anything you missed, then move on.
                    @else
                        Re-read the sections behind the questions you missed and try again — nothing is recorded against you.
                    @endif
                </p>

                <div class="mt-4 flex flex-wrap gap-3">
                    <a href="{{ route('lesson.quiz', $module['slug']) }}"
                       class="rounded-lg border border-ink-300 bg-white px-4 py-2 text-sm font-semibold text-ink-700">
                        Retake
                    </a>
                    @if ($module['next'])
                        <a href="{{ route('lesson.show', $module['next']['slug']) }}"
                           class="rounded-lg bg-ink-900 px-4 py-2 text-sm font-semibold text-white">
                            Next: {{ $module['next']['title'] }} →
                        </a>
                    @else
                        <a href="{{ route('home') }}" class="rounded-lg bg-ink-900 px-4 py-2 text-sm font-semibold text-white">
                            Back to the curriculum →
                        </a>
                    @endif
                </div>
            </div>

            {{-- Review --}}
            <ol class="mt-8 space-y-4">
                @foreach ($result['review'] as $index => $item)
                    <li class="rounded-2xl border border-ink-200 bg-white p-5">
                        <p class="flex items-start gap-2 font-medium text-ink-900">
                            <span class="{{ $item['correct'] ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $item['correct'] ? '✓' : '✗' }}
                            </span>
                            <span>{{ $index + 1 }}. {{ $item['question']['question'] }}</span>
                        </p>

                        <ul class="mt-3 space-y-1.5 text-sm">
                            @foreach ($item['question']['options'] as $optionIndex => $option)
                                <li @class([
                                    'rounded-lg px-3 py-2',
                                    'bg-emerald-50 font-medium text-emerald-800' => $optionIndex === $item['question']['answer'],
                                    'bg-rose-50 text-rose-800' => $optionIndex === $item['given'] && ! $item['correct'],
                                    'text-ink-600' => $optionIndex !== $item['question']['answer'] && $optionIndex !== $item['given'],
                                ])>
                                    {{ $option }}
                                    @if ($optionIndex === $item['question']['answer'])
                                        <span class="ml-1 text-xs uppercase tracking-wide">correct</span>
                                    @elseif ($optionIndex === $item['given'])
                                        <span class="ml-1 text-xs uppercase tracking-wide">your answer</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        <p class="mt-3 rounded-lg bg-ink-50 px-3 py-2 text-sm text-ink-700">
                            <span class="font-semibold">Why:</span> {{ $item['question']['explanation'] }}
                        </p>
                    </li>
                @endforeach
            </ol>
        @else
            <form action="{{ route('lesson.quiz.submit', $module['slug']) }}" method="POST" class="mt-8 space-y-4">
                @csrf

                @foreach ($questions as $index => $question)
                    <fieldset data-question class="rounded-2xl border border-ink-200 bg-white p-5">
                        <legend class="sr-only">Question {{ $index + 1 }}</legend>
                        <p class="font-medium text-ink-900">{{ $index + 1 }}. {{ $question['question'] }}</p>

                        <div class="mt-3 space-y-1.5">
                            @foreach ($question['options'] as $optionIndex => $option)
                                <label class="flex cursor-pointer items-start gap-3 rounded-lg px-3 py-2 text-sm text-ink-700 transition hover:bg-ink-50 has-checked:bg-indigo-50 has-checked:text-indigo-900">
                                    <input type="radio" name="answers[{{ $index }}]" value="{{ $optionIndex }}"
                                           class="mt-0.5 accent-indigo-600" required>
                                    <span>{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach

                <button type="submit"
                        class="w-full rounded-xl bg-ink-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-ink-800">
                    Check my answers
                </button>
            </form>
        @endif
    </div>

@endsection
