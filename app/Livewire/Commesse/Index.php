<?php

namespace App\Livewire\Commesse;

use App\Enums\StatoConsegna;
use App\Livewire\Concerns\ConPaginazione;
use App\Models\Commessa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Commesse')]
class Index extends Component
{
    use ConPaginazione;

    #[Url(as: 'q', except: '')]
    public string $cerca = '';

    /** attive | chiuse | tutte */
    #[Url(except: 'attive')]
    public string $stato = 'attive';

    /** Commessa in modifica nel drawer; null = nuova. */
    public ?int $modificaId = null;

    public string $codice = '';

    public string $nome = '';

    public string $descrizione = '';

    public bool $attiva = true;

    public function nuova(): void
    {
        $this->reset('modificaId', 'codice', 'nome', 'descrizione', 'attiva');
        $this->resetValidation();
    }

    public function modifica(int $id): void
    {
        $commessa = Commessa::findOrFail($id);

        $this->modificaId = $commessa->id;
        $this->codice = (string) $commessa->codice;
        $this->nome = $commessa->nome;
        $this->descrizione = (string) $commessa->descrizione;
        $this->attiva = $commessa->attiva;
        $this->resetValidation();
    }

    public function salva(): void
    {
        $this->codice = trim($this->codice);
        $this->nome = trim($this->nome);
        $this->descrizione = trim($this->descrizione);

        $dati = $this->validate([
            'codice' => ['nullable', 'string', 'max:50', Rule::unique('commesse', 'codice')->ignore($this->modificaId)],
            'nome' => ['required', 'string', 'max:150'],
            'descrizione' => ['nullable', 'string', 'max:1000'],
            'attiva' => ['boolean'],
        ]);
        $dati['codice'] = $dati['codice'] ?: null;
        $dati['descrizione'] = $dati['descrizione'] ?: null;

        $commessa = $this->modificaId
            ? tap(Commessa::findOrFail($this->modificaId))->update($dati)
            : Commessa::create($dati);

        $this->dispatch('close-drawer', id: 'commessa');
        $this->dispatch('toast', type: 'success', message: ($this->modificaId ? 'Commessa aggiornata: ' : 'Commessa aggiunta: ').$commessa->etichetta);
        $this->nuova();
    }

    public function azzeraFiltri(): void
    {
        $this->reset('cerca', 'stato');
        $this->resetPage();
    }

    #[Computed]
    public function commesse(): LengthAwarePaginator
    {
        return Commessa::query()
            ->cerca($this->cerca)
            ->when($this->stato === 'attive', fn (Builder $q) => $q->where('attiva', true))
            ->when($this->stato === 'chiuse', fn (Builder $q) => $q->where('attiva', false))
            ->withCount(['consegne' => fn (Builder $q) => $q->where('stato', StatoConsegna::Confermata)])
            ->withMax(['consegne as ultima_consegna' => fn (Builder $q) => $q->where('stato', StatoConsegna::Confermata)], 'data_consegna')
            ->orderByDesc('attiva')
            ->orderBy('nome')
            ->paginate(self::PER_PAGINA);
    }

    public function render()
    {
        return view('livewire.commesse.index');
    }
}
