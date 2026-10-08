<?php

namespace App\Livewire\Carichi;

use App\Models\Carico;
use App\Models\Indumento;
use App\Services\CaricoService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Carico merce')]
class NuovoCarico extends Component
{
    public const LIMITE_ULTIMI = 8;

    public const MAX_QUANTITA = 10000;

    public string $dataCarico = '';

    public string $note = '';

    /** @var list<array{indumento_id: string, variante_id: string, quantita: string}> */
    public array $righe = [];

    public function mount(): void
    {
        $this->nuovoModulo();
    }

    public function aggiungiRiga(): void
    {
        $this->righe[] = ['indumento_id' => '', 'variante_id' => '', 'quantita' => ''];
    }

    public function rimuoviRiga(int $indice): void
    {
        unset($this->righe[$indice]);
        $this->righe = array_values($this->righe);
        $this->resetValidation();

        if ($this->righe === []) {
            $this->aggiungiRiga();
        }
    }

    /** Cambiando indumento la taglia scelta non è più valida. */
    public function updatedRighe(mixed $valore, string $chiave): void
    {
        if (str_ends_with($chiave, '.indumento_id')) {
            $this->righe[(int) $chiave]['variante_id'] = '';
        }
    }

    public function conferma(CaricoService $carichi): void
    {
        $this->validate(...$this->regole());

        try {
            $carico = $carichi->registra(
                collect($this->righe)->map(fn (array $r) => ['variante_id' => $r['variante_id'], 'quantita' => $r['quantita']]),
                auth()->user(),
                Carbon::parse($this->dataCarico),
                $this->note,
            );
        } catch (InvalidArgumentException $e) {
            $this->addError('righe', $e->getMessage());

            return;
        }

        $unita = $carico->righe->sum('quantita');
        $this->dispatch('toast', type: 'success', message: "Carico registrato: {$unita} unità su {$carico->righe->count()} taglie.");

        $this->nuovoModulo();
        unset($this->catalogo, $this->ultimiCarichi);
    }

    /**
     * Indumenti attivi con le sole taglie attive, raggruppabili per categoria.
     *
     * @return Collection<int, Indumento>
     */
    #[Computed]
    public function catalogo(): Collection
    {
        return Indumento::query()
            ->attivi()
            ->whereHas('varianti', fn ($q) => $q->where('attiva', true))
            ->with([
                'categoria:id,nome,ordinamento',
                'varianti' => fn ($q) => $q->where('attiva', true),
            ])
            ->orderBy('nome')
            ->get()
            ->sortBy(fn (Indumento $i) => [$i->categoria->ordinamento, $i->categoria->nome])
            ->keyBy('id');
    }

    /** @return Collection<int, Carico> */
    #[Computed]
    public function ultimiCarichi(): Collection
    {
        return Carico::query()
            ->with('operatore:id,name')
            ->withCount('righe')
            ->withSum('righe as totale_unita', 'quantita')
            ->latest('data_carico')
            ->latest('id')
            ->limit(self::LIMITE_ULTIMI)
            ->get();
    }

    /** @return array{int, int} righe compilate e unità totali */
    #[Computed]
    public function riepilogo(): array
    {
        $compilate = collect($this->righe)->filter(fn (array $r) => $r['variante_id'] !== '' && (int) $r['quantita'] > 0);

        return [$compilate->count(), (int) $compilate->sum(fn (array $r) => (int) $r['quantita'])];
    }

    public function render()
    {
        return view('livewire.carichi.nuovo-carico');
    }

    private function nuovoModulo(): void
    {
        $this->dataCarico = now()->format('Y-m-d\TH:i');
        $this->note = '';
        $this->righe = [];
        $this->aggiungiRiga();
        $this->resetValidation();
    }

    /** @return array{array<string, mixed>, array<string, string>, array<string, string>} */
    private function regole(): array
    {
        $varianteValida = Rule::exists('indumento_varianti', 'id')->where('attiva', true);

        return [
            [
                'dataCarico' => ['required', 'date', 'before_or_equal:now'],
                'note' => ['nullable', 'string', 'max:1000'],
                'righe' => ['required', 'array', 'min:1'],
                'righe.*.indumento_id' => ['required'],
                'righe.*.variante_id' => ['required', 'integer', $varianteValida],
                'righe.*.quantita' => ['required', 'integer', 'min:1', 'max:'.self::MAX_QUANTITA],
            ],
            [
                'dataCarico.before_or_equal' => 'La data del carico non può essere futura.',
                'righe.*.indumento_id.required' => 'Scegli l\'indumento.',
                'righe.*.variante_id.required' => 'Scegli la taglia.',
                'righe.*.variante_id.exists' => 'Taglia non disponibile.',
            ],
            [
                'dataCarico' => 'data del carico',
                'righe.*.quantita' => 'quantità',
            ],
        ];
    }
}
