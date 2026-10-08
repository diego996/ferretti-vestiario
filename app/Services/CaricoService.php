<?php

namespace App\Services;

use App\Enums\TipoMovimento;
use App\Models\Carico;
use App\Models\IndumentoVariante;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Registrazione dei carichi di merce.
 *
 * Crea testata e righe (aggregate per variante) e delega a MagazzinoService
 * movimenti e giacenze, tutto nella stessa transazione.
 */
class CaricoService
{
    public function __construct(private readonly MagazzinoService $magazzino) {}

    /**
     * @param  iterable<array{variante_id: int|string, quantita: int|string}>  $righe
     *
     * @throws InvalidArgumentException righe non valide, varianti inattive o data futura
     */
    public function registra(iterable $righe, User $operatore, ?CarbonInterface $data = null, ?string $note = null): Carico
    {
        $quantita = $this->magazzino->aggregaRighe($righe);

        if ($quantita === []) {
            throw new InvalidArgumentException('Il carico deve contenere almeno una riga.');
        }

        $data ??= now();

        if ($data->isFuture()) {
            throw new InvalidArgumentException('La data del carico non può essere futura.');
        }

        $this->verificaVariantiAttive(array_keys($quantita));

        $righeAggregate = collect($quantita)
            ->map(fn (int $q, int $varianteId) => ['variante_id' => $varianteId, 'quantita' => $q])
            ->values()
            ->all();

        return DB::transaction(function () use ($righeAggregate, $operatore, $data, $note) {
            $carico = Carico::create([
                'data_carico' => $data,
                'user_id' => $operatore->id,
                'note' => filled($note) ? trim($note) : null,
            ]);

            $carico->righe()->createMany($righeAggregate);

            $this->magazzino->registraMovimenti(TipoMovimento::Carico, $righeAggregate, $operatore, $carico, $data);

            return $carico;
        });
    }

    /** @param  list<int>  $ids */
    private function verificaVariantiAttive(array $ids): void
    {
        $attive = IndumentoVariante::query()
            ->whereIn('id', $ids)
            ->where('attiva', true)
            ->whereHas('indumento', fn ($q) => $q->where('attivo', true))
            ->count();

        if ($attive !== count($ids)) {
            throw new InvalidArgumentException('Si possono caricare solo taglie attive di indumenti attivi.');
        }
    }
}
