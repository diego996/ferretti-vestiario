@use('Illuminate\Support\Number')

@php
    $azioni = [
        ['label' => 'Nuova consegna', 'descrizione' => 'Registra e stampa il verbale', 'icon' => 'clipboard-check', 'route' => 'consegne.create', 'fase' => 3],
        ['label' => 'Nuovo carico', 'descrizione' => 'Aggiungi merce al magazzino', 'icon' => 'package-plus', 'route' => 'carichi.create', 'fase' => 2],
        ['label' => 'Consulta magazzino', 'descrizione' => 'Giacenze per articolo e taglia', 'icon' => 'warehouse', 'route' => 'magazzino.index', 'fase' => 2],
    ];
    $dettaglioDisponibile = Route::has('consegne.show');
@endphp

<div class="space-y-5">

    {{-- KPI --}}
    <section aria-label="Indicatori principali" class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        <x-ui.kpi
            label="Unità disponibili"
            :value="Number::format($this->unitaDisponibili)"
            icon="boxes"
            hint="Su articoli e taglie attivi"
        />
        <x-ui.kpi
            label="Consegne del mese"
            :value="Number::format($this->meseCorrente['consegne'])"
            icon="clipboard-check"
            tone="success"
            :hint="Number::format($this->meseCorrente['unita']).' unità consegnate a '.now()->translatedFormat('F')"
        />
        <x-ui.kpi
            label="Varianti sotto scorta"
            :value="Number::format($this->sottoScorta['totale'])"
            icon="triangle-alert"
            :tone="$this->sottoScorta['totale'] > 0 ? 'warning' : 'success'"
            :hint="$this->sottoScorta['esaurite'] > 0 ? 'di cui '.$this->sottoScorta['esaurite'].' esaurite' : 'Nessuna variante esaurita'"
        />
        <x-ui.kpi
            label="Colleghi registrati"
            :value="Number::format($this->colleghi['totale'])"
            icon="users"
            tone="neutral"
            :hint="$this->colleghi['attivi'].' attivi'"
        />
    </section>

    <div class="grid gap-5 xl:grid-cols-3 xl:items-start">

        {{-- Ultime consegne --}}
        <x-ui.panel title="Ultime consegne" icon="clipboard-list" flush class="xl:col-span-2">
            <x-slot:actions>
                @if (Route::has('consegne.index'))
                    <x-ui.button variant="ghost" size="sm" :href="route('consegne.index')" icon-right="arrow-right" wire:navigate>Tutte</x-ui.button>
                @endif
            </x-slot:actions>

            @if ($this->ultimeConsegne->isEmpty())
                <x-ui.empty-state
                    icon="clipboard-list"
                    title="Nessuna consegna registrata"
                    description="Le consegne compariranno qui appena registrate."
                />
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Data</th>
                                <th scope="col">Numero</th>
                                <th scope="col">Collega</th>
                                <th scope="col">Commessa</th>
                                <th scope="col" class="text-right">Unità</th>
                                <th scope="col" class="w-10"><span class="sr-only">Azioni</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->ultimeConsegne as $consegna)
                                <tr wire:key="consegna-{{ $consegna->id }}" @class(['text-slate-400' => $consegna->isAnnullata()])>
                                    <td class="tabular whitespace-nowrap text-slate-600">{{ $consegna->data_consegna->format('d/m/Y') }}
                                        <span class="text-slate-400">{{ $consegna->data_consegna->format('H:i') }}</span>
                                    </td>
                                    <td class="tabular font-medium whitespace-nowrap text-slate-900">
                                        {{ $consegna->numero }}
                                        @if ($consegna->isAnnullata())
                                            <x-ui.badge tone="danger" class="ml-1">Annullata</x-ui.badge>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap">{{ $consegna->collega->nominativo }}</td>
                                    <td class="w-full max-w-0 truncate text-slate-600" title="{{ $consegna->commessa->etichetta }}">
                                        @if ($consegna->commessa->codice)
                                            <span class="tabular text-slate-400">{{ $consegna->commessa->codice }}</span>
                                        @endif
                                        {{ $consegna->commessa->nome }}
                                    </td>
                                    <td class="tabular text-right font-medium">{{ $consegna->totale_unita }}</td>
                                    <td class="text-right">
                                        @if ($dettaglioDisponibile)
                                            <a href="{{ route('consegne.show', $consegna) }}" wire:navigate class="inline-grid size-7 place-items-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Dettaglio consegna {{ $consegna->numero }}">
                                                <x-lucide-eye class="size-4" />
                                            </a>
                                        @else
                                            <span class="inline-grid size-7 cursor-not-allowed place-items-center text-slate-300" title="Dettaglio consegna disponibile dalla Fase 3" aria-disabled="true">
                                                <x-lucide-eye class="size-4" aria-hidden="true" />
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-ui.panel>

        <div class="space-y-5">

            {{-- Azioni rapide --}}
            <x-ui.panel title="Azioni rapide" icon="zap" flush>
                <ul class="divide-y divide-line/70">
                    @foreach ($azioni as $azione)
                        @php $disponibile = Route::has($azione['route']); @endphp
                        <li>
                            @if ($disponibile)
                                <a href="{{ route($azione['route']) }}" wire:navigate class="group flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50">
                                    <span class="grid size-8 place-items-center rounded-md bg-primary-50 text-primary-700" aria-hidden="true">
                                        <x-dynamic-component :component="'lucide-'.$azione['icon']" class="size-4" />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-[13px] font-medium text-slate-900">{{ $azione['label'] }}</span>
                                        <span class="block truncate text-xs text-slate-500">{{ $azione['descrizione'] }}</span>
                                    </span>
                                    <x-lucide-chevron-right class="size-4 text-slate-300 group-hover:text-slate-500" aria-hidden="true" />
                                </a>
                            @else
                                <div class="flex cursor-not-allowed items-center gap-3 px-4 py-2.5" aria-disabled="true" title="Disponibile dalla Fase {{ $azione['fase'] }}">
                                    <span class="grid size-8 place-items-center rounded-md bg-slate-100 text-slate-400" aria-hidden="true">
                                        <x-dynamic-component :component="'lucide-'.$azione['icon']" class="size-4" />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-[13px] font-medium text-slate-500">{{ $azione['label'] }}</span>
                                        <span class="block truncate text-xs text-slate-400">{{ $azione['descrizione'] }}</span>
                                    </span>
                                    <x-ui.badge>Fase {{ $azione['fase'] }}</x-ui.badge>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </x-ui.panel>

            {{-- Scorte basse --}}
            <x-ui.panel title="Scorte da controllare" icon="triangle-alert" flush>
                <x-slot:actions>
                    @if ($this->sottoScorta['totale'] > 0)
                        <x-ui.badge tone="warning">{{ $this->sottoScorta['totale'] }}</x-ui.badge>
                    @endif
                </x-slot:actions>

                @if ($this->scorteBasse->isEmpty())
                    <x-ui.empty-state icon="circle-check" title="Scorte in ordine" description="Nessuna variante è sotto la scorta minima." />
                @else
                    <ul class="divide-y divide-line/70">
                        @foreach ($this->scorteBasse as $variante)
                            <li wire:key="scorta-{{ $variante->id }}" class="flex items-center gap-3 px-4 py-2">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[13px] font-medium text-slate-900">{{ $variante->indumento->nome }}</p>
                                    <p class="tabular truncate text-xs text-slate-500">
                                        {{ $variante->indumento->codice ?? '—' }} · Taglia <span class="font-medium text-slate-700">{{ $variante->taglia }}</span>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="tabular text-[13px] font-semibold {{ $variante->giacenza === 0 ? 'text-red-700' : 'text-amber-700' }}">
                                        {{ $variante->giacenza }}<span class="font-normal text-slate-400"> / {{ $variante->scorta_minima }}</span>
                                    </p>
                                    <x-ui.stock-status :stato="$variante->statoScorta()" />
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($this->sottoScorta['totale'] > $this::LIMITE_SCORTE)
                    <x-slot:footer>
                        Mostrate le {{ $this::LIMITE_SCORTE }} più critiche su {{ $this->sottoScorta['totale'] }}.
                    </x-slot:footer>
                @endif
            </x-ui.panel>
        </div>
    </div>
</div>
