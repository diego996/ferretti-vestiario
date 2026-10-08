@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <x-layouts.head :title="$title" />
</head>
<body class="h-full bg-white">
    <div class="grid min-h-full lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">

        {{-- Pannello istituzionale --}}
        <aside class="relative hidden overflow-hidden bg-navy-950 lg:flex lg:flex-col lg:justify-between lg:p-10">
            {{-- Trama tecnica discreta (griglia), nessuna immagine protetta --}}
            <div class="pointer-events-none absolute inset-0 opacity-[0.07]" aria-hidden="true"
                 style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 32px 32px;"></div>

            <x-brand dark class="relative" />

            <div class="relative max-w-md">
                <span class="mb-5 block h-1 w-10 rounded-full bg-brand-500" aria-hidden="true"></span>
                <h2 class="text-2xl leading-snug font-semibold tracking-tight text-white">
                    Vestiario, DPI e consegne<br>in un unico registro.
                </h2>
                <ul class="mt-6 space-y-2.5 text-sm text-navy-200">
                    <li class="flex items-center gap-2.5"><x-lucide-warehouse class="size-4 text-primary-300" aria-hidden="true" /> Giacenze sempre aggiornate per taglia</li>
                    <li class="flex items-center gap-2.5"><x-lucide-clipboard-check class="size-4 text-primary-300" aria-hidden="true" /> Consegne per collega e commessa</li>
                    <li class="flex items-center gap-2.5"><x-lucide-file-text class="size-4 text-primary-300" aria-hidden="true" /> Verbale Word pronto da firmare</li>
                </ul>
            </div>

            <p class="relative text-xs text-navy-400">{{ config('vestiario.company_name') }} · uso interno</p>
        </aside>

        {{-- Form --}}
        <main class="flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-sm">
                <x-brand class="mb-10 lg:hidden" />
                {{ $slot }}
            </div>
        </main>
    </div>

    @livewireScripts
</body>
</html>
