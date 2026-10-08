@use('Illuminate\Support\Number')

<form wire:submit="salva" class="space-y-5" novalidate>
    <div class="grid gap-5 xl:grid-cols-3 xl:items-start">

        <x-ui.panel title="Dati indumento" icon="shirt">
            <div class="space-y-4">
                <x-ui.field label="Categoria" for="categoria_id" required>
                    <x-ui.select id="categoria_id" wire:model="categoria_id" placeholder="Scegli categoria…">
                        @foreach ($this->categorie as $voce)
                            <option value="{{ $voce->id }}">{{ $voce->nome }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>
                <x-ui.field label="Codice" for="codice" hint="Facoltativo, ma univoco se indicato.">
                    <x-ui.input id="codice" wire:model="codice" maxlength="50" autocomplete="off" />
                </x-ui.field>
                <x-ui.field label="Nome" for="nome" required>
                    <x-ui.input id="nome" wire:model="nome" maxlength="150" autocomplete="off" />
                </x-ui.field>
                <x-ui.field label="Descrizione" for="descrizione">
                    <x-ui.textarea id="descrizione" wire:model="descrizione" maxlength="2000" />
                </x-ui.field>
                <div>
                    <x-ui.checkbox wire:model="attivo">Attivo</x-ui.checkbox>
                    <p class="mt-1 text-xs text-slate-500">Un indumento disattivato resta nello storico ma non si può caricare né consegnare.</p>
                </div>
            </div>
        </x-ui.panel>

        <x-ui.panel title="Taglie" icon="ruler" description="L'ordine delle righe è l'ordine di visualizzazione" flush class="xl:col-span-2">
            <x-slot:actions>
                <span class="text-xs text-slate-500">Aggiungi:</span>
                @foreach ($this::PRESET as $chiave => $preset)
                    <x-ui.button variant="secondary" size="sm" wire:click="aggiungiPreset('{{ $chiave }}')">{{ $preset['etichetta'] }}</x-ui.button>
                @endforeach
            </x-slot:actions>

            @error('varianti')
                <div class="flex items-center gap-2 border-b border-red-200 bg-red-50 px-4 py-2 text-[13px] text-red-800" role="alert">
                    <x-lucide-circle-alert class="size-4 shrink-0" aria-hidden="true" /> {{ $message }}
                </div>
            @enderror

            @if ($varianti === [])
                <x-ui.empty-state icon="ruler" title="Nessuna taglia" description="Aggiungi le taglie singolarmente o con uno dei gruppi qui sopra." />
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col" class="w-16"><span class="sr-only">Ordine</span></th>
                                <th scope="col">Taglia</th>
                                <th scope="col" class="w-32">Scorta minima</th>
                                <th scope="col" class="w-24 text-right">Giacenza</th>
                                <th scope="col" class="w-24">Attiva</th>
                                <th scope="col" class="w-10"><span class="sr-only">Azioni</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($varianti as $i => $v)
                                <tr wire:key="taglia-{{ $v['id'] ?? 'n'.$i }}" class="align-top">
                                    <td class="pt-2.5">
                                        <div class="flex">
                                            <button type="button" wire:click="sposta({{ $i }}, -1)" @disabled($loop->first) class="grid size-6 place-items-center rounded text-slate-400 hover:bg-slate-100 hover:text-slate-700 disabled:opacity-30" aria-label="Sposta su {{ $v['taglia'] }}">
                                                <x-lucide-arrow-up class="size-3.5" aria-hidden="true" />
                                            </button>
                                            <button type="button" wire:click="sposta({{ $i }}, 1)" @disabled($loop->last) class="grid size-6 place-items-center rounded text-slate-400 hover:bg-slate-100 hover:text-slate-700 disabled:opacity-30" aria-label="Sposta giù {{ $v['taglia'] }}">
                                                <x-lucide-arrow-down class="size-3.5" aria-hidden="true" />
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <x-ui.input wire:model="varianti.{{ $i }}.taglia" maxlength="20" class="w-40" aria-label="Taglia riga {{ $i + 1 }}" placeholder="Es. M, 42, Unica" />
                                        @error('varianti.'.$i.'.taglia') <p class="mt-1 text-xs text-red-700" role="alert">{{ $message }}</p> @enderror
                                    </td>
                                    <td>
                                        <x-ui.input type="number" min="0" step="1" class="tabular text-right" wire:model="varianti.{{ $i }}.scorta_minima" aria-label="Scorta minima riga {{ $i + 1 }}" />
                                        @error('varianti.'.$i.'.scorta_minima') <p class="mt-1 text-xs text-red-700" role="alert">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="tabular pt-3.5 text-right text-slate-600">{{ $v['id'] ? Number::format($v['giacenza']) : '—' }}</td>
                                    <td class="pt-3">
                                        <input type="checkbox" wire:model="varianti.{{ $i }}.attiva" class="size-4 rounded border-line-strong accent-primary-600" aria-label="Taglia {{ $v['taglia'] }} attiva">
                                    </td>
                                    <td class="pt-2.5 text-right">
                                        @if ($v['movimentata'])
                                            <span class="grid size-7 cursor-not-allowed place-items-center text-slate-300" title="Ha movimenti: non eliminabile, puoi disattivarla" aria-disabled="true">
                                                <x-lucide-lock class="size-4" aria-hidden="true" />
                                            </span>
                                        @else
                                            <button type="button" wire:click="rimuovi({{ $i }})" class="grid size-7 place-items-center rounded-md text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="Elimina taglia {{ $v['taglia'] }}">
                                                <x-lucide-trash-2 class="size-4" aria-hidden="true" />
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="border-t border-line px-4 py-2">
                <x-ui.button variant="ghost" size="sm" icon="plus" wire:click="aggiungiTaglia">Aggiungi taglia</x-ui.button>
            </div>

            <x-slot:footer>Le nuove taglie partono da giacenza zero: la merce si aggiunge con un carico.</x-slot:footer>
        </x-ui.panel>
    </div>

    <div class="flex justify-end gap-2">
        <x-ui.button variant="secondary" :href="route('catalogo.index')" wire:navigate>Annulla</x-ui.button>
        <x-ui.button type="submit" icon="check" wire:loading.attr="disabled" wire:target="salva">Salva indumento</x-ui.button>
    </div>
</form>
