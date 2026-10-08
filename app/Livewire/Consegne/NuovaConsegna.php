<?php

namespace App\Livewire\Consegne;

use App\Enums\StatoDocumento;
use App\Exceptions\GiacenzaInsufficienteException;
use App\Exceptions\OperazioneNonConsentitaException;
use App\Models\Collega;
use App\Models\Commessa;
use App\Models\Indumento;
use App\Services\ConsegnaService;
use App\Services\DocumentoConsegnaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Le righe restano in bozza nel componente: nessun movimento finché non si conferma.
 */
#[Title('Nuova consegna')]
class NuovaConsegna extends Component
{
    public const LIMITE_RISULTATI = 8;

    public const MAX_QUANTITA = 1000;

    /** Generata all'apertura del modulo: un doppio invio non crea due consegne. */
    #[Locked]
    public string $idempotencyKey = '';

    public ?int $collegaId = null;

    public string $cercaCollega = '';

    public ?int $commessaId = null;

    public string $cercaCommessa = '';

    public string $dataConsegna = '';

    public string $note = '';

    public string $cercaArticolo = '';

    /** @var list<array{indumento_id: int, variante_id: string, quantita: string}> */
    public array $righe = [];

    public function mount(): void
    {
        $this->idempotencyKey = (string) Str::uuid();
        $this->dataConsegna = now()->format('Y-m-d\TH:i');

        if ($collega = Collega::attivi()->find((int) request()->query('collega'))) {
            $this->collegaId = $collega->id;
        }
    }

    public function scegliCollega(int $id): void
    {
        $this->collegaId = Collega::attivi()->findOrFail($id)->id;
        $this->cercaCollega = '';
        $this->resetErrorBag('collegaId');
    }

    public function scegliPrimoCollega(): void
    {
        if ($primo = $this->risultatiColleghi->first()) {
            $this->scegliCollega($primo->id);
        }
    }

    public function cambiaCollega(): void
    {
        $this->collegaId = null;
    }

    public function scegliCommessa(int $id): void
    {
        $this->commessaId = Commessa::attive()->findOrFail($id)->id;
        $this->cercaCommessa = '';
        $this->resetErrorBag('commessaId');
    }

    public function scegliPrimaCommessa(): void
    {
        if ($prima = $this->risultatiCommesse->first()) {
            $this->scegliCommessa($prima->id);
        }
    }

    public function cambiaCommessa(): void
    {
        $this->commessaId = null;
    }

    public function aggiungiArticolo(int $indumentoId): void
    {
        $indumento = $this->queryArticoli()->findOrFail($indumentoId);
        $conScorta = $indumento->varianti->filter(fn ($v) => $v->giacenza > 0);

        $this->righe[] = [
            'indumento_id' => $indumento->id,
            // Con una sola taglia disponibile (es. "Unica") la si propone già.
            'variante_id' => $conScorta->count() === 1 ? (string) $conScorta->first()->id : '',
            'quantita' => '1',
        ];

        $this->cercaArticolo = '';
        $this->resetErrorBag('righe');
        unset($this->articoli);
    }

    public function aggiungiPrimoArticolo(): void
    {
        if ($primo = $this->risultatiArticoli->first()) {
            $this->aggiungiArticolo($primo->id);
        }
    }

    public function rimuoviRiga(int $indice): void
    {
        unset($this->righe[$indice]);
        $this->righe = array_values($this->righe);
        $this->resetValidation();
    }

    public function conferma(ConsegnaService $consegne, DocumentoConsegnaService $documenti): void
    {
        $this->validate(...$this->regole());

        if ($this->segnalaEccedenze()) {
            return;
        }

        try {
            $consegna = $consegne->registra(
                Collega::findOrFail($this->collegaId),
                Commessa::findOrFail($this->commessaId),
                collect($this->righe)->map(fn (array $r) => ['variante_id' => $r['variante_id'], 'quantita' => $r['quantita']]),
                auth()->user(),
                Carbon::parse($this->dataConsegna),
                $this->note,
                $this->idempotencyKey,
            );
        } catch (GiacenzaInsufficienteException $e) {
            // Disponibilità cambiata nel frattempo (altra consegna o rettifica).
            unset($this->articoli);
            foreach ($e->mancanze as $mancanza) {
                foreach ($this->righe as $i => $riga) {
                    if ((int) $riga['variante_id'] === $mancanza['variante_id']) {
                        $this->addError("righe.$i.quantita", "Disponibili {$mancanza['disponibile']}.");
                    }
                }
            }
            $this->addError('righe', 'La disponibilità è cambiata: '.$e->getMessage());

            return;
        } catch (OperazioneNonConsentitaException|InvalidArgumentException $e) {
            $this->addError('righe', $e->getMessage());

            return;
        }

        // A consegna già salvata: un errore qui non la annulla, si potrà ritentare dal dettaglio.
        $documento = $documenti->genera($consegna);

        if ($documento->stato === StatoDocumento::Generato) {
            session()->flash('scarica_documento', true);
            session()->flash('toast', ['type' => 'success', 'message' => "Consegna {$consegna->numero} registrata. Download del documento in corso."]);
        } else {
            session()->flash('toast', ['type' => 'warning', 'message' => "Consegna {$consegna->numero} registrata, ma il documento Word non è stato generato: riprova dal dettaglio."]);
        }

        $this->redirectRoute('consegne.show', $consegna, navigate: true);
    }

    #[Computed]
    public function collega(): ?Collega
    {
        return $this->collegaId ? Collega::find($this->collegaId) : null;
    }

    #[Computed]
    public function commessa(): ?Commessa
    {
        return $this->commessaId ? Commessa::find($this->commessaId) : null;
    }

    /** @return Collection<int, Collega> */
    #[Computed]
    public function risultatiColleghi(): Collection
    {
        if (trim($this->cercaCollega) === '') {
            return collect();
        }

        return Collega::attivi()->cerca($this->cercaCollega)->ordinati()->limit(self::LIMITE_RISULTATI)->get();
    }

    /** @return Collection<int, Commessa> */
    #[Computed]
    public function risultatiCommesse(): Collection
    {
        if (trim($this->cercaCommessa) === '') {
            return collect();
        }

        return Commessa::attive()->cerca($this->cercaCommessa)->orderBy('nome')->limit(self::LIMITE_RISULTATI)->get();
    }

    /** @return Collection<int, Indumento> indumenti consegnabili con la disponibilità totale */
    #[Computed]
    public function risultatiArticoli(): Collection
    {
        if (trim($this->cercaArticolo) === '') {
            return collect();
        }

        return $this->queryArticoli()
            ->cerca($this->cercaArticolo)
            ->withSum(['varianti as disponibili' => fn (Builder $q) => $q->where('attiva', true)], 'giacenza')
            ->orderBy('nome')
            ->limit(self::LIMITE_RISULTATI)
            ->get();
    }

    /** @return Collection<int, Indumento> indumenti delle righe, con le taglie attive e la giacenza attuale */
    #[Computed]
    public function articoli(): Collection
    {
        $ids = collect($this->righe)->pluck('indumento_id')->unique()->all();

        return $this->queryArticoli()->whereIn('id', $ids)->get()->keyBy('id');
    }

    /**
     * Quantità richiesta in totale per ogni taglia (più righe possono riferirsi alla stessa).
     *
     * @return array<int, int>
     */
    #[Computed]
    public function richiestePerVariante(): array
    {
        $totali = [];

        foreach ($this->righe as $riga) {
            if ($riga['variante_id'] !== '') {
                $totali[(int) $riga['variante_id']] = ($totali[(int) $riga['variante_id']] ?? 0) + max(0, (int) $riga['quantita']);
            }
        }

        return $totali;
    }

    public function render()
    {
        return view('livewire.consegne.nuova-consegna')
            ->layoutData(['breadcrumb' => [
                ['label' => 'Consegne', 'url' => route('consegne.index')],
                ['label' => 'Nuova consegna'],
            ]]);
    }

    /** Indumenti attivi con almeno una taglia attiva; `varianti` limitata alle taglie attive. */
    private function queryArticoli(): Builder
    {
        return Indumento::query()
            ->attivi()
            ->whereHas('varianti', fn (Builder $q) => $q->where('attiva', true))
            ->with(['varianti' => fn ($q) => $q->where('attiva', true)]);
    }

    /** Segnala le righe che, sommate per taglia, superano la giacenza visibile. */
    private function segnalaEccedenze(): bool
    {
        unset($this->articoli);
        $giacenze = $this->articoli->flatMap->varianti->pluck('giacenza', 'id');
        $eccedenze = false;

        foreach ($this->righe as $i => $riga) {
            $id = (int) $riga['variante_id'];
            $disponibile = (int) ($giacenze[$id] ?? 0);

            if (($this->richiestePerVariante[$id] ?? 0) > $disponibile) {
                $this->addError("righe.$i.quantita", "Disponibili {$disponibile}.");
                $eccedenze = true;
            }
        }

        return $eccedenze;
    }

    /** @return array{array<string, mixed>, array<string, string>, array<string, string>} */
    private function regole(): array
    {
        return [
            [
                'collegaId' => ['required', Rule::exists('colleghi', 'id')->where('attivo', true)],
                'commessaId' => ['required', Rule::exists('commesse', 'id')->where('attiva', true)],
                'dataConsegna' => ['required', 'date', 'before_or_equal:now'],
                'note' => ['nullable', 'string', 'max:1000'],
                'righe' => ['required', 'array', 'min:1'],
                'righe.*.variante_id' => ['required', 'integer', Rule::exists('indumento_varianti', 'id')->where('attiva', true)],
                'righe.*.quantita' => ['required', 'integer', 'min:1', 'max:'.self::MAX_QUANTITA],
            ],
            [
                'collegaId.required' => 'Scegli il collega che riceve il vestiario.',
                'collegaId.exists' => 'Il collega scelto non è attivo.',
                'commessaId.required' => 'Scegli la commessa.',
                'commessaId.exists' => 'La commessa scelta non è attiva.',
                'dataConsegna.before_or_equal' => 'La data della consegna non può essere futura.',
                'righe.required' => 'Aggiungi almeno un articolo.',
                'righe.*.variante_id.required' => 'Scegli la taglia.',
                'righe.*.variante_id.exists' => 'Taglia non disponibile.',
            ],
            [
                'dataConsegna' => 'data della consegna',
                'righe.*.quantita' => 'quantità',
            ],
        ];
    }
}
