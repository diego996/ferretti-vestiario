{{--
    Pannello laterale (es. storico movimenti di una variante).
    Apertura: $dispatch('open-drawer', { id: 'nome' }); chiusura con Esc o clic esterno.
--}}
@props([
    'id',
    'title',
    'description' => null,
    'width' => 'max-w-xl',
])

<div
    x-data="{ open: false }"
    x-on:open-drawer.window="if ($event.detail.id === @js($id)) open = true"
    x-on:close-drawer.window="if (! $event.detail?.id || $event.detail.id === @js($id)) open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-40"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $id }}-title"
>
    <div class="absolute inset-0 bg-navy-950/30" x-on:click="open = false" x-show="open" x-transition.opacity></div>

    <aside
        class="absolute inset-y-0 right-0 flex w-full {{ $width }} flex-col bg-white shadow-pop"
        x-show="open"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="translate-x-full"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-end="translate-x-full"
        x-trap.noscroll="open"
    >
        <header class="flex items-start justify-between gap-3 border-b border-line px-5 py-3">
            <div class="min-w-0">
                <h2 id="{{ $id }}-title" class="truncate text-sm font-semibold text-slate-900">{{ $title }}</h2>
                @if ($description)
                    <p class="truncate text-xs text-slate-500">{{ $description }}</p>
                @endif
            </div>
            <button type="button" class="rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700" x-on:click="open = false" aria-label="Chiudi pannello">
                <x-lucide-x class="size-4" />
            </button>
        </header>
        <div class="flex-1 overflow-y-auto">{{ $slot }}</div>
    </aside>
</div>
