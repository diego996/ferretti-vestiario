<div class="space-y-4">

    <x-ui.panel title="Categorie" icon="tags" description="Raggruppano gli indumenti nel catalogo e negli elenchi di scelta" flush>
        <x-slot:actions>
            <x-ui.button icon="plus" x-on:click="$dispatch('open-drawer', { id: 'categoria' })" wire:click="nuova">Nuova categoria</x-ui.button>
        </x-slot:actions>

        @if ($this->categorie->isEmpty())
            <x-ui.empty-state icon="tags" title="Nessuna categoria" description="Crea almeno una categoria prima di aggiungere indumenti." />
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col" class="w-20 text-right">Ordine</th>
                            <th scope="col">Nome</th>
                            <th scope="col" class="w-28 text-right">Indumenti</th>
                            <th scope="col" class="w-28">Stato</th>
                            <th scope="col" class="w-24"><span class="sr-only">Azioni</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->categorie as $categoria)
                            <tr wire:key="categoria-{{ $categoria->id }}" @class(['text-slate-400' => ! $categoria->attiva])>
                                <td class="tabular text-right text-slate-500">{{ $categoria->ordinamento }}</td>
                                <td>
                                    <p @class(['font-medium', 'text-slate-900' => $categoria->attiva])>{{ $categoria->nome }}</p>
                                    @if ($categoria->descrizione)
                                        <p class="text-xs text-slate-500">{{ $categoria->descrizione }}</p>
                                    @endif
                                </td>
                                <td class="tabular text-right">
                                    @if ($categoria->indumenti_count > 0)
                                        <a href="{{ route('catalogo.index', ['categoria' => $categoria->id]) }}" wire:navigate class="text-primary-700 hover:underline">{{ $categoria->indumenti_count }}</a>
                                    @else
                                        0
                                    @endif
                                </td>
                                <td><x-ui.badge :tone="$categoria->attiva ? 'success' : 'neutral'" dot>{{ $categoria->attiva ? 'Attiva' : 'Disattivata' }}</x-ui.badge></td>
                                <td class="text-right">
                                    <x-ui.button variant="ghost" size="sm" icon="pencil"
                                        x-on:click="$dispatch('open-drawer', { id: 'categoria' })"
                                        wire:click="modifica({{ $categoria->id }})"
                                        aria-label="Modifica {{ $categoria->nome }}">Modifica</x-ui.button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.panel>

    <x-ui.drawer id="categoria" :title="$modificaId ? 'Modifica categoria' : 'Nuova categoria'" width="max-w-md">
        <div wire:loading.flex wire:target="modifica" class="flex-col"><x-ui.skeleton :rows="4" /></div>

        <form wire:submit="salva" wire:loading.remove wire:target="modifica" class="space-y-4 p-5" novalidate>
            <x-ui.field label="Nome" for="nome" required>
                <x-ui.input id="nome" wire:model="nome" maxlength="100" autocomplete="off" />
            </x-ui.field>
            <x-ui.field label="Descrizione" for="descrizione">
                <x-ui.input id="descrizione" wire:model="descrizione" maxlength="255" autocomplete="off" />
            </x-ui.field>
            <x-ui.field label="Ordine di visualizzazione" for="ordinamento" required hint="Numero più basso = mostrata prima.">
                <x-ui.input id="ordinamento" type="number" min="0" max="65535" step="1" class="tabular w-32" wire:model="ordinamento" />
            </x-ui.field>
            <div>
                <x-ui.checkbox wire:model="attiva">Attiva</x-ui.checkbox>
                <p class="mt-1 text-xs text-slate-500">Una categoria disattivata non è proposta per i nuovi indumenti.</p>
            </div>

            <div class="flex justify-end gap-2 border-t border-line pt-4">
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-drawer', { id: 'categoria' })">Annulla</x-ui.button>
                <x-ui.button type="submit" icon="check" wire:loading.attr="disabled" wire:target="salva">Salva</x-ui.button>
            </div>
        </form>
    </x-ui.drawer>
</div>
