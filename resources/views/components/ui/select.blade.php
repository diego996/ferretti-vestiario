@props([
    'placeholder' => null,
    'invalid' => false,
])

@php
    $name = $attributes->get('name') ?? $attributes->wire('model')->value();
    $hasError = $invalid || ($name && $errors->has($name));
@endphp

<div class="relative">
    <select
        @if ($hasError) aria-invalid="true" @endif
        {{ $attributes->class([
            'block h-8 w-full appearance-none rounded-control border bg-white pr-8 pl-2.5 text-[13px] text-slate-900 shadow-xs focus:outline-none focus:ring-2 disabled:bg-slate-50',
            'border-line-strong focus:border-primary-500 focus:ring-primary-500/20' => ! $hasError,
            'border-red-400 focus:border-red-500 focus:ring-red-500/20' => $hasError,
        ]) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        {{ $slot }}
    </select>
    <x-lucide-chevrons-up-down class="pointer-events-none absolute top-1/2 right-2 size-3.5 -translate-y-1/2 text-slate-400" aria-hidden="true" />
</div>
