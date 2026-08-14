<div>
    <label for="{{ $id }}" class="text-xs font-semibold uppercase tracking-wide text-ink-400">Model</label>
    <select name="model" id="{{ $id }}"
            class="mt-1 rounded-lg border border-ink-200 px-3 py-2 text-sm focus:border-indigo-400 focus:outline-none">
        @foreach ($models as $value => $model)
            <option value="{{ $value }}" @selected($value === $defaultModel)>
                {{ $model['label'] }} — ${{ number_format($model['input'], 2) }}/${{ number_format($model['output'], 2) }} per Mtok
            </option>
        @endforeach
    </select>
</div>
