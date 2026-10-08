<?php

namespace App\Livewire\Catalogo;

use App\Models\CategoriaIndumento;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Categorie')]
class Categorie extends Component
{
    /** Categoria in modifica nel drawer; null = nuova. */
    public ?int $modificaId = null;

    public string $nome = '';

    public string $descrizione = '';

    public string $ordinamento = '0';

    public bool $attiva = true;

    public function nuova(): void
    {
        $this->reset('modificaId', 'nome', 'descrizione', 'attiva');
        $this->ordinamento = (string) (((int) CategoriaIndumento::max('ordinamento')) + 10);
        $this->resetValidation();
    }

    public function modifica(int $id): void
    {
        $categoria = CategoriaIndumento::findOrFail($id);

        $this->modificaId = $categoria->id;
        $this->nome = $categoria->nome;
        $this->descrizione = (string) $categoria->descrizione;
        $this->ordinamento = (string) $categoria->ordinamento;
        $this->attiva = $categoria->attiva;
        $this->resetValidation();
    }

    public function salva(): void
    {
        $this->nome = trim($this->nome);
        $this->descrizione = trim($this->descrizione);

        $dati = $this->validate([
            'nome' => ['required', 'string', 'max:100', Rule::unique('categorie_indumenti', 'nome')->ignore($this->modificaId)],
            'descrizione' => ['nullable', 'string', 'max:255'],
            'ordinamento' => ['required', 'integer', 'min:0', 'max:65535'],
            'attiva' => ['boolean'],
        ]);
        $dati['descrizione'] = $dati['descrizione'] ?: null;

        $categoria = $this->modificaId
            ? tap(CategoriaIndumento::findOrFail($this->modificaId))->update($dati)
            : CategoriaIndumento::create($dati);

        unset($this->categorie);
        $this->dispatch('close-drawer', id: 'categoria');
        $this->dispatch('toast', type: 'success', message: ($this->modificaId ? 'Categoria aggiornata: ' : 'Categoria aggiunta: ').$categoria->nome);
        $this->reset('modificaId', 'nome', 'descrizione', 'attiva');
    }

    /** @return Collection<int, CategoriaIndumento> */
    #[Computed]
    public function categorie(): Collection
    {
        return CategoriaIndumento::query()
            ->withCount('indumenti')
            ->ordinate()
            ->get();
    }

    public function render()
    {
        return view('livewire.catalogo.categorie')
            ->layoutData(['breadcrumb' => [
                ['label' => 'Catalogo indumenti', 'url' => route('catalogo.index')],
                ['label' => 'Categorie'],
            ]]);
    }
}
