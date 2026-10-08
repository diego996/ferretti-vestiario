<?php

namespace App\Livewire\Catalogo;

use App\Livewire\Concerns\ConPaginazione;
use App\Models\CategoriaIndumento;
use App\Models\Indumento;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Catalogo indumenti')]
class Index extends Component
{
    use ConPaginazione;

    #[Url(as: 'q', except: '')]
    public string $cerca = '';

    #[Url(except: '')]
    public string $categoria = '';

    /** attivi | disattivati | tutti */
    #[Url(except: 'attivi')]
    public string $stato = 'attivi';

    public function azzeraFiltri(): void
    {
        $this->reset('cerca', 'categoria', 'stato');
        $this->resetPage();
    }

    #[Computed]
    public function indumenti(): LengthAwarePaginator
    {
        return Indumento::query()
            ->cerca($this->cerca)
            ->when($this->categoria !== '', fn (Builder $q) => $q->where('categoria_id', (int) $this->categoria))
            ->when($this->stato === 'attivi', fn (Builder $q) => $q->where('attivo', true))
            ->when($this->stato === 'disattivati', fn (Builder $q) => $q->where('attivo', false))
            ->with(['categoria:id,nome', 'varianti:id,indumento_id,taglia,attiva,ordinamento'])
            ->withSum('varianti as giacenza_totale', 'giacenza')
            ->orderBy('nome')
            ->paginate(self::PER_PAGINA);
    }

    /** @return Collection<int, CategoriaIndumento> */
    #[Computed]
    public function categorie(): Collection
    {
        return CategoriaIndumento::ordinate()->get(['id', 'nome']);
    }

    public function render()
    {
        return view('livewire.catalogo.index');
    }
}
