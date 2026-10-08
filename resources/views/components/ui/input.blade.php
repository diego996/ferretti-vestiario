@props([
    'icon' => null,
    'invalid' => false,
])

@php
    $name = $attributes->get('name') ?? $attributes->wire('model')->value();
    $hasError = $invalid || ($name && $errors->has($name));
@endphp

<div class="relative">
    @if ($icon)
        <x-dynamic-component :component="'lucide-'.$icon" class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
    @endif
    <input
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $attributes->get('id') }}-error" @endif
        {{ $attributes->class([
            'block h-8 w-full rounded-control border bg-white text-[13px] text-slate-900 shadow-xs transition-colors placeholder:text-slate-400 focus:outline-none focus:ring-2 disabled:bg-slate-50 disabled:text-slate-500',
            'pl-8 pr-2.5' => $icon,
            'px-2.5' => ! $icon,
            'border-line-strong focus:border-primary-500 focus:ring-primary-500/20' => ! $hasError,
            'border-red-400 focus:border-red-500 focus:ring-red-500/20' => $hasError,
        ]) }}
    />
</div>
