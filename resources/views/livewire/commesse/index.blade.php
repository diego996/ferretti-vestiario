@use('Illuminate\Support\Carbon')

@php $filtriAttivi = $cerca !== '' || $stato !== 'attive'; @endphp

<div class="space-y-4">

    <div class="panel flex flex-wrap items-end gap-3 px-4 py-3">
        <x-ui.field label="Cerca" for="cerca" class="w-72">
            <x-ui.input id="cerca" type="search" icon="search" wire:model.live.debounce.300ms="cerca" placeholder="Codice o nome" autocomplete="off" />
        </x-ui.field>
        <x-ui.field label="Stato" for="stato" class="w-44">
            <x-ui.select id="stato" wire:model.live="stato">
                <option value="attive">Attive</option>
                <option value="chiuse">Chiuse</option>
                <option value="tutte">Tutte</option>
            </x-ui.select>
        </x-ui.field>
        @if ($filtriAttivi)
            <x-ui.button variant="ghost" size="sm" icon="x" class="mb-0.5" wire:click="azzeraFiltri">Azzera filtri</x-ui.button>
        @endif
        <div class="ml-auto pb-0.5">
            <x-ui.button icon="plus" x-on:click="$dispatch('open-drawer', { id: 'commessa' })" wire:click="nuova">Nuova commessa</x-ui.button>
        </div>
    </div>

    <x-ui.panel flush>
        <div wire:loading.class="opacity-60" wire:target="cerca,stato,azzeraFiltri,gotoPage,nextPage,previousPage">
            @if ($this->commesse->isEmpty())
                <x-ui.empty-state icon="folder-kanban" title="Nessuna commessa trovata"
                    :description="$filtriAttivi ? 'Nessun risultato con i filtri impostati.' : 'Aggiungi le commesse a cui imputare le consegne.'" />
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col" class="w-36">Codice</th>
                                <th scope="col">Nome</th>
                                <th scope="col" class="w-28 text-right">Consegne</th>
                                <th scope="col" class="w-36">Ultima consegna</th>
                                <th scope="col" class="w-28">Stato</th>
                                <th scope="col" class="w-24"><span class="sr-only">Azioni</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->commesse as $commessa)
                                <tr wire:key="commessa-{{ $commessa->id }}" @class(['text-slate-400' => ! $commessa->attiva])>
                                    <td class="tabular text-slate-500">{{ $commessa->codice ?? '—' }}</td>
                                    <td class="max-w-0 w-full">
                                        <p @class(['truncate font-medium', 'text-slate-900' => $commessa->attiva])>{{ $commessa->nome }}</p>
                                        @if ($commessa->descrizione)
                                            <p class="truncate text-xs text-slate-500" title="{{ $commessa->descrizione }}">{{ $commessa->descrizione }}</p>
                                        @endif
                                    </td>
                                    <td class="tabular text-right">
                                        @if ($commessa->consegne_count > 0)
                                            <a href="{{ route('consegne.index', ['commessa' => $commessa->id]) }}" wire:navigate class="text-primary-700 hover:underline">{{ $commessa->consegne_count }}</a>
                                        @else
                                            0
                                        @endif
                                    </td>
                                    <td class="tabular text-slate-500">{{ $commessa->ultima_consegna ? Carbon::parse($commessa->ultima_consegna)->format('d/m/Y') : '—' }}</td>
                                    <td>
                                        <x-ui.badge :tone="$commessa->attiva ? 'success' : 'neutral'" dot>{{ $commessa->attiva ? 'Attiva' : 'Chiusa' }}</x-ui.badge>
                                    </td>
                                    <td class="text-right">
                                        <x-ui.button variant="ghost" size="sm" icon="pencil"
                                            x-on:click="$dispatch('open-drawer', { id: 'commessa' })"
                                            wire:click="modifica({{ $commessa->id }})"
                                            aria-label="Modifica {{ $commessa->etichetta }}">Modifica</x-ui.button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{ $this->commesse->links() }}
    </x-ui.panel>

    <x-ui.drawer id="commessa" :title="$modificaId ? 'Modifica commessa' : 'Nuova commessa'" width="max-w-md">
        <div wire:loading.flex wire:target="modifica" class="flex-col"><x-ui.skeleton :rows="4" /></div>

        <form wire:submit="salva" wire:loading.remove wire:target="modifica" class="space-y-4 p-5" novalidate>
            <x-ui.field label="Codice" for="codice" hint="Facoltativo, ma univoco se indicato.">
                <x-ui.input id="codice" wire:model="codice" maxlength="50" autocomplete="off" />
            </x-ui.field>
            <x-ui.field label="Nome" for="nome" required>
                <x-ui.input id="nome" wire:model="nome" maxlength="150" autocomplete="off" />
            </x-ui.field>
            <x-ui.field label="Descrizione" for="descrizione">
                <x-ui.textarea id="descrizione" wire:model="descrizione" maxlength="1000" />
            </x-ui.field>
            <div>
                <x-ui.checkbox wire:model="attiva">Attiva</x-ui.checkbox>
                <p class="mt-1 text-xs text-slate-500">Una commessa chiusa resta nello storico ma non è selezionabile per nuove consegne.</p>
            </div>

            <div class="flex justify-end gap-2 border-t border-line pt-4">
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-drawer', { id: 'commessa' })">Annulla</x-ui.button>
                <x-ui.button type="submit" icon="check" wire:loading.attr="disabled" wire:target="salva">Salva</x-ui.button>
            </div>
        </form>
    </x-ui.drawer>
</div>
