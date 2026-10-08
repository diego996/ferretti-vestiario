<?php

namespace App\Livewire\Colleghi;

use App\Enums\StatoConsegna;
use App\Livewire\Concerns\ConPaginazione;
use App\Models\Collega;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Colleghi')]
class Index extends Component
{
    use ConPaginazione;

    #[Url(as: 'q', except: '')]
    public string $cerca = '';

    /** attivi | disattivati | tutti */
    #[Url(except: 'attivi')]
    public string $stato = 'attivi';

    /** Collega in modifica nel drawer; null = nuovo. */
    public ?int $modificaId = null;

    public string $nome = '';

    public string $cognome = '';

    public string $matricola = '';

    public bool $attivo = true;

    public function nuovo(): void
    {
        $this->reset('modificaId', 'nome', 'cognome', 'matricola', 'attivo');
        $this->resetValidation();
    }

    public function modifica(int $id): void
    {
        $collega = Collega::findOrFail($id);

        $this->modificaId = $collega->id;
        $this->nome = $collega->nome;
        $this->cognome = $collega->cognome;
        $this->matricola = (string) $collega->matricola;
        $this->attivo = $collega->attivo;
        $this->resetValidation();
    }

    public function salva(): void
    {
        $this->nome = trim($this->nome);
        $this->cognome = trim($this->cognome);
        $this->matricola = trim($this->matricola);

        $dati = $this->validate([
            'cognome' => ['required', 'string', 'max:100'],
            'nome' => ['required', 'string', 'max:100'],
            'matricola' => ['nullable', 'string', 'max:50', Rule::unique('colleghi', 'matricola')->ignore($this->modificaId)],
            'attivo' => ['boolean'],
        ]);
        $dati['matricola'] = $dati['matricola'] ?: null;

        $collega = $this->modificaId
            ? tap(Collega::findOrFail($this->modificaId))->update($dati)
            : Collega::create($dati);

        $this->dispatch('close-drawer', id: 'collega');
        $this->dispatch('toast', type: 'success', message: ($this->modificaId ? 'Collega aggiornato: ' : 'Collega aggiunto: ').$collega->nominativo);
        $this->nuovo();
    }

    public function azzeraFiltri(): void
    {
        $this->reset('cerca', 'stato');
        $this->resetPage();
    }

    #[Computed]
    public function colleghi(): LengthAwarePaginator
    {
        return Collega::query()
            ->cerca($this->cerca)
            ->when($this->stato === 'attivi', fn (Builder $q) => $q->where('attivo', true))
            ->when($this->stato === 'disattivati', fn (Builder $q) => $q->where('attivo', false))
            ->withCount(['consegne' => fn (Builder $q) => $q->where('stato', StatoConsegna::Confermata)])
            ->withMax(['consegne as ultima_consegna' => fn (Builder $q) => $q->where('stato', StatoConsegna::Confermata)], 'data_consegna')
            ->ordinati()
            ->paginate(self::PER_PAGINA);
    }

    public function render()
    {
        return view('livewire.colleghi.index');
    }
}
