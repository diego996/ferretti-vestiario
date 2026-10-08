{{--
    Marchio applicazione. Se VESTIARIO_LOGO_PATH è configurato mostra il logo
    ufficiale, altrimenti un branding provvisorio neutro (icona + nome).
    Nessun logo viene inventato o imitato.
--}}
@props([
    'dark' => false,
    'compact' => false,
])

@php
    $logo = $dark ? (config('vestiario.logo_dark_path') ?? config('vestiario.logo_path')) : config('vestiario.logo_path');
    $azienda = config('vestiario.company_name');
@endphp

<div {{ $attributes->class('flex min-w-0 items-center gap-2.5') }}>
    @if ($logo && ! $compact)
        <img src="{{ asset($logo) }}" alt="{{ $azienda }}" class="h-7 w-auto max-w-[140px] object-contain">
    @else
        <span @class([
            'relative grid size-8 shrink-0 place-items-center rounded-md',
            'bg-white/10 text-white ring-1 ring-white/15' => $dark,
            'bg-navy-950 text-white' => ! $dark,
        ]) aria-hidden="true">
            <x-lucide-shirt class="size-[18px]" />
            <span class="absolute bottom-1 left-1.5 h-0.5 w-2.5 rounded-full bg-brand-500"></span>
        </span>
    @endif

    @unless ($compact)
        <div class="min-w-0 leading-tight">
            <p @class(['truncate text-[13px] font-semibold tracking-tight', 'text-white' => $dark, 'text-slate-900' => ! $dark])>
                {{ $logo ? 'Gestione Vestiario' : Str::before(config('app.name'), ' |') }}
            </p>
            <p @class(['truncate text-[11px]', 'text-navy-300' => $dark, 'text-slate-500' => ! $dark])>
                {{ $logo ? $azienda : Str::after(config('app.name'), '| ') }}
            </p>
        </div>
    @endunless
</div>
