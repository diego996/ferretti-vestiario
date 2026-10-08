<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;
use Livewire\WithPagination;
use ReflectionProperty;

/**
 * Paginazione Livewire con la vista condivisa dell'applicazione.
 *
 * I filtri di una pagina sono le proprietà con #[Url]: quando uno di essi
 * cambia si torna alla prima pagina. Le altre proprietà (form nei drawer)
 * non toccano la paginazione.
 */
trait ConPaginazione
{
    use WithPagination;

    public const PER_PAGINA = 50;

    public function paginationView(): string
    {
        return 'livewire.partials.paginazione';
    }

    public function updatedConPaginazione(string $percorso): void
    {
        $proprieta = strtok($percorso, '.');

        if (property_exists($this, $proprieta)
            && (new ReflectionProperty($this, $proprieta))->getAttributes(Url::class) !== []) {
            $this->resetPage();
        }
    }
}
