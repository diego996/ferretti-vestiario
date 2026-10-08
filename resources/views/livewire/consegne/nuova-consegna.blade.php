@use('Illuminate\Support\Number')

@php
    $richieste = $this->richiestePerVariante;
    $unitaTotali = array_sum(array_map(fn ($r) => max(0, (int) $r['quantita']), $righe));
    $classeRisultati = 'absolute inset-x-0 top-full z-20 mt-1 max-h-72 overflow-y-auto rounded-panel border border-line bg-white py-1 shadow-pop';
    $classeVoce = 'flex w-full items-center gap-2 px-3 py-1.5 text-left text-[13px] hover:bg-primary-50 focus:bg-primary-50 focus:outline-none';
@endphp

<div class="grid gap-5 xl:grid-cols-3 xl:items-start">

    {{-- DATI CONSEGNA --}}
    <x-ui.panel title="Destinatario" icon="user-round" class="xl:sticky xl:top-0">
        <div class="space-y-4">

            {{-- Collega --}}
            <div>
                <p class="mb-1 text-xs font-medium text-slate-700">Collega <span class="text-red-600" aria-hidden="true">*</span></p>
                @if ($this->collega)
                    <div class="flex items-center gap-3 rounded-control border border-primary-200 bg-primary-50/60 px-3 py-2">
                        <span class="grid size-8 shrink-0 place-items-center rounded-full bg-primary-100 text-[11px] font-semibold text-primary-800" aria-hidden="true">
                            {{ Str::substr($this->collega->cognome, 0, 1) }}{{ Str::substr($this->collega->nome, 0, 1) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-semibold text-slate-900">{{ $this->collega->nominativo }}</p>
                            <p class="tabular text-xs text-slate-500">{{ $this->collega->matricola ? 'Matricola '.$this->collega->matricola : 'Senza matricola' }}</p>
                        </div>
                        <x-ui.button variant="ghost" size="sm" wire:click="cambiaCollega">Cambia</x-ui.button>
                    </div>
                @else
                    <div class="relative" x-data="{ aperto: false }" x-on:click.outside="aperto = false">
                        <x-ui.input id="cercaCollega" type="search" icon="search" autocomplete="off" placeholder="Cognome, nome o matricola…"
                            wire:model.live.debounce.250ms="cercaCollega" wire:keydown.enter.prevent="scegliPrimoCollega"
                            x-on:focus="aperto = true" x-on:input="aperto = true" x-on:keydown.escape="aperto = false"
                            aria-label="Cerca collega" :invalid="$errors->has('collegaId')" />
                        @if (trim($cercaCollega) !== '')
                            <ul x-show="aperto" class="{{ $classeRisultati }}" role="listbox" aria-label="Colleghi trovati">
                                @forelse ($this->risultatiColleghi as $voce)
                                    <li wire:key="cc-{{ $voce->id }}">
                                        <button type="button" class="{{ $classeVoce }}" wire:click="scegliCollega({{ $voce->id }})" role="option">
                                            <span class="flex-1 truncate font-medium text-slate-900">{{ $voce->nominativo }}</span>
                                            <span class="tabular text-xs text-slate-400">{{ $voce->matricola }}</span>
                                        </button>
                                    </li>
                                @empty
                                    <li class="px-3 py-2 text-xs text-slate-500">Nessun collega attivo trovato.</li>
                                @endforelse
                            </ul>
                        @endif
                    </div>
                @endif
                @error('collegaId') <p class="mt-1 text-xs text-red-700" role="alert">{{ $message }}</p> @enderror
            </div>

            {{-- Commessa --}}
            <div>
                <p class="mb-1 text-xs font-medium text-slate-700">Commessa <span class="text-red-600" aria-hidden="true">*</span></p>
                @if ($this->commessa)
                    <div class="flex items-center gap-3 rounded-control border border-primary-200 bg-primary-50/60 px-3 py-2">
                        <x-lucide-folder-kanban class="size-4 shrink-0 text-primary-700" aria-hidden="true" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-semibold text-slate-900">{{ $this->commessa->nome }}</p>
                            @if ($this->commessa->codice)
                                <p class="tabular text-xs text-slate-500">{{ $this->commessa->codice }}</p>
                            @endif
                        </div>
                        <x-ui.button variant="ghost" size="sm" wire:click="cambiaCommessa">Cambia</x-ui.button>
                    </div>
                @else
                    <div class="relative" x-data="{ aperto: false }" x-on:click.outside="aperto = false">
                        <x-ui.input id="cercaCommessa" type="search" icon="search" autocomplete="off" placeholder="Codice o nome commessa…"
                            wire:model.live.debounce.250ms="cercaCommessa" wire:keydown.enter.prevent="scegliPrimaCommessa"
                            x-on:focus="aperto = true" x-on:input="aperto = true" x-on:keydown.escape="aperto = false"
                            aria-label="Cerca commessa" :invalid="$errors->has('commessaId')" />
                        @if (trim($cercaCommessa) !== '')
                            <ul x-show="aperto" class="{{ $classeRisultati }}" role="listbox" aria-label="Commesse trovate">
                                @forelse ($this->risultatiCommesse as $voce)
                                    <li wire:key="cm-{{ $voce->id }}">
                                        <button type="button" class="{{ $classeVoce }}" wire:click="scegliCommessa({{ $voce->id }})" role="option">
                                            <span class="tabular w-20 shrink-0 text-xs text-slate-400">{{ $voce->codice ?? '—' }}</span>
                                            <span class="flex-1 truncate font-medium text-slate-900">{{ $voce->nome }}</span>
                                        </button>
                                    </li>
                                @empty
                                    <li class="px-3 py-2 text-xs text-slate-500">Nessuna commessa attiva trovata.</li>
                                @endforelse
                            </ul>
                        @endif
                    </div>
                @endif
                @error('commessaId') <p class="mt-1 text-xs text-red-700" role="alert">{{ $message }}</p> @enderror
            </div>

            <x-ui.field label="Data e ora della consegna" for="dataConsegna" required hint="Di default è adesso.">
                <x-ui.input id="dataConsegna" type="datetime-local" wire:model="dataConsegna" max="{{ now()->format('Y-m-d\TH:i') }}" />
            </x-ui.field>

            <x-ui.field label="Note" for="note">
                <x-ui.textarea id="note" rows="2" wire:model="note" maxlength="1000" placeholder="Facoltative" />
            </x-ui.field>
        </div>
    </x-ui.panel>

    {{-- ARTICOLI --}}
    <x-ui.panel title="Articoli consegnati" icon="shirt" flush class="xl:col-span-2">

        <div class="border-b border-line p-4">
            <div class="relative" x-data="{ aperto: false }" x-on:click.outside="aperto = false">
                <x-ui.input id="cercaArticolo" type="search" icon="plus" autocomplete="off" placeholder="Aggiungi articolo: cerca per nome o codice e premi Invio…"
                    wire:model.live.debounce.250ms="cercaArticolo" wire:keydown.enter.prevent="aggiungiPrimoArticolo"
                    x-on:focus="aperto = true" x-on:input="aperto = true" x-on:keydown.escape="aperto = false"
                    aria-label="Cerca articolo da aggiungere" class="h-9" />
                @if (trim($cercaArticolo) !== '')
                    <ul x-show="aperto" class="{{ $classeRisultati }}" role="listbox" aria-label="Articoli trovati">
                        @forelse ($this->risultatiArticoli as $voce)
                            <li wire:key="ca-{{ $voce->id }}">
                                <button type="button" class="{{ $classeVoce }}" wire:click="aggiungiArticolo({{ $voce->id }})" role="option">
                                    <span class="tabular w-20 shrink-0 text-xs text-slate-400">{{ $voce->codice ?? '—' }}</span>
                                    <span class="flex-1 truncate font-medium text-slate-900">{{ $voce->nome }}</span>
                                    <span class="truncate text-xs text-slate-500">{{ $voce->varianti->pluck('taglia')->implode(' · ') }}</span>
                                    <span @class(['tabular w-24 shrink-0 text-right text-xs', 'text-red-700' => (int) $voce->disponibili === 0, 'text-slate-500' => (int) $voce->disponibili > 0])>
                                        {{ (int) $voce->disponibili === 0 ? 'Esaurito' : Number::format((int) $voce->disponibili).' disp.' }}
                                    </span>
                                </button>
                            </li>
                        @empty
                            <li class="px-3 py-2 text-xs text-slate-500">Nessun articolo attivo trovato.</li>
                        @endforelse
                    </ul>
                @endif
            </div>
        </div>

        @error('righe')
            <div class="flex items-start gap-2 border-b border-red-200 bg-red-50 px-4 py-2 text-[13px] text-red-800" role="alert">
                <x-lucide-circle-alert class="mt-0.5 size-4 shrink-0" aria-hidden="true" /> {{ $message }}
            </div>
        @enderror

        @if ($righe === [])
            <x-ui.empty-state icon="shirt" title="Nessun articolo" description="Cerca un articolo qui sopra per aggiungerlo alla consegna." />
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col">Articolo</th>
                            <th scope="col" class="w-48">Taglia</th>
                            <th scope="col" class="w-24 text-right">Disponibili</th>
                            <th scope="col" class="w-28">Quantità</th>
                            <th scope="col" class="w-10"><span class="sr-only">Azioni</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($righe as $i => $riga)
                            @php
                                $articolo = $this->articoli->get($riga['indumento_id']);
                                $variante = $articolo?->varianti->firstWhere('id', (int) $riga['variante_id']);
                                $eccede = $variante && ($richieste[$variante->id] ?? 0) > $variante->giacenza;
                            @endphp
                            <tr wire:key="riga-{{ $i }}-{{ $riga['indumento_id'] }}" class="align-top">
                                <td class="pt-3">
                                    @if ($articolo)
                                        <p class="font-medium text-slate-900">{{ $articolo->nome }}</p>
                                        <p class="tabular text-xs text-slate-400">{{ $articolo->codice ?? '—' }}</p>
                                    @else
                                        <p class="text-red-700">Articolo non più disponibile</p>
                                    @endif
                                </td>
                                <td>
                                    <x-ui.select wire:model.live="righe.{{ $i }}.variante_id" placeholder="Taglia…" aria-label="Taglia riga {{ $i + 1 }}">
                                        @foreach ($articolo?->varianti ?? [] as $taglia)
                                            <option value="{{ $taglia->id }}" @disabled($taglia->giacenza === 0 && (int) $riga['variante_id'] !== $taglia->id)>
                                                {{ $taglia->taglia }} — {{ $taglia->giacenza === 0 ? 'esaurita' : $taglia->giacenza.' disp.' }}
                                            </option>
                                        @endforeach
                                    </x-ui.select>
                                    @error('righe.'.$i.'.variante_id') <p class="mt-1 text-xs text-red-700" role="alert">{{ $message }}</p> @enderror
                                </td>
                                <td class="tabular pt-3.5 text-right">
                                    @if ($variante)
                                        <span @class(['font-medium', 'text-red-700' => $eccede, 'text-slate-700' => ! $eccede])>{{ $variante->giacenza }}</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td>
                                    <x-ui.input type="number" min="1" max="{{ $this::MAX_QUANTITA }}" step="1" inputmode="numeric" class="tabular text-right"
                                        wire:model.live.debounce.300ms="righe.{{ $i }}.quantita" aria-label="Quantità riga {{ $i + 1 }}" :invalid="$eccede || $errors->has('righe.'.$i.'.quantita')" />
                                    @error('righe.'.$i.'.quantita')
                                        <p class="mt-1 text-xs text-red-700" role="alert">{{ $message }}</p>
                                    @else
                                        @if ($eccede)
                                            <p class="mt-1 text-xs text-red-700">Oltre la disponibilità</p>
                                        @endif
                                    @enderror
                                </td>
                                <td class="pt-2.5 text-right">
                                    <button type="button" wire:click="rimuoviRiga({{ $i }})" class="grid size-7 place-items-center rounded-md text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="Rimuovi riga {{ $i + 1 }}">
                                        <x-lucide-trash-2 class="size-4" aria-hidden="true" />
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <x-slot:footer>
            <div class="flex items-center justify-between gap-4">
                <p class="tabular text-[13px] text-slate-600">
                    <span class="font-semibold text-slate-900">{{ Number::format($unitaTotali) }}</span> {{ $unitaTotali === 1 ? 'capo' : 'capi' }} in
                    <span class="font-semibold text-slate-900">{{ count($righe) }}</span> {{ count($righe) === 1 ? 'riga' : 'righe' }}
                </p>
                <div class="flex gap-2">
                    <x-ui.button variant="secondary" :href="route('consegne.index')" wire:navigate>Annulla</x-ui.button>
                    <x-ui.button variant="success" icon="check" wire:click="conferma" wire:loading.attr="disabled" wire:target="conferma">
                        <span wire:loading.remove wire:target="conferma">Conferma consegna</span>
                        <span wire:loading wire:target="conferma">Registrazione…</span>
                    </x-ui.button>
                </div>
            </div>
        </x-slot:footer>
    </x-ui.panel>
</div>
