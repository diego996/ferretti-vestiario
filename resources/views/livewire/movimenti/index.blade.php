@use('App\Enums\TipoMovimento')
@use('Illuminate\Support\Number')

@php
    $filtriAttivi = $cerca !== '' || $tipo !== '' || $dal !== '' || $al !== '' || $operatore !== '' || $variante !== '' || $carico !== '' || $consegna !== '';
@endphp

<div class="space-y-4">

    <div class="panel flex flex-wrap items-end gap-3 px-4 py-3">
        <x-ui.field label="Indumento" for="cerca" class="w-64">
            <x-ui.input id="cerca" type="search" icon="search" wire:model.live.debounce.300ms="cerca" placeholder="Nome o codice" autocomplete="off" />
        </x-ui.field>
        <x-ui.field label="Tipo" for="tipo" class="w-52">
            <x-ui.select id="tipo" wire:model.live="tipo" placeholder="Tutti i tipi">
                @foreach (TipoMovimento::cases() as $voce)
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
        <x-ui.field label="Operatore" for="operatore" class="w-48">
            <x-ui.select id="operatore" wire:model.live="operatore" placeholder="Tutti">
                @foreach ($this->operatori as $utente)
                    <option value="{{ $utente->id }}">{{ $utente->name }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>
        @if ($filtriAttivi)
            <x-ui.button variant="ghost" size="sm" icon="x" class="mb-0.5" wire:click="azzeraFiltri">Azzera filtri</x-ui.button>
        @endif
    </div>

    @if ($this->contesto)
        <div class="flex items-center gap-2 rounded-panel border border-primary-200 bg-primary-50 px-4 py-2 text-[13px] text-primary-900">
            <x-lucide-filter class="size-4 shrink-0" aria-hidden="true" />
            <span>Movimenti di: <span class="font-semibold">{{ $this->contesto }}</span></span>
            <button type="button" wire:click="rimuoviContesto" class="ml-auto inline-flex items-center gap-1 text-xs font-medium text-primary-700 hover:text-primary-900">
                <x-lucide-x class="size-3.5" aria-hidden="true" /> Rimuovi
            </button>
        </div>
    @endif

    <x-ui.panel flush>
        <div wire:loading.class="opacity-60" wire:target="cerca,tipo,dal,al,operatore,variante,carico,consegna,azzeraFiltri,rimuoviContesto,gotoPage,nextPage,previousPage">
            @if ($this->movimenti->isEmpty())
                <x-ui.empty-state icon="history" title="Nessun movimento"
                    :description="$filtriAttivi ? 'Nessun movimento con i filtri impostati.' : 'I movimenti compariranno qui dopo il primo carico.'" />
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col" class="w-32">Data</th>
                                <th scope="col" class="w-44">Tipo</th>
                                <th scope="col">Indumento</th>
                                <th scope="col" class="w-20">Taglia</th>
                                <th scope="col" class="w-20 text-right">Qtà</th>
                                <th scope="col" class="w-20 text-right">Saldo</th>
                                <th scope="col">Origine / note</th>
                                <th scope="col" class="w-40">Operatore</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->movimenti as $m)
                                <tr wire:key="mov-{{ $m->id }}">
                                    <td class="tabular whitespace-nowrap text-slate-600">
                                        {{ $m->data_movimento->format('d/m/Y') }} <span class="text-slate-400">{{ $m->data_movimento->format('H:i') }}</span>
                                    </td>
                                    <td><x-ui.badge :tone="$m->tipo->tone()">{{ $m->tipo->label() }}</x-ui.badge></td>
                                    <td class="whitespace-nowrap">
                                        <span class="font-medium text-slate-900">{{ $m->variante->indumento->nome }}</span>
                                        @if ($m->variante->indumento->codice)
                                            <span class="tabular text-xs text-slate-400">{{ $m->variante->indumento->codice }}</span>
                                        @endif
                                    </td>
                                    <td class="font-medium">{{ $m->variante->taglia }}</td>
                                    <td class="tabular text-right font-semibold {{ $m->quantita > 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $m->quantita > 0 ? '+' : '−' }}{{ Number::format(abs($m->quantita)) }}</td>
                                    <td class="tabular text-right text-slate-600">{{ Number::format($m->giacenza_risultante) }}</td>
                                    <td class="max-w-72">
                                        <p class="truncate text-slate-600" title="{{ $m->note }}">
                                            {{ $m->descrizioneOrigine() }}@if ($m->descrizioneOrigine() && $m->note) · @endif<span class="italic">{{ $m->note }}</span>
                                        </p>
                                    </td>
                                    <td class="truncate whitespace-nowrap text-slate-600">{{ $m->operatore->name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{ $this->movimenti->links() }}
    </x-ui.panel>
</div>
