{{--
    Modale di conferma per azioni irreversibili (es. annullamento consegna).
    Apertura: $dispatch('open-confirm', { id: 'nome' }) oppure x-on:click="$dispatch(...)".
    Il pulsante di conferma va passato nello slot "confirm".
--}}
@props([
    'id',
    'title',
    'tone' => 'danger',
])

<div
    x-data="{ open: false }"
    x-on:open-confirm.window="if ($event.detail.id === @js($id)) open = true"
    x-on:close-confirm.window="if (! $event.detail?.id || $event.detail.id === @js($id)) open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-40 flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $id }}-title"
>
    <div class="absolute inset-0 bg-navy-950/40" x-on:click="open = false" x-show="open" x-transition.opacity></div>

    <div class="relative w-full max-w-md rounded-panel bg-white shadow-pop" x-show="open" x-transition x-trap.noscroll="open">
        <div class="flex gap-3 px-5 pt-5">
            <span @class([
                'grid size-9 shrink-0 place-items-center rounded-full',
                'bg-red-50 text-red-600' => $tone === 'danger',
                'bg-amber-50 text-amber-600' => $tone === 'warning',
                'bg-primary-50 text-primary-700' => $tone === 'primary',
            ]) aria-hidden="true">
                <x-lucide-triangle-alert class="size-[18px]" />
            </span>
            <div class="min-w-0 flex-1">
                <h2 id="{{ $id }}-title" class="text-sm font-semibold text-slate-900">{{ $title }}</h2>
                <div class="mt-1 text-[13px] text-slate-600">{{ $slot }}</div>
            </div>
        </div>
        <div class="mt-5 flex justify-end gap-2 rounded-b-panel border-t border-line bg-slate-50 px-5 py-3">
            <x-ui.button variant="secondary" x-on:click="open = false">Annulla</x-ui.button>
            {{ $confirm }}
        </div>
    </div>
</div>
