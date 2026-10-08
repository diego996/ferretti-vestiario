@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'icon' => null,
    'iconRight' => null,
    'type' => 'button',
    'disabled' => false,
])

@php
    $base = 'inline-flex shrink-0 items-center justify-center gap-1.5 rounded-control font-medium whitespace-nowrap transition-colors select-none disabled:cursor-not-allowed disabled:opacity-50 aria-disabled:cursor-not-allowed aria-disabled:opacity-50';

    $sizes = [
        'sm' => 'h-7 px-2.5 text-xs',
        'md' => 'h-8 px-3 text-[13px]',
        'lg' => 'h-9 px-4 text-sm',
    ];

    $variants = [
        'primary' => 'bg-primary-600 text-white shadow-sm hover:bg-primary-700 active:bg-primary-800',
        'secondary' => 'border border-line-strong bg-white text-slate-700 shadow-sm hover:bg-slate-50 hover:text-slate-900',
        'ghost' => 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
        'danger' => 'bg-red-600 text-white shadow-sm hover:bg-red-700',
        'success' => 'bg-emerald-600 text-white shadow-sm hover:bg-emerald-700',
    ];

    $iconSize = $size === 'sm' ? 'size-3.5' : 'size-4';
    $classes = $base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href && ! $disabled)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon) <x-dynamic-component :component="'lucide-'.$icon" class="{{ $iconSize }}" aria-hidden="true" /> @endif
        {{ $slot }}
        @if ($iconRight) <x-dynamic-component :component="'lucide-'.$iconRight" class="{{ $iconSize }}" aria-hidden="true" /> @endif
    </a>
@elseif ($href && $disabled)
    <span role="link" aria-disabled="true" {{ $attributes->class($classes) }}>
        @if ($icon) <x-dynamic-component :component="'lucide-'.$icon" class="{{ $iconSize }}" aria-hidden="true" /> @endif
        {{ $slot }}
    </span>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->class($classes) }}>
        @if ($icon) <x-dynamic-component :component="'lucide-'.$icon" class="{{ $iconSize }}" aria-hidden="true" /> @endif
        {{ $slot }}
        @if ($iconRight) <x-dynamic-component :component="'lucide-'.$iconRight" class="{{ $iconSize }}" aria-hidden="true" /> @endif
    </button>
@endif
