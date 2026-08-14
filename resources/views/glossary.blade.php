@extends('layouts.app')

@section('title', 'Glossary — '.config('curriculum.title'))

@section('content')

    <div class="mx-auto max-w-3xl">
        <h1 class="text-3xl font-bold tracking-tight text-ink-900">Glossary</h1>
        <p class="mt-3 text-[15px] leading-relaxed text-ink-600">
            Every term in the course, defined the way a backend engineer would want it defined — what it is,
            and what it means for your code.
        </p>

        <dl class="mt-8 space-y-3">
            @foreach ($terms as $term => $definition)
                <div class="rounded-xl border border-ink-200 bg-white p-4">
                    <dt class="font-semibold text-ink-900">{{ $term }}</dt>
                    <dd class="mt-1 text-[15px] leading-relaxed text-ink-600">{!! $definition !!}</dd>
                </div>
            @endforeach
        </dl>
    </div>

@endsection
