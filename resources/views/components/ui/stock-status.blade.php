{{-- Stato disponibilità: colore + testo (mai solo colore). --}}
@props(['stato'])

<x-ui.badge :tone="$stato->tone()" dot {{ $attributes }}>{{ $stato->label() }}</x-ui.badge>
