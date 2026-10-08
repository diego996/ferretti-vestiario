<?php

namespace App\Livewire;

use App\Models\Collega;
use App\Models\Consegna;
use App\Models\ConsegnaRiga;
use App\Models\IndumentoVariante;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    public const LIMITE_CONSEGNE = 10;

    public const LIMITE_SCORTE = 8;

    /** Unità disponibili su varianti e indumenti attivi. */
    #[Computed]
    public function unitaDisponibili(): int
    {
        return (int) IndumentoVariante::query()
            ->attive()
            ->whereHas('indumento', fn (Builder $q) => $q->where('attivo', true))
            ->sum('giacenza');
    }

    /** @return array{consegne: int, unita: int} */
    #[Computed]
    public function meseCorrente(): array
    {
        $periodo = [now()->startOfMonth(), now()->endOfMonth()];

        return [
            'consegne' => Consegna::confermate()->whereBetween('data_consegna', $periodo)->count(),
            'unita' => (int) ConsegnaRiga::query()
                ->whereHas('consegna', fn (Builder $q) => $q->confermate()->whereBetween('data_consegna', $periodo))
                ->sum('quantita'),
        ];
    }

    /** @return array{totale: int, esaurite: int} */
    #[Computed]
    public function sottoScorta(): array
    {
        return [
            'totale' => IndumentoVariante::daRiordinare()->count(),
            'esaurite' => IndumentoVariante::daRiordinare()->where('giacenza', 0)->count(),
        ];
    }

    /** @return array{totale: int, attivi: int} */
    #[Computed]
    public function colleghi(): array
    {
        return [
            'totale' => Collega::count(),
            'attivi' => Collega::attivi()->count(),
        ];
    }

    /** @return Collection<int, Consegna> */
    #[Computed]
    public function ultimeConsegne(): Collection
    {
        return Consegna::query()
            ->with(['collega:id,nome,cognome', 'commessa:id,codice,nome'])
            ->withSum('righe as totale_unita', 'quantita')
            ->latest('data_consegna')
            ->latest('id')
            ->limit(self::LIMITE_CONSEGNE)
            ->get();
    }

    /** @return Collection<int, IndumentoVariante> */
    #[Computed]
    public function scorteBasse(): Collection
    {
        return IndumentoVariante::daRiordinare()
            ->with('indumento:id,codice,nome')
            ->orderBy('giacenza')
            ->orderByRaw('scorta_minima - giacenza DESC')
            ->limit(self::LIMITE_SCORTE)
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
