@use('App\Enums\StatoConsegna')
@use('Illuminate\Support\Number')

@php
    $filtriAttivi = $cerca !== '' || $commessa !== '' || $stato !== '' || $dal !== '' || $al !== '' || $collega !== '';
@endphp

<div class="space-y-4">

    <div class="panel flex flex-wrap items-end gap-3 px-4 py-3">
        <x-ui.field label="Cerca" for="cerca" class="w-64">
            <x-ui.input id="cerca" type="search" icon="search" wire:model.live.debounce.300ms="cerca" placeholder="Numero o collega" autocomplete="off" />
        </x-ui.field>
        <x-ui.field label="Commessa" for="commessa" class="w-64">
            <x-ui.select id="commessa" wire:model.live="commessa" placeholder="Tutte le commesse">
                @foreach ($this->commesse as $voce)
                    <option value="{{ $voce->id }}">{{ $voce->etichetta }}{{ $voce->attiva ? '' : ' (chiusa)' }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>
        <x-ui.field label="Stato" for="stato" class="w-40">
            <x-ui.select id="stato" wire:model.live="stato" placeholder="Tutti">
                @foreach (StatoConsegna::cases() as $voce)
                    <option value="{{ $voce->value }}">{{ $voce->label() }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>
        <x-ui.field label="Dal" for="dal" class="w-40">
            <x-ui.input id="dal" type="date" wire:model.live="dal" :max="$al ?: null" />
        </x-ui.field>
        <x-ui.field label="Al" for="al" class="w-40">
            <x-ui.input id="al" type="date" wire:model.live="al" :min="$dal ?: null" />
        </x-ui.field>
        @if ($filtriAttivi)
            <x-ui.button variant="ghost" size="sm" icon="x" class="mb-0.5" wire:click="azzeraFiltri">Azzera filtri</x-ui.button>
        @endif
    </div>

    @if ($collega !== '')
        <div class="flex items-center gap-2 rounded-panel border border-primary-200 bg-primary-50 px-4 py-2 text-[13px] text-primary-900">
            <x-lucide-filter class="size-4 shrink-0" aria-hidden="true" />
            <span>Consegne a: <span class="font-semibold">{{ $this->collegaFiltrato?->nominativo ?? 'collega inesistente' }}</span></span>
            <button type="button" wire:click="$set('collega', '')" class="ml-auto inline-flex items-center gap-1 text-xs font-medium text-primary-700 hover:text-primary-900">
                <x-lucide-x class="size-3.5" aria-hidden="true" /> Rimuovi
            </button>
        </div>
    @endif

    <x-ui.panel flush>
        <div wire:loading.class="opacity-60" wire:target="cerca,commessa,stato,dal,al,collega,azzeraFiltri,gotoPage,nextPage,previousPage">
            @if ($this->consegne->isEmpty())
                <x-ui.empty-state icon="clipboard-list" title="Nessuna consegna"
                    :description="$filtriAttivi ? 'Nessuna consegna con i filtri impostati.' : 'Le consegne compariranno qui appena registrate.'">
                    @unless ($filtriAttivi)
                        <x-ui.button icon="plus" :href="route('consegne.create')" wire:navigate>Nuova consegna</x-ui.button>
                    @endunless
                </x-ui.empty-state>
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col" class="w-28">Numero</th>
                                <th scope="col" class="w-36">Data</th>
                                <th scope="col">Collega</th>
                                <th scope="col">Commessa</th>
                                <th scope="col" class="w-20 text-right">Capi</th>
                                <th scope="col" class="w-28">Stato</th>
                                <th scope="col" class="w-36">Operatore</th>
                                <th scope="col" class="w-10"><span class="sr-only">Azioni</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->consegne as $consegna)
                                <tr wire:key="consegna-{{ $consegna->id }}" @class(['text-slate-400' => $consegna->isAnnullata()])>
                                    <td class="tabular font-medium whitespace-nowrap">
                                        <a href="{{ route('consegne.show', $consegna) }}" wire:navigate @class(['hover:underline', 'text-primary-700' => ! $consegna->isAnnullata(), 'line-through' => $consegna->isAnnullata()])>{{ $consegna->numero }}</a>
                                    </td>
                                    <td class="tabular whitespace-nowrap">{{ $consegna->data_consegna->format('d/m/Y') }} <span class="text-slate-400">{{ $consegna->data_consegna->format('H:i') }}</span></td>
                                    <td class="whitespace-nowrap {{ $consegna->isAnnullata() ? '' : 'text-slate-900' }}">{{ $consegna->collega->nominativo }}</td>
                                    <td class="max-w-72 truncate" title="{{ $consegna->commessa->etichetta }}">
                                        @if ($consegna->commessa->codice)<span class="tabular text-slate-400">{{ $consegna->commessa->codice }}</span>@endif
                                        {{ $consegna->commessa->nome }}
                                    </td>
                                    <td class="tabular text-right font-medium">{{ Number::format((int) $consegna->totale_unita) }}</td>
                                    <td><x-ui.badge :tone="$consegna->stato->tone()" dot>{{ $consegna->stato->label() }}</x-ui.badge></td>
                                    <td class="truncate whitespace-nowrap">{{ $consegna->operatore->name }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('consegne.show', $consegna) }}" wire:navigate class="inline-grid size-7 place-items-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Dettaglio consegna {{ $consegna->numero }}">
                                            <x-lucide-eye class="size-4" />
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{ $this->consegne->links() }}
    </x-ui.panel>
</div>
