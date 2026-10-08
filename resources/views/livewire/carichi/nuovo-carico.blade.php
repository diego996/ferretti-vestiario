@use('Illuminate\Support\Number')

@php
    [$righeCompilate, $unitaTotali] = $this->riepilogo;
    $perCategoria = $this->catalogo->groupBy(fn ($indumento) => $indumento->categoria->nome);
    $movimentiDisponibili = Route::has('movimenti.index');
@endphp

<div class="grid gap-5 xl:grid-cols-3 xl:items-start">

    <form wire:submit="conferma" class="xl:col-span-2" novalidate>
        <x-ui.panel title="Nuovo carico" icon="package-plus" description="Merce ricevuta: indumento, taglia e quantità" flush>

            <div class="grid gap-4 border-b border-line p-4 sm:grid-cols-3">
                <x-ui.field label="Data e ora del carico" for="dataCarico" required hint="Data operativa: di default è adesso.">
                    <x-ui.input id="dataCarico" type="datetime-local" wire:model="dataCarico" max="{{ now()->format('Y-m-d\TH:i') }}" />
                </x-ui.field>
                <x-ui.field label="Note" for="note" class="sm:col-span-2" hint="Facoltative: fornitore, numero DDT…">
                    <x-ui.input id="note" wire:model="note" maxlength="1000" placeholder="Es. DDT 123 del 05/10" />
                </x-ui.field>
            </div>

            @if ($this->catalogo->isEmpty())
                <x-ui.empty-state icon="shirt" title="Nessun indumento caricabile" description="Per registrare un carico serve almeno un indumento attivo con una taglia attiva.">
                    @if (Route::has('catalogo.create'))
                        <x-ui.button variant="secondary" :href="route('catalogo.create')" icon="plus" wire:navigate>Nuovo indumento</x-ui.button>
                    @endif
                </x-ui.empty-state>
            @else
                @error('righe')
                    <div class="flex items-center gap-2 border-b border-red-200 bg-red-50 px-4 py-2 text-[13px] text-red-800" role="alert">
                        <x-lucide-circle-alert class="size-4 shrink-0" aria-hidden="true" /> {{ $message }}
                    </div>
                @enderror

                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col" class="w-8">#</th>
                                <th scope="col">Indumento</th>
                                <th scope="col" class="w-44">Taglia</th>
                                <th scope="col" class="w-24 text-right">Giacenza</th>
                                <th scope="col" class="w-28">Quantità</th>
                                <th scope="col" class="w-10"><span class="sr-only">Azioni</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($righe as $i => $riga)
                                @php
                                    $indumento = $riga['indumento_id'] !== '' ? $this->catalogo->get((int) $riga['indumento_id']) : null;
                                    $variante = $indumento?->varianti->firstWhere('id', (int) $riga['variante_id']);
                                @endphp
                                <tr wire:key="riga-{{ $i }}" class="align-top">
                                    <td class="tabular pt-3.5 text-slate-400">{{ $i + 1 }}</td>
                                    <td>
                                        <x-ui.select wire:model.live="righe.{{ $i }}.indumento_id" placeholder="Scegli indumento…" aria-label="Indumento riga {{ $i + 1 }}" :invalid="$errors->has('righe.'.$i.'.indumento_id')">
                                            @foreach ($perCategoria as $categoria => $indumenti)
                                                <optgroup label="{{ $categoria }}">
                                                    @foreach ($indumenti as $voce)
                                                        <option value="{{ $voce->id }}">{{ $voce->codice ? $voce->codice.' · ' : '' }}{{ $voce->nome }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </x-ui.select>
                                        @error('righe.'.$i.'.indumento_id') <p class="mt-1 text-xs text-red-700" role="alert">{{ $message }}</p> @enderror
                                    </td>
                                    <td>
                                        <x-ui.select wire:model.live="righe.{{ $i }}.variante_id" :placeholder="$indumento ? 'Taglia…' : '—'" :disabled="! $indumento" aria-label="Taglia riga {{ $i + 1 }}" :invalid="$errors->has('righe.'.$i.'.variante_id')">
                                            @foreach ($indumento?->varianti ?? [] as $taglia)
                                                <option value="{{ $taglia->id }}">{{ $taglia->taglia }}</option>
                                            @endforeach
                                        </x-ui.select>
                                        @error('righe.'.$i.'.variante_id') <p class="mt-1 text-xs text-red-700" role="alert">{{ $message }}</p> @enderror
                                    </td>
                                    <td class="tabular pt-3.5 text-right text-slate-600">
                                        @if ($variante)
                                            {{ $variante->giacenza }}
                                            @if ((int) $riga['quantita'] > 0)
                                                <span class="block text-xs text-emerald-700">→ {{ $variante->giacenza + (int) $riga['quantita'] }}</span>
                                            @endif
                                        @else
                                            <span class="text-slate-300">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <x-ui.input type="number" min="1" max="{{ $this::MAX_QUANTITA }}" step="1" inputmode="numeric" class="tabular text-right"
                                            wire:model.live.debounce.300ms="righe.{{ $i }}.quantita" aria-label="Quantità riga {{ $i + 1 }}" :invalid="$errors->has('righe.'.$i.'.quantita')" />
                                        @error('righe.'.$i.'.quantita') <p class="mt-1 text-xs text-red-700" role="alert">{{ $message }}</p> @enderror
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

                <div class="border-t border-line px-4 py-2">
                    <x-ui.button variant="ghost" size="sm" icon="plus" wire:click="aggiungiRiga">Aggiungi riga</x-ui.button>
                </div>
            @endif

            <x-slot:footer>
                <div class="flex items-center justify-between gap-4">
                    <p class="tabular text-[13px] text-slate-600">
                        <span class="font-semibold text-slate-900">{{ Number::format($unitaTotali) }}</span> unità su
                        <span class="font-semibold text-slate-900">{{ $righeCompilate }}</span> {{ $righeCompilate === 1 ? 'riga' : 'righe' }}
                    </p>
                    <x-ui.button type="submit" variant="success" icon="check" :disabled="$this->catalogo->isEmpty()" wire:loading.attr="disabled" wire:target="conferma">
                        <span wire:loading.remove wire:target="conferma">Conferma carico</span>
                        <span wire:loading wire:target="conferma">Registrazione…</span>
                    </x-ui.button>
                </div>
            </x-slot:footer>
        </x-ui.panel>
    </form>

    <x-ui.panel title="Ultimi carichi" icon="history" flush>
        @if ($this->ultimiCarichi->isEmpty())
            <x-ui.empty-state icon="package" title="Nessun carico registrato" />
        @else
            <ul class="divide-y divide-line/70">
                @foreach ($this->ultimiCarichi as $carico)
                    <li wire:key="carico-{{ $carico->id }}" class="flex items-center gap-3 px-4 py-2">
                        <div class="min-w-0 flex-1">
                            <p class="tabular text-[13px] font-medium text-slate-900">
                                {{ $carico->data_carico->format('d/m/Y') }} <span class="font-normal text-slate-400">{{ $carico->data_carico->format('H:i') }}</span>
                            </p>
                            <p class="truncate text-xs text-slate-500" title="{{ $carico->note }}">
                                {{ $carico->operatore->name }}@if ($carico->note) · {{ $carico->note }}@endif
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="tabular text-[13px] font-semibold text-slate-900">+{{ Number::format((int) $carico->totale_unita) }}</p>
                            <p class="text-xs text-slate-500">{{ $carico->righe_count }} {{ $carico->righe_count === 1 ? 'taglia' : 'taglie' }}</p>
                        </div>
                        @if ($movimentiDisponibili)
                            <a href="{{ route('movimenti.index', ['carico' => $carico->id]) }}" wire:navigate class="grid size-7 place-items-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Movimenti del carico del {{ $carico->data_carico->format('d/m/Y H:i') }}" title="Vedi movimenti">
                                <x-lucide-list class="size-4" aria-hidden="true" />
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.panel>
</div>
