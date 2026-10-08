@use('Illuminate\Support\Number')

@php $filtriAttivi = $cerca !== '' || $categoria !== '' || $stato !== 'attivi'; @endphp

<div class="space-y-4">

    <div class="panel flex flex-wrap items-end gap-3 px-4 py-3">
        <x-ui.field label="Cerca" for="cerca" class="w-72">
            <x-ui.input id="cerca" type="search" icon="search" wire:model.live.debounce.300ms="cerca" placeholder="Nome o codice" autocomplete="off" />
        </x-ui.field>
        <x-ui.field label="Categoria" for="categoria" class="w-56">
            <x-ui.select id="categoria" wire:model.live="categoria" placeholder="Tutte le categorie">
                @foreach ($this->categorie as $voce)
                    <option value="{{ $voce->id }}">{{ $voce->nome }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>
        <x-ui.field label="Stato" for="stato" class="w-40">
            <x-ui.select id="stato" wire:model.live="stato">
                <option value="attivi">Attivi</option>
                <option value="disattivati">Disattivati</option>
                <option value="tutti">Tutti</option>
            </x-ui.select>
        </x-ui.field>
        @if ($filtriAttivi)
            <x-ui.button variant="ghost" size="sm" icon="x" class="mb-0.5" wire:click="azzeraFiltri">Azzera filtri</x-ui.button>
        @endif
        <div class="ml-auto flex gap-2 pb-0.5">
            <x-ui.button variant="secondary" icon="tags" :href="route('catalogo.categorie')" wire:navigate>Categorie</x-ui.button>
            <x-ui.button icon="plus" :href="route('catalogo.create')" wire:navigate>Nuovo indumento</x-ui.button>
        </div>
    </div>

    <x-ui.panel flush>
        <div wire:loading.class="opacity-60" wire:target="cerca,categoria,stato,azzeraFiltri,gotoPage,nextPage,previousPage">
            @if ($this->indumenti->isEmpty())
                <x-ui.empty-state icon="shirt" title="Nessun indumento trovato"
                    :description="$filtriAttivi ? 'Nessun risultato con i filtri impostati.' : 'Aggiungi il primo indumento con le sue taglie.'">
                    @unless ($filtriAttivi)
                        <x-ui.button icon="plus" :href="route('catalogo.create')" wire:navigate>Nuovo indumento</x-ui.button>
                    @endunless
                </x-ui.empty-state>
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col" class="w-28">Codice</th>
                                <th scope="col">Indumento</th>
                                <th scope="col" class="w-48">Categoria</th>
                                <th scope="col">Taglie</th>
                                <th scope="col" class="w-24 text-right">Giacenza</th>
                                <th scope="col" class="w-28">Stato</th>
                                <th scope="col" class="w-24"><span class="sr-only">Azioni</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->indumenti as $indumento)
                                <tr wire:key="indumento-{{ $indumento->id }}" @class(['text-slate-400' => ! $indumento->attivo])>
                                    <td class="tabular text-slate-500">{{ $indumento->codice ?? '—' }}</td>
                                    <td @class(['font-medium', 'text-slate-900' => $indumento->attivo])>{{ $indumento->nome }}</td>
                                    <td class="text-slate-500">{{ $indumento->categoria->nome }}</td>
                                    <td>
                                        <div class="flex flex-wrap gap-1">
                                            @forelse ($indumento->varianti as $variante)
                                                <span @class([
                                                    'rounded px-1.5 py-px text-[11px] font-medium ring-1 ring-inset',
                                                    'bg-slate-50 text-slate-700 ring-line' => $variante->attiva,
                                                    'text-slate-400 line-through ring-line' => ! $variante->attiva,
                                                ]) @if (! $variante->attiva) title="Taglia disattivata" @endif>{{ $variante->taglia }}</span>
                                            @empty
                                                <span class="text-xs text-amber-700">Nessuna taglia</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="tabular text-right">{{ Number::format((int) $indumento->giacenza_totale) }}</td>
                                    <td><x-ui.badge :tone="$indumento->attivo ? 'success' : 'neutral'" dot>{{ $indumento->attivo ? 'Attivo' : 'Disattivato' }}</x-ui.badge></td>
                                    <td class="text-right">
                                        <x-ui.button variant="ghost" size="sm" icon="pencil" :href="route('catalogo.edit', $indumento)" wire:navigate aria-label="Modifica {{ $indumento->nome }}">Modifica</x-ui.button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{ $this->indumenti->links() }}
    </x-ui.panel>
</div>
