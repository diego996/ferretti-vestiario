{{--
    Layout amministrativo: sidebar persistente (collassabile) + header + contenuto.
    Usato da tutti i componenti Livewire a pagina intera.

    Dati opzionali (via ->layoutData() o #[Layout]):
    - $title       titolo pagina (anche via #[Title])
    - $breadcrumb  array di ['label' => ..., 'url' => ?]
--}}
@props(['title' => null, 'breadcrumb' => []])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <x-layouts.head :title="$title" />
</head>
<body class="h-full">
    <a href="#contenuto" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded focus:bg-white focus:px-3 focus:py-2 focus:text-sm focus:shadow-pop">
        Vai al contenuto
    </a>

    <div x-data="{ collassata: $persist(false).as('sidebar-collassata') }" class="flex h-full">

        {{-- SIDEBAR --}}
        <aside
            class="flex shrink-0 flex-col bg-navy-950 text-navy-200 transition-[width] duration-150"
            :class="collassata ? 'w-[60px]' : 'w-[248px]'"
            aria-label="Navigazione principale"
        >
            <div class="flex h-14 items-center border-b border-white/10 px-3.5">
                <a href="{{ route('dashboard') }}" class="min-w-0 rounded focus-visible:outline-white" aria-label="{{ config('app.name') }} – Dashboard">
                    <x-brand dark x-show="! collassata" />
                    <x-brand dark compact x-show="collassata" x-cloak />
                </a>
            </div>

            <nav class="flex-1 overflow-y-auto px-2 py-3">
                <ul class="space-y-0.5">
                    @foreach (\App\Support\Navigazione::voci() as $voce)
                        @php $attiva = $voce['disponibile'] && request()->routeIs($voce['pattern']); @endphp
                        <li>
                            @if ($voce['disponibile'])
                                <a
                                    href="{{ route($voce['route']) }}"
                                    wire:navigate
                                    @if ($attiva) aria-current="page" @endif
                                    @class([
                                        'group relative flex h-8 items-center gap-2.5 rounded-md px-2.5 text-[13px] font-medium transition-colors focus-visible:outline-white',
                                        'bg-white/10 text-white' => $attiva,
                                        'text-navy-200 hover:bg-white/5 hover:text-white' => ! $attiva,
                                    ])
                                    :title="collassata ? @js($voce['label']) : null"
                                >
                                    @if ($attiva)
                                        <span class="absolute top-1.5 bottom-1.5 left-0 w-0.5 rounded-full bg-brand-500" aria-hidden="true"></span>
                                    @endif
                                    <x-dynamic-component :component="'lucide-'.$voce['icon']" class="size-4 shrink-0" aria-hidden="true" />
                                    <span class="truncate" x-show="! collassata">{{ $voce['label'] }}</span>
                                </a>
                            @else
                                {{-- Modulo non ancora sviluppato: visibile ma chiaramente non operativo --}}
                                <span
                                    class="flex h-8 cursor-not-allowed items-center gap-2.5 rounded-md px-2.5 text-[13px] text-navy-400/80"
                                    aria-disabled="true"
                                    title="{{ $voce['label'] }} – disponibile dalla Fase {{ $voce['fase'] }}"
                                >
                                    <x-dynamic-component :component="'lucide-'.$voce['icon']" class="size-4 shrink-0" aria-hidden="true" />
                                    <span class="flex-1 truncate" x-show="! collassata">{{ $voce['label'] }}</span>
                                    <span class="rounded bg-white/5 px-1.5 py-px text-[10px] font-medium tracking-wide text-navy-300 uppercase" x-show="! collassata">
                                        Fase {{ $voce['fase'] }}
                                    </span>
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="border-t border-white/10 p-2">
                <button
                    type="button"
                    x-on:click="collassata = ! collassata"
                    class="flex h-8 w-full items-center gap-2.5 rounded-md px-2.5 text-[13px] text-navy-300 hover:bg-white/5 hover:text-white focus-visible:outline-white"
                    :aria-label="collassata ? 'Espandi barra laterale' : 'Comprimi barra laterale'"
                    :aria-expanded="(! collassata).toString()"
                >
                    <x-lucide-panel-left-close class="size-4 shrink-0" x-show="! collassata" aria-hidden="true" />
                    <x-lucide-panel-left-open class="size-4 shrink-0" x-show="collassata" x-cloak aria-hidden="true" />
                    <span x-show="! collassata">Comprimi</span>
                </button>
            </div>
        </aside>

        {{-- AREA PRINCIPALE --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-14 shrink-0 items-center justify-between gap-4 border-b border-line bg-white px-6">
                <div class="min-w-0">
                    @if ($breadcrumb)
                        <nav aria-label="Percorso" class="mb-0.5">
                            <ol class="flex items-center gap-1 text-[11px] text-slate-500">
                                @foreach ($breadcrumb as $voce)
                                    <li class="flex items-center gap-1">
                                        @if (! empty($voce['url']))
                                            <a href="{{ $voce['url'] }}" wire:navigate class="hover:text-slate-800">{{ $voce['label'] }}</a>
                                        @else
                                            <span>{{ $voce['label'] }}</span>
                                        @endif
                                        @unless ($loop->last)
                                            <x-lucide-chevron-right class="size-3" aria-hidden="true" />
                                        @endunless
                                    </li>
                                @endforeach
                            </ol>
                        </nav>
                    @endif
                    <h1 class="truncate text-[15px] font-semibold tracking-tight text-slate-900">{{ $title }}</h1>
                </div>

                <div class="flex shrink-0 items-center gap-3">
                    @if (config('vestiario.demo_mode'))
                        <x-ui.badge tone="warning" title="I dati visualizzati sono fittizi, generati per dimostrazione">
                            <x-lucide-flask-conical class="size-3" aria-hidden="true" /> Dati demo
                        </x-ui.badge>
                    @endif

                    @if (Route::has('consegne.create'))
                        <x-ui.button :href="route('consegne.create')" icon="plus" wire:navigate>Nuova consegna</x-ui.button>
                    @else
                        <x-ui.button icon="plus" disabled title="Disponibile dalla Fase 3">Nuova consegna</x-ui.button>
                    @endif

                    {{-- Menu utente --}}
                    <div x-data="{ aperto: false }" class="relative" x-on:click.outside="aperto = false" x-on:keydown.escape="aperto = false">
                        <button
                            type="button"
                            x-on:click="aperto = ! aperto"
                            class="flex items-center gap-2 rounded-md py-1 pr-1.5 pl-1 hover:bg-slate-100"
                            :aria-expanded="aperto.toString()"
                            aria-haspopup="menu"
                        >
                            <span class="grid size-7 place-items-center rounded-full bg-navy-100 text-[11px] font-semibold text-navy-800" aria-hidden="true">
                                {{ Str::of(auth()->user()->name)->explode(' ')->take(2)->map(fn ($p) => Str::substr($p, 0, 1))->implode('') }}
                            </span>
                            <span class="hidden max-w-36 truncate text-[13px] font-medium text-slate-700 lg:block">{{ auth()->user()->name }}</span>
                            <x-lucide-chevron-down class="size-3.5 text-slate-400" aria-hidden="true" />
                        </button>

                        <div
                            x-show="aperto"
                            x-cloak
                            x-transition.origin.top.right
                            class="absolute right-0 z-30 mt-1 w-56 rounded-panel border border-line bg-white py-1 shadow-pop"
                            role="menu"
                        >
                            <div class="border-b border-line px-3 py-2">
                                <p class="truncate text-[13px] font-medium text-slate-900">{{ auth()->user()->name }}</p>
                                <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2 px-3 py-1.5 text-left text-[13px] text-slate-700 hover:bg-slate-50" role="menuitem">
                                    <x-lucide-log-out class="size-4 text-slate-400" aria-hidden="true" /> Esci
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main id="contenuto" class="flex-1 overflow-y-auto">
                <div class="mx-auto max-w-[1600px] px-6 py-5">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    <x-ui.toasts />
    @livewireScripts
</body>
</html>
