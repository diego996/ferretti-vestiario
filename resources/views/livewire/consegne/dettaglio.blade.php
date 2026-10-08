@use('App\Enums\StatoDocumento')
@use('Illuminate\Support\Js')
@use('Illuminate\Support\Number')

@php $totale = $consegna->righe->sum('quantita'); @endphp

<div class="space-y-5">

    @if ($consegna->isAnnullata())
        <div class="flex items-start gap-3 rounded-panel border border-red-200 bg-red-50 px-4 py-3 text-[13px] text-red-900" role="status">
            <x-lucide-ban class="mt-0.5 size-4 shrink-0 text-red-600" aria-hidden="true" />
            <div>
                <p class="font-semibold">Consegna annullata il {{ $consegna->annullata_at->format('d/m/Y \a\l\l\e H:i') }} da {{ $consegna->annullataDa?->name ?? '—' }}</p>
                <p class="mt-0.5">Motivazione: {{ $consegna->motivo_annullamento }}</p>
                <p class="mt-0.5 text-xs text-red-700">I capi sono rientrati a magazzino con movimenti di compensazione. La consegna resta nello storico.</p>
            </div>
        </div>
    @endif

    <div class="grid gap-5 xl:grid-cols-3 xl:items-start">

        {{-- Articoli --}}
        <x-ui.panel title="Articoli consegnati" icon="shirt" flush class="xl:col-span-2">
            <x-slot:actions>
                <x-ui.badge>{{ Number::format($totale) }} {{ $totale === 1 ? 'capo' : 'capi' }}</x-ui.badge>
            </x-slot:actions>

            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th scope="col" class="w-28">Codice</th>
                            <th scope="col">Articolo</th>
                            <th scope="col" class="w-24">Taglia</th>
                            <th scope="col" class="w-24 text-right">Quantità</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($consegna->righe as $riga)
                            <tr wire:key="riga-{{ $riga->id }}">
                                <td class="tabular text-slate-500">{{ $riga->indumento_codice ?? '—' }}</td>
                                <td class="font-medium text-slate-900">{{ $riga->indumento_nome }}</td>
                                <td class="font-medium">{{ $riga->taglia }}</td>
                                <td class="tabular text-right font-semibold">{{ $riga->quantita }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-slot:footer>Descrizioni e taglie sono quelle registrate al momento della consegna.</x-slot:footer>
        </x-ui.panel>

        <div class="space-y-5">

            {{-- Riepilogo --}}
            <x-ui.panel title="Consegna {{ $consegna->numero }}" icon="clipboard-check">
                <x-slot:actions>
                    <x-ui.badge :tone="$consegna->stato->tone()" dot>{{ $consegna->stato->label() }}</x-ui.badge>
                </x-slot:actions>

                <dl class="grid grid-cols-[7rem_1fr] gap-x-3 gap-y-2.5 text-[13px]">
                    <dt class="text-slate-500">Collega</dt>
                    <dd>
                        <span class="font-semibold text-slate-900">{{ $consegna->collega->nominativo }}</span>
                        @if ($consegna->collega->matricola)
                            <span class="tabular block text-xs text-slate-500">Matricola {{ $consegna->collega->matricola }}</span>
                        @endif
                    </dd>
                    <dt class="text-slate-500">Commessa</dt>
                    <dd>
                        <span class="font-medium text-slate-900">{{ $consegna->commessa->nome }}</span>
                        @if ($consegna->commessa->codice)
                            <span class="tabular block text-xs text-slate-500">{{ $consegna->commessa->codice }}</span>
                        @endif
                    </dd>
                    <dt class="text-slate-500">Data</dt>
                    <dd class="tabular">{{ $consegna->data_consegna->format('d/m/Y H:i') }}</dd>
                    <dt class="text-slate-500">Operatore</dt>
                    <dd>{{ $consegna->operatore->name }}</dd>
                    <dt class="text-slate-500">Registrata</dt>
                    <dd class="tabular text-slate-600">{{ $consegna->created_at->format('d/m/Y H:i') }}</dd>
                    @if ($consegna->note)
                        <dt class="text-slate-500">Note</dt>
                        <dd class="whitespace-pre-line text-slate-700">{{ $consegna->note }}</dd>
                    @endif
                </dl>
            </x-ui.panel>

            {{-- Documento Word --}}
            @php $documento = $consegna->documento; @endphp
            <x-ui.panel title="Documento Word" icon="file-text"
                x-data
                x-init="{{ session('scarica_documento') && $documento?->stato === StatoDocumento::Generato ? 'window.location.href = '.Js::from(route('consegne.documento', $consegna)) : '' }}"
                x-on:scarica-documento.window="window.location.href = $event.detail.url">
                @if ($documento?->stato === StatoDocumento::Generato)
                    <div class="flex items-start gap-3">
                        <span class="grid size-9 shrink-0 place-items-center rounded-md bg-primary-50 text-primary-700" aria-hidden="true">
                            <x-lucide-file-text class="size-[18px]" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] font-medium text-slate-900" title="{{ $documento->nome_file }}">{{ $documento->nome_file }}</p>
                            <p class="text-xs text-slate-500">Archiviato il {{ $documento->generato_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    <x-ui.button icon="download" class="mt-3 w-full" :href="route('consegne.documento', $consegna)">Scarica documento</x-ui.button>
                    <p class="mt-2 text-xs text-slate-500">È il file originale archiviato alla consegna: non viene rigenerato.</p>
                @elseif ($documento?->stato === StatoDocumento::Fallito)
                    <div class="rounded-control border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800" role="alert">
                        <p class="font-semibold">Generazione non riuscita ({{ $documento->tentativi }} {{ $documento->tentativi === 1 ? 'tentativo' : 'tentativi' }})</p>
                        <p class="mt-0.5 break-words">{{ $documento->ultimo_errore }}</p>
                    </div>
                    @unless ($consegna->isAnnullata())
                        <x-ui.button icon="refresh-cw" class="mt-3 w-full" wire:click="generaDocumento" wire:loading.attr="disabled" wire:target="generaDocumento">Riprova</x-ui.button>
                    @endunless
                @elseif ($consegna->isAnnullata())
                    <p class="text-xs text-slate-500">Consegna annullata prima che il documento fosse generato.</p>
                @else
                    <p class="text-xs text-slate-500">Il documento non è ancora stato generato.</p>
                    <x-ui.button icon="file-plus" class="mt-3 w-full" wire:click="generaDocumento" wire:loading.attr="disabled" wire:target="generaDocumento">Genera documento</x-ui.button>
                @endif
            </x-ui.panel>

            {{-- Azioni --}}
            <x-ui.panel title="Azioni" icon="zap" flush>
                <div class="space-y-2 p-4">
                    <x-ui.button variant="ghost" icon="history" class="w-full" :href="route('movimenti.index', ['consegna' => $consegna->id])" wire:navigate>
                        Movimenti di magazzino
                    </x-ui.button>
                    @unless ($consegna->isAnnullata())
                        <x-ui.button variant="ghost" icon="ban" class="w-full text-red-700 hover:bg-red-50 hover:text-red-800"
                            x-on:click="$dispatch('open-confirm', { id: 'annulla-consegna' })">
                            Annulla consegna
                        </x-ui.button>
                    @endunless
                </div>
            </x-ui.panel>
        </div>
    </div>

    @unless ($consegna->isAnnullata())
        <x-ui.confirm-modal id="annulla-consegna" title="Annullare la consegna {{ $consegna->numero }}?">
            <p>{{ $totale === 1 ? 'Il capo rientrerà' : 'I '.Number::format($totale).' capi rientreranno' }} a magazzino. La consegna resterà nello storico come annullata e l'operazione non si può annullare.</p>

            <x-ui.field label="Motivazione" for="motivo" required class="mt-3">
                <x-ui.textarea id="motivo" rows="3" wire:model="motivo" maxlength="1000" placeholder="Es. taglia errata, consegna da rifare" />
            </x-ui.field>

            <x-slot:confirm>
                <x-ui.button variant="danger" icon="ban" wire:click="annulla" wire:loading.attr="disabled" wire:target="annulla">Conferma annullamento</x-ui.button>
            </x-slot:confirm>
        </x-ui.confirm-modal>
    @endunless
</div>
