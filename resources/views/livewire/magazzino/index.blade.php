@use('App\Enums\StatoScorta')
@use('Illuminate\Support\Number')

@php
    $filtriAttivi = $cerca !== '' || $categoria !== '' || $stato !== '' || $inattivi;
    $chip = ['' => 'Tutte'] + collect(StatoScorta::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all();
@endphp

<div class="space-y-4">

    {{-- Filtri --}}
    <div class="panel flex flex-wrap items-end gap-3 px-4 py-3">
        <x-ui.field label="Cerca" for="cerca" class="w-72">
            <x-ui.input id="cerca" type="search" icon="search" wire:model.live.debounce.300ms="cerca" placeholder="Nome o codice indumento" autocomplete="off" />
        </x-ui.field>
        <x-ui.field label="Categoria" for="categoria" class="w-56">
            <x-ui.select id="categoria" wire:model.live="categoria" placeholder="Tutte le categorie">
                @foreach ($this->categorie as $voce)
                    <option value="{{ $voce->id }}">{{ $voce->nome }}</option>
                @endforeach
            </x-ui.select>
        </x-ui.field>
        <div class="pb-1.5">
            <x-ui.checkbox wire:model.live="inattivi">Mostra anche disattivati</x-ui.checkbox>
        </div>
        @if ($filtriAttivi)
            <x-ui.button variant="ghost" size="sm" icon="x" class="mb-0.5" wire:click="azzeraFiltri">Azzera filtri</x-ui.button>
        @endif
        <div class="ml-auto flex items-center gap-2 pb-0.5">
            @if (Route::has('carichi.create'))
                <x-ui.button variant="secondary" :href="route('carichi.create')" icon="package-plus" wire:navigate>Nuovo carico</x-ui.button>
            @endif
        </div>
    </div>

    <x-ui.panel flush>

        {{-- Filtro rapido per stato --}}
        <div class="flex flex-wrap items-center gap-1.5 border-b border-line px-4 py-2" role="group" aria-label="Filtra per disponibilità">
            @foreach ($chip as $valore => $etichetta)
                <button type="button" wire:click="$set('stato', '{{ $valore }}')" aria-pressed="{{ $stato === $valore ? 'true' : 'false' }}"
                    @class([
                        'inline-flex h-7 items-center gap-1.5 rounded-full px-3 text-xs font-medium ring-1 ring-inset transition-colors',
                        'bg-primary-600 text-white ring-primary-600' => $stato === $valore,
                        'bg-white text-slate-600 ring-line-strong hover:bg-slate-50' => $stato !== $valore,
                    ])>
                    {{ $etichetta }}
                    <span @class(['tabular', 'text-primary-100' => $stato === $valore, 'text-slate-400' => $stato !== $valore])>{{ Number::format($this->conteggi[$valore]) }}</span>
                </button>
            @endforeach
        </div>

        <div class="relative" wire:loading.class="opacity-60" wire:target="cerca,categoria,stato,inattivi,azzeraFiltri,gotoPage,nextPage,previousPage">
            @if ($this->varianti->isEmpty())
                <x-ui.empty-state icon="package-search" title="Nessuna taglia trovata"
                    :description="$filtriAttivi ? 'Nessun risultato con i filtri impostati.' : 'Il catalogo è vuoto: aggiungi indumenti e taglie dal Catalogo.'">
                    @if ($filtriAttivi)
                        <x-ui.button variant="secondary" size="sm" wire:click="azzeraFiltri">Azzera filtri</x-ui.button>
                    @endif
                </x-ui.empty-state>
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th scope="col" class="w-28">Codice</th>
                                <th scope="col">Indumento</th>
                                <th scope="col">Categoria</th>
                                <th scope="col" class="w-20">Taglia</th>
                                <th scope="col" class="w-24 text-right">Giacenza</th>
                                <th scope="col" class="w-24 text-right">Scorta min.</th>
                                <th scope="col" class="w-32">Stato</th>
                                <th scope="col" class="w-24"><span class="sr-only">Azioni</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->varianti as $v)
                                @php $disattivata = ! $v->attiva || ! $v->indumento->attivo; @endphp
                                <tr wire:key="variante-{{ $v->id }}" @class(['text-slate-400' => $disattivata])>
                                    <td class="tabular whitespace-nowrap text-slate-500">{{ $v->indumento->codice ?? '—' }}</td>
                                    <td class="font-medium {{ $disattivata ? '' : 'text-slate-900' }}">
                                        {{ $v->indumento->nome }}
                                        @if ($disattivata)
                                            <x-ui.badge class="ml-1">Disattivata</x-ui.badge>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap text-slate-500">{{ $v->indumento->categoria->nome }}</td>
                                    <td class="font-medium whitespace-nowrap">{{ $v->taglia }}</td>
                                    <td @class([
                                        'tabular text-right font-semibold',
                                        'text-red-700' => ! $disattivata && $v->giacenza === 0,
                                        'text-amber-700' => ! $disattivata && $v->giacenza > 0 && $v->giacenza <= $v->scorta_minima,
                                        'text-slate-900' => ! $disattivata && $v->giacenza > $v->scorta_minima,
                                    ])>{{ Number::format($v->giacenza) }}</td>
                                    <td class="tabular text-right text-slate-500">{{ $v->scorta_minima }}</td>
                                    <td><x-ui.stock-status :stato="$v->statoScorta()" /></td>
                                    <td class="text-right">
                                        <x-ui.button variant="ghost" size="sm" icon="history"
                                            x-on:click="$dispatch('open-drawer', { id: 'variante' })"
                                            wire:click="apriVariante({{ $v->id }})"
                                            aria-label="Movimenti e rettifica di {{ $v->indumento->nome }} taglia {{ $v->taglia }}">
                                            Dettaglio
                                        </x-ui.button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{ $this->varianti->links() }}
    </x-ui.panel>

    {{-- Drawer: storico movimenti e rettifica --}}
    @php $sel = $this->variante; @endphp
    <x-ui.drawer id="variante"
        :title="$sel ? $sel->indumento->nome.' — taglia '.$sel->taglia : 'Dettaglio taglia'"
        :description="$sel ? trim(($sel->indumento->codice ?? '').' · '.$sel->indumento->categoria->nome, ' ·') : null">

        <div wire:loading.flex wire:target="apriVariante" class="flex-col">
            <x-ui.skeleton :rows="6" />
        </div>

        <div wire:loading.remove wire:target="apriVariante">
            @if ($sel)
                <dl class="grid grid-cols-3 divide-x divide-line border-b border-line">
                    <div class="px-5 py-3">
                        <dt class="text-xs text-slate-500">Giacenza</dt>
                        <dd class="tabular text-2xl font-semibold text-slate-900">{{ Number::format($sel->giacenza) }}</dd>
                    </div>
                    <div class="px-5 py-3">
                        <dt class="text-xs text-slate-500">Scorta minima</dt>
                        <dd class="tabular text-2xl font-semibold text-slate-500">{{ $sel->scorta_minima }}</dd>
                    </div>
                    <div class="px-5 py-3">
                        <dt class="text-xs text-slate-500">Stato</dt>
                        <dd class="mt-1.5"><x-ui.stock-status :stato="$sel->statoScorta()" /></dd>
                    </div>
                </dl>

                {{-- Rettifica inventariale --}}
                <form wire:submit="registraRettifica" class="space-y-3 border-b border-line px-5 py-4" novalidate>
                    <div>
                        <h3 class="text-[13px] font-semibold text-slate-900">Rettifica inventariale</h3>
                        <p class="text-xs text-slate-500">Correzione dopo un conteggio fisico. Resta nello storico con la motivazione.</p>
                    </div>

                    <div class="grid grid-cols-[auto_8rem] items-end gap-3">
                        <fieldset>
                            <legend class="mb-1 text-xs font-medium text-slate-700">Tipo</legend>
                            <div class="inline-flex rounded-control border border-line-strong p-0.5">
                                @foreach (['aumento' => ['Aumenta', 'plus'], 'riduzione' => ['Riduci', 'minus']] as $valore => [$etichetta, $icona])
                                    <label @class([
                                        'flex h-7 cursor-pointer items-center gap-1 rounded px-3 text-xs font-medium',
                                        'bg-primary-600 text-white' => $rettificaVerso === $valore,
                                        'text-slate-600 hover:bg-slate-50' => $rettificaVerso !== $valore,
                                    ])>
                                        <input type="radio" class="sr-only" name="rettificaVerso" value="{{ $valore }}" wire:model.live="rettificaVerso">
                                        <x-dynamic-component :component="'lucide-'.$icona" class="size-3.5" aria-hidden="true" /> {{ $etichetta }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        <x-ui.field label="Quantità" for="rettificaQuantita" required>
                            <x-ui.input id="rettificaQuantita" type="number" min="1" step="1" inputmode="numeric" class="tabular text-right" wire:model="rettificaQuantita" />
                        </x-ui.field>
                    </div>

                    <x-ui.field label="Motivazione" for="rettificaMotivo" required>
                        <x-ui.textarea id="rettificaMotivo" rows="2" maxlength="500" wire:model="rettificaMotivo" placeholder="Es. inventario del 08/10: 2 capi danneggiati" />
                    </x-ui.field>

                    <div class="flex justify-end">
                        <x-ui.button type="submit" :variant="$rettificaVerso === 'riduzione' ? 'danger' : 'primary'" icon="check" wire:loading.attr="disabled" wire:target="registraRettifica">
                            Registra rettifica
                        </x-ui.button>
                    </div>
                </form>

                {{-- Movimenti --}}
                <div>
                    <h3 class="px-5 pt-4 pb-2 text-[13px] font-semibold text-slate-900">Ultimi movimenti</h3>
                    @if ($this->movimenti->isEmpty())
                        <x-ui.empty-state icon="history" title="Nessun movimento" description="La taglia non è mai stata caricata o consegnata." />
                    @else
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th scope="col">Data</th>
                                    <th scope="col">Operazione</th>
                                    <th scope="col" class="text-right">Qtà</th>
                                    <th scope="col" class="text-right">Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->movimenti as $m)
                                    <tr wire:key="mov-{{ $m->id }}" class="align-top">
                                        <td class="tabular whitespace-nowrap text-slate-600">
                                            {{ $m->data_movimento->format('d/m/Y') }}
                                            <span class="block text-xs text-slate-400">{{ $m->data_movimento->format('H:i') }} · {{ $m->operatore->name }}</span>
                                        </td>
                                        <td>
                                            <x-ui.badge :tone="$m->tipo->tone()">{{ $m->tipo->label() }}</x-ui.badge>
                                            @if ($origine = $m->descrizioneOrigine())
                                                <span class="mt-0.5 block text-xs text-slate-500">{{ $origine }}</span>
                                            @endif
                                            @if ($m->note)
                                                <span class="mt-0.5 block text-xs break-words text-slate-500 italic">{{ $m->note }}</span>
                                            @endif
                                        </td>
                                        <td class="tabular text-right font-semibold {{ $m->quantita > 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $m->quantita > 0 ? '+' : '−' }}{{ abs($m->quantita) }}</td>
                                        <td class="tabular text-right text-slate-600">{{ $m->giacenza_risultante }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if (Route::has('movimenti.index'))
                            <div class="px-5 py-3">
                                <x-ui.button variant="ghost" size="sm" icon-right="arrow-right" :href="route('movimenti.index', ['variante' => $sel->id])" wire:navigate>Storico completo</x-ui.button>
                            </div>
                        @endif
                    @endif
                </div>
            @endif
        </div>
    </x-ui.drawer>
</div>
