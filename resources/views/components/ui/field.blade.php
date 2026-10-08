{{-- Etichetta + controllo + errore contestuale. Il controllo va nello slot. --}}
@props([
    'label',
    'for',
    'error' => null,
    'hint' => null,
    'required' => false,
])

@php $messaggio = $error ?? $errors->first($for); @endphp

<div {{ $attributes->class('space-y-1') }}>
    <label for="{{ $for }}" class="block text-xs font-medium text-slate-700">
        {{ $label }}
        @if ($required)
            <span class="text-red-600" aria-hidden="true">*</span>
        @endif
    </label>

    {{ $slot }}

    @if ($messaggio)
        <p id="{{ $for }}-error" class="flex items-center gap-1 text-xs text-red-700" role="alert">
            <x-lucide-circle-alert class="size-3.5 shrink-0" aria-hidden="true" />
            {{ $messaggio }}
        </p>
    @elseif ($hint)
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
