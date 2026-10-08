<?php

namespace App\Livewire\Catalogo;

use App\Models\CategoriaIndumento;
use App\Models\Indumento;
use App\Models\IndumentoVariante;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Creazione e modifica di un indumento con le sue taglie.
 *
 * Le taglie nascono sempre a giacenza zero (la giacenza si modifica solo con
 * carichi e rettifiche). Una taglia con movimenti non si elimina: si disattiva.
 */
class FormIndumento extends Component
{
    /** Gruppi di taglie aggiungibili con un clic. */
    public const PRESET = [
        'abbigliamento' => ['etichetta' => 'S – XXL', 'taglie' => ['S', 'M', 'L', 'XL', 'XXL']],
        'scarpe' => ['etichetta' => '39 – 46', 'taglie' => ['39', '40', '41', '42', '43', '44', '45', '46']],
        'unica' => ['etichetta' => 'Unica', 'taglie' => ['Unica']],
    ];

    public ?int $indumentoId = null;

    public string $categoria_id = '';

    public string $codice = '';

    public string $nome = '';

    public string $descrizione = '';

    public bool $attivo = true;

    /** @var list<array{id: ?int, taglia: string, scorta_minima: string, attiva: bool, giacenza: int, movimentata: bool}> */
    public array $varianti = [];

    /** @var list<int> taglie esistenti da eliminare al salvataggio */
    public array $daEliminare = [];

    public function mount(?Indumento $indumento = null): void
    {
        if (! $indumento?->exists) {
            $this->aggiungiTaglia();

            return;
        }

        $this->indumentoId = $indumento->id;
        $this->categoria_id = (string) $indumento->categoria_id;
        $this->codice = (string) $indumento->codice;
        $this->nome = $indumento->nome;
        $this->descrizione = (string) $indumento->descrizione;
        $this->attivo = $indumento->attivo;

        $this->varianti = $indumento->varianti()
            ->withExists('movimenti as movimentata')
            ->get()
            ->map(fn (IndumentoVariante $v) => [
                'id' => $v->id,
                'taglia' => $v->taglia,
                'scorta_minima' => (string) $v->scorta_minima,
                'attiva' => $v->attiva,
                'giacenza' => $v->giacenza,
                'movimentata' => (bool) $v->movimentata,
            ])
            ->all();
    }

    public function aggiungiTaglia(string $taglia = ''): void
    {
        $this->varianti[] = [
            'id' => null,
            'taglia' => $taglia,
            'scorta_minima' => $this->varianti === [] ? '0' : (string) end($this->varianti)['scorta_minima'],
            'attiva' => true,
            'giacenza' => 0,
            'movimentata' => false,
        ];
    }

    /** Aggiunge le taglie del gruppo non ancora presenti; sostituisce l'eventuale riga iniziale vuota. */
    public function aggiungiPreset(string $chiave): void
    {
        $preset = self::PRESET[$chiave] ?? null;

        if (! $preset) {
            return;
        }

        $this->varianti = array_values(array_filter($this->varianti, fn (array $v) => $v['id'] !== null || trim($v['taglia']) !== ''));
        $presenti = collect($this->varianti)->map(fn (array $v) => Str::lower(trim($v['taglia'])));

        foreach ($preset['taglie'] as $taglia) {
            if (! $presenti->contains(Str::lower($taglia))) {
                $this->aggiungiTaglia($taglia);
            }
        }
    }

    public function sposta(int $indice, int $direzione): void
    {
        $destinazione = $indice + ($direzione < 0 ? -1 : 1);

        if (! isset($this->varianti[$indice], $this->varianti[$destinazione])) {
            return;
        }

        [$this->varianti[$indice], $this->varianti[$destinazione]] = [$this->varianti[$destinazione], $this->varianti[$indice]];
        $this->resetValidation();
    }

    public function rimuovi(int $indice): void
    {
        $riga = $this->varianti[$indice] ?? null;

        if (! $riga) {
            return;
        }

        if ($riga['id'] !== null) {
            if ($this->haMovimenti($riga['id'])) {
                $this->dispatch('toast', type: 'warning', message: "La taglia {$riga['taglia']} ha movimenti: non si può eliminare, disattivala.");

                return;
            }

            $this->daEliminare[] = $riga['id'];
        }

        unset($this->varianti[$indice]);
        $this->varianti = array_values($this->varianti);
        $this->resetValidation();
    }

    public function salva(): void
    {
        $this->codice = trim($this->codice);
        $this->nome = trim($this->nome);
        $this->descrizione = trim($this->descrizione);
        $this->varianti = array_map(fn (array $v) => ['taglia' => trim($v['taglia'])] + $v, $this->varianti);

        $this->validate([
            'categoria_id' => ['required', 'integer', Rule::exists('categorie_indumenti', 'id')],
            'codice' => ['nullable', 'string', 'max:50', Rule::unique('indumenti', 'codice')->ignore($this->indumentoId)],
            'nome' => ['required', 'string', 'max:150'],
            'descrizione' => ['nullable', 'string', 'max:2000'],
            'attivo' => ['boolean'],
            'varianti' => ['required', 'array', 'min:1'],
            'varianti.*.taglia' => ['required', 'string', 'max:20', 'distinct:ignore_case'],
            'varianti.*.scorta_minima' => ['required', 'integer', 'min:0', 'max:100000'],
            'varianti.*.attiva' => ['boolean'],
        ], [
            'varianti.required' => 'Aggiungi almeno una taglia.',
            'varianti.*.taglia.distinct' => 'Taglia ripetuta.',
            'varianti.*.taglia.required' => 'Indica la taglia.',
        ], [
            'varianti.*.taglia' => 'taglia',
            'varianti.*.scorta_minima' => 'scorta minima',
        ]);

        $nuovo = $this->indumentoId === null;

        $indumento = DB::transaction(function () {
            $indumento = Indumento::findOrNew($this->indumentoId);
            $indumento->fill([
                'categoria_id' => (int) $this->categoria_id,
                'codice' => $this->codice ?: null,
                'nome' => $this->nome,
                'descrizione' => $this->descrizione ?: null,
                'attivo' => $this->attivo,
            ])->save();

            foreach ($this->daEliminare as $id) {
                $variante = $indumento->varianti()->whereKey($id)->first();

                if ($variante && ! $this->haMovimenti($id)) {
                    $variante->delete();
                }
            }

            $esistenti = $indumento->varianti()->get()->keyBy('id');

            // Nomi temporanei per le taglie rinominate: evita conflitti sull'indice
            // univoco (indumento, taglia) quando due taglie si scambiano il nome.
            foreach ($this->varianti as $riga) {
                $variante = $riga['id'] ? $esistenti->get($riga['id']) : null;

                if ($variante && $variante->taglia !== $riga['taglia']) {
                    $variante->update(['taglia' => "~{$variante->id}"]);
                }
            }

            foreach ($this->varianti as $posizione => $riga) {
                $dati = [
                    'taglia' => $riga['taglia'],
                    'ordinamento' => ($posizione + 1) * 10,
                    'scorta_minima' => (int) $riga['scorta_minima'],
                    'attiva' => (bool) $riga['attiva'],
                ];

                $variante = $riga['id'] ? $esistenti->get($riga['id']) : null;
                $variante ? $variante->update($dati) : $indumento->varianti()->create($dati);
            }

            return $indumento;
        });

        session()->flash('toast', [
            'type' => 'success',
            'message' => ($nuovo ? 'Indumento creato: ' : 'Indumento aggiornato: ').$indumento->nome,
        ]);

        $this->redirectRoute('catalogo.index', navigate: true);
    }

    /** @return Collection<int, CategoriaIndumento> attive, più quella attuale anche se disattivata */
    #[Computed]
    public function categorie(): Collection
    {
        return CategoriaIndumento::query()
            ->where(fn ($q) => $q->where('attiva', true)->when($this->categoria_id !== '', fn ($q) => $q->orWhere('id', (int) $this->categoria_id)))
            ->ordinate()
            ->get(['id', 'nome']);
    }

    public function render()
    {
        $titolo = $this->indumentoId ? 'Modifica indumento' : 'Nuovo indumento';

        return view('livewire.catalogo.form-indumento')
            ->title($titolo)
            ->layoutData(['breadcrumb' => [
                ['label' => 'Catalogo indumenti', 'url' => route('catalogo.index')],
                ['label' => $titolo],
            ]]);
    }

    private function haMovimenti(int $varianteId): bool
    {
        return DB::table('movimenti_magazzino')->where('variante_id', $varianteId)->exists()
            || DB::table('carico_righe')->where('variante_id', $varianteId)->exists()
            || DB::table('consegna_righe')->where('variante_id', $varianteId)->exists();
    }
}
