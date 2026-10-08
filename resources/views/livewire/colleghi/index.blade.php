@use('Illuminate\Support\Carbon')

@php $filtriAttivi = $cerca !== '' || $stato !== 'attivi'; @endphp

<div class="space-y-4">

    <div class="panel flex flex-wrap items-end gap-3 px-4 py-3">
        <x-ui.field label="Cerca" for="cerca" class="w-72">
            <x-ui.input id="cerca" type="search" icon="search" wire:model.live.debounce.300ms="cerca" placeholder="Cognome, nome o matricola" autocomplete="off" />
        </x-ui.field>
        <x-ui.field label="Stato" for="stato" class="w-44">
            <x-ui.select id="stato" wire:model.live="stato">
                <option value="attivi">Attivi</option>
                <option value="disattivati">Disattivati</option>
                <option value="tutti">Tutti</option>
            </x-ui.select>
        </x-ui.field>
        @if ($filtriAttivi)
            <x-ui.button variant="ghost" size="sm" icon="x" class="mb-0.5" wire:click="azzeraFiltri">Azzera filtri</x-ui.button>
        @endif
        <div class="ml-auto pb-0.5">
            <x-ui.button icon="user-plus" x-on:click="$dispatch('open-drawer', { id: 'collega' })" wire:click="nuovo">Nuovo collega</x-ui.button>
        </div>
    </div>

    <x-ui.panel flush>
        <div wire:loading.class="opacity-60" wire:target="cerca,stato,azzeraFiltri,gotoPage,nextPage,previousPage">
            @if ($this->colleghi->isEmpty())
                <x-ui.empty-state icon="users" title="Nessun collega trovato"
                    :description="$filtriAttivi ? 'Nessun risultato con i filtri impostati.' : 'Aggiungi i colleghi a cui consegnare il vestiario.'" />
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col">Cognome e nome</th>
                                <th scope="col" class="w-40">Matricola</th>
                                <th scope="col" class="w-28 text-right">Consegne</th>
                                <th scope="col" class="w-36">Ultima consegna</th>
                                <th scope="col" class="w-28">Stato</th>
                                <th scope="col" class="w-24"><span class="sr-only">Azioni</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->colleghi as $collega)
                                <tr wire:key="collega-{{ $collega->id }}" @class(['text-slate-400' => ! $collega->attivo])>
                                    <td @class(['font-medium', 'text-slate-900' => $collega->attivo])>{{ $collega->nominativo }}</td>
                                    <td class="tabular text-slate-500">{{ $collega->matricola ?? '—' }}</td>
                                    <td class="tabular text-right">
                                        @if ($collega->consegne_count > 0)
                                            <a href="{{ route('consegne.index', ['collega' => $collega->id]) }}" wire:navigate class="text-primary-700 hover:underline">{{ $collega->consegne_count }}</a>
                                        @else
                                            0
                                        @endif
                                    </td>
                                    <td class="tabular text-slate-500">{{ $collega->ultima_consegna ? Carbon::parse($collega->ultima_consegna)->format('d/m/Y') : '—' }}</td>
                                    <td>
                                        <x-ui.badge :tone="$collega->attivo ? 'success' : 'neutral'" dot>{{ $collega->attivo ? 'Attivo' : 'Disattivato' }}</x-ui.badge>
                                    </td>
                                    <td class="text-right">
                                        <x-ui.button variant="ghost" size="sm" icon="pencil"
                                            x-on:click="$dispatch('open-drawer', { id: 'collega' })"
                                            wire:click="modifica({{ $collega->id }})"
                                            aria-label="Modifica {{ $collega->nominativo }}">Modifica</x-ui.button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{ $this->colleghi->links() }}
    </x-ui.panel>

    <x-ui.drawer id="collega" :title="$modificaId ? 'Modifica collega' : 'Nuovo collega'" width="max-w-md">
        <div wire:loading.flex wire:target="modifica" class="flex-col"><x-ui.skeleton :rows="4" /></div>

        <form wire:submit="salva" wire:loading.remove wire:target="modifica" class="space-y-4 p-5" novalidate>
            <x-ui.field label="Cognome" for="cognome" required>
                <x-ui.input id="cognome" wire:model="cognome" maxlength="100" autocomplete="off" />
            </x-ui.field>
            <x-ui.field label="Nome" for="nome" required>
                <x-ui.input id="nome" wire:model="nome" maxlength="100" autocomplete="off" />
            </x-ui.field>
            <x-ui.field label="Matricola" for="matricola" hint="Facoltativa, ma univoca se indicata.">
                <x-ui.input id="matricola" wire:model="matricola" maxlength="50" autocomplete="off" />
            </x-ui.field>
            <div>
                <x-ui.checkbox wire:model="attivo">Attivo</x-ui.checkbox>
                <p class="mt-1 text-xs text-slate-500">Un collega disattivato resta nello storico ma non può ricevere nuove consegne.</p>
            </div>

            <div class="flex justify-end gap-2 border-t border-line pt-4">
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-drawer', { id: 'collega' })">Annulla</x-ui.button>
                <x-ui.button type="submit" icon="check" wire:loading.attr="disabled" wire:target="salva">Salva</x-ui.button>
            </div>
        </form>
    </x-ui.drawer>
</div>
