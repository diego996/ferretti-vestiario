<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Voci della sidebar. Una voce senza rotta registrata viene mostrata
 * disabilitata con l'indicazione della fase in cui sarà disponibile:
 * nessun link fittizio presentato come operativo.
 */
class Navigazione
{
    /**
     * @return list<array{label: string, icon: string, route: string, pattern: string, fase: int, disponibile: bool}>
     */
    public static function voci(): array
    {
        $voci = [
            ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'route' => 'dashboard', 'pattern' => 'dashboard', 'fase' => 1],
            ['label' => 'Magazzino', 'icon' => 'warehouse', 'route' => 'magazzino.index', 'pattern' => 'magazzino.*', 'fase' => 2],
            ['label' => 'Carico merce', 'icon' => 'package-plus', 'route' => 'carichi.create', 'pattern' => 'carichi.*', 'fase' => 2],
            ['label' => 'Consegne', 'icon' => 'clipboard-check', 'route' => 'consegne.index', 'pattern' => 'consegne.*', 'fase' => 3],
            ['label' => 'Colleghi', 'icon' => 'users', 'route' => 'colleghi.index', 'pattern' => 'colleghi.*', 'fase' => 2],
            ['label' => 'Commesse', 'icon' => 'folder-kanban', 'route' => 'commesse.index', 'pattern' => 'commesse.*', 'fase' => 2],
            ['label' => 'Catalogo indumenti', 'icon' => 'shirt', 'route' => 'catalogo.index', 'pattern' => 'catalogo.*', 'fase' => 2],
            ['label' => 'Storico movimenti', 'icon' => 'history', 'route' => 'movimenti.index', 'pattern' => 'movimenti.*', 'fase' => 2],
            ['label' => 'Impostazioni', 'icon' => 'settings', 'route' => 'impostazioni', 'pattern' => 'impostazioni*', 'fase' => 5],
        ];

        return array_map(
            fn (array $voce) => $voce + ['disponibile' => Route::has($voce['route'])],
            $voci,
        );
    }
}
