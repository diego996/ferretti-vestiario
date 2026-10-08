<?php

namespace App\Livewire\Magazzino;

use App\Enums\StatoScorta;
use App\Exceptions\GiacenzaInsufficienteException;
use App\Livewire\Concerns\ConPaginazione;
use App\Models\CategoriaIndumento;
use App\Models\IndumentoVariante;
use App\Models\MovimentoMagazzino;
use App\Services\MagazzinoService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Magazzino')]
class Index extends Component
{
    use ConPaginazione;

    public const LIMITE_MOVIMENTI = 30;

    #[Url(as: 'q', except: '')]
    public string $cerca = '';

    #[Url(except: '')]
    public string $categoria = '';

    #[Url(except: '')]
    public string $stato = '';

    #[Url(except: false)]
    public bool $inattivi = false;

    /** Variante aperta nel drawer. */
    public ?int $varianteId = null;

    public string $rettificaVerso = 'aumento';

    public string $rettificaQuantita = '';

    public string $rettificaMotivo = '';

    public function apriVariante(int $id): void
    {
        $this->varianteId = $id;
        $this->reset('rettificaVerso', 'rettificaQuantita', 'rettificaMotivo');
        $this->resetValidation();
        unset($this->variante, $this->movimenti);
    }

    public function registraRettifica(MagazzinoService $magazzino): void
    {
        $variante = $this->variante;

        if (! $variante) {
            return;
        }

        $this->validate([
            'rettificaVerso' => ['required', Rule::in(['aumento', 'riduzione'])],
            'rettificaQuantita' => ['required', 'integer', 'min:1', 'max:10000'],
            'rettificaMotivo' => ['required', 'string', 'min:3', 'max:500'],
        ], attributes: [
            'rettificaQuantita' => 'quantità',
            'rettificaMotivo' => 'motivazione',
        ]);

        $delta = (int) $this->rettificaQuantita * ($this->rettificaVerso === 'riduzione' ? -1 : 1);

        try {
            $movimento = $magazzino->rettifica($variante, $delta, auth()->user(), $this->rettificaMotivo);
        } catch (GiacenzaInsufficienteException $e) {
            $disponibile = $e->mancanze[0]['disponibile'] ?? 0;
            $this->addError('rettificaQuantita', "Non puoi ridurre di più della giacenza attuale ({$disponibile}).");

            return;
        }

        $this->reset('rettificaVerso', 'rettificaQuantita', 'rettificaMotivo');
        unset($this->variante, $this->movimenti);

        $this->dispatch('toast', type: 'success', message: "Rettifica registrata. Nuova giacenza: {$movimento->giacenza_risultante}.");
    }

    public function azzeraFiltri(): void
    {
        $this->reset('cerca', 'categoria', 'stato', 'inattivi');
        $this->resetPage();
    }

    #[Computed]
    public function varianti(): LengthAwarePaginator
    {
        $query = $this->queryFiltrata();
        $statoScelto = StatoScorta::tryFrom($this->stato);

        if ($statoScelto) {
            $query->conStato($statoScelto);
        }

        return $query
            ->with('indumento.categoria:id,nome')
            ->orderBy('indumenti.nome')
            ->orderBy('indumento_varianti.ordinamento')
            ->orderBy('indumento_varianti.taglia')
            ->paginate(self::PER_PAGINA);
    }

    /**
     * Conteggi per stato con gli altri filtri applicati (chip di filtro rapido).
     *
     * @return array<string, int>
     */
    #[Computed]
    public function conteggi(): array
    {
        $conteggi = ['' => $this->queryFiltrata()->count()];

        foreach (StatoScorta::cases() as $stato) {
            $conteggi[$stato->value] = $this->queryFiltrata()->conStato($stato)->count();
        }

        return $conteggi;
    }

    /** @return Collection<int, CategoriaIndumento> */
    #[Computed]
    public function categorie(): Collection
    {
        return CategoriaIndumento::ordinate()->get(['id', 'nome']);
    }

    #[Computed]
    public function variante(): ?IndumentoVariante
    {
        return $this->varianteId
            ? IndumentoVariante::with('indumento.categoria:id,nome')->find($this->varianteId)
            : null;
    }

    /** @return Collection<int, MovimentoMagazzino> */
    #[Computed]
    public function movimenti(): Collection
    {
        if (! $this->varianteId) {
            return collect();
        }

        return MovimentoMagazzino::query()
            ->where('variante_id', $this->varianteId)
            ->with(['operatore:id,name', 'origine'])
            ->latest('data_movimento')
            ->latest('id')
            ->limit(self::LIMITE_MOVIMENTI)
            ->get();
    }

    public function render()
    {
        return view('livewire.magazzino.index');
    }

    private function queryFiltrata(): Builder
    {
        return IndumentoVariante::query()
            ->select('indumento_varianti.*')
            ->join('indumenti', 'indumenti.id', '=', 'indumento_varianti.indumento_id')
            ->unless($this->inattivi, fn (Builder $q) => $q
                ->where('indumento_varianti.attiva', true)
                ->where('indumenti.attivo', true))
            ->when($this->categoria !== '', fn (Builder $q) => $q->where('indumenti.categoria_id', (int) $this->categoria))
            ->when(trim($this->cerca) !== '', fn (Builder $q) => $q->whereHas('indumento', fn (Builder $i) => $i->cerca($this->cerca)));
    }
}
