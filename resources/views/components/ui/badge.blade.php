@props([
    'tone' => 'neutral',
    'dot' => false,
])

@php
    $tones = [
        'neutral' => ['bg-slate-100 text-slate-700 ring-slate-200', 'bg-slate-400'],
        'primary' => ['bg-primary-50 text-primary-800 ring-primary-200', 'bg-primary-500'],
        'success' => ['bg-emerald-50 text-emerald-800 ring-emerald-200', 'bg-emerald-500'],
        'warning' => ['bg-amber-50 text-amber-800 ring-amber-200', 'bg-amber-500'],
        'danger' => ['bg-red-50 text-red-800 ring-red-200', 'bg-red-500'],
    ];

    [$colors, $dotColor] = $tones[$tone] ?? $tones['neutral'];
@endphp

<span {{ $attributes->class("inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-medium whitespace-nowrap ring-1 ring-inset $colors") }}>
    @if ($dot)
        <span class="size-1.5 rounded-full {{ $dotColor }}" aria-hidden="true"></span>
    @endif
    {{ $slot }}
</span>
