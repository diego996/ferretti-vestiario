@props([
    'invalid' => false,
    'rows' => 3,
])

@php
    $name = $attributes->get('name') ?? $attributes->wire('model')->value();
    $hasError = $invalid || ($name && $errors->has($name));
@endphp

<textarea
    rows="{{ $rows }}"
    @if ($hasError) aria-invalid="true" aria-describedby="{{ $attributes->get('id') }}-error" @endif
    {{ $attributes->class([
        'block w-full rounded-control border bg-white px-2.5 py-1.5 text-[13px] text-slate-900 shadow-xs transition-colors placeholder:text-slate-400 focus:outline-none focus:ring-2 disabled:bg-slate-50',
        'border-line-strong focus:border-primary-500 focus:ring-primary-500/20' => ! $hasError,
        'border-red-400 focus:border-red-500 focus:ring-red-500/20' => $hasError,
    ]) }}
></textarea>
