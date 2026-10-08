@props([
    'label',
    'value',
    'icon',
    'hint' => null,
    'tone' => 'primary',
])

@php
    $iconTones = [
        'primary' => 'bg-primary-50 text-primary-700',
        'success' => 'bg-emerald-50 text-emerald-700',
        'warning' => 'bg-amber-50 text-amber-700',
        'neutral' => 'bg-slate-100 text-slate-600',
    ];
@endphp

<div {{ $attributes->class('panel flex items-start gap-3 px-4 py-3') }}>
    <span class="grid size-9 shrink-0 place-items-center rounded-md {{ $iconTones[$tone] ?? $iconTones['primary'] }}" aria-hidden="true">
        <x-dynamic-component :component="'lucide-'.$icon" class="size-[18px]" />
    </span>
    <div class="min-w-0">
        <p class="truncate text-xs font-medium text-slate-500">{{ $label }}</p>
        <p class="tabular mt-0.5 text-2xl leading-7 font-semibold tracking-tight text-slate-900">{{ $value }}</p>
        @if ($hint)
            <p class="mt-0.5 truncate text-xs text-slate-500">{{ $hint }}</p>
        @endif
    </div>
</div>
