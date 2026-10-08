<?php

namespace App\Services;

use App\Enums\TipoMovimento;
use App\Exceptions\GiacenzaInsufficienteException;
use App\Models\IndumentoVariante;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Unico punto in cui la giacenza delle varianti viene modificata.
 *
 * Ogni variazione produce un movimento e aggiorna il saldo nella stessa
 * transazione, con lock pessimistico sulle varianti coinvolte acquisito in
 * ordine di ID crescente (evita deadlock tra operazioni concorrenti).
 */
class MagazzinoService
{
    /**
     * Registra movimenti dello stesso tipo su una o più varianti.
     *
     * @param  iterable<array{variante_id: int|string, quantita: int|string}>  $righe  quantità sempre positive: il segno è dato dal tipo
     * @return Collection<int, MovimentoMagazzino>
     *
     * @throws GiacenzaInsufficienteException
     */
    public function registraMovimenti(
        TipoMovimento $tipo,
        iterable $righe,
        User $operatore,
        ?Model $origine = null,
        ?CarbonInterface $data = null,
        ?string $note = null,
    ): Collection {
        $quantita = $this->aggregaRighe($righe);

        if ($quantita === []) {
            throw new InvalidArgumentException('Nessuna riga da registrare.');
        }

        $data ??= now();

        return DB::transaction(function () use ($tipo, $quantita, $operatore, $origine, $data, $note) {
            $varianti = $this->bloccaVarianti(array_keys($quantita));

            if ($tipo->segno() < 0) {
                $this->verificaDisponibilita($varianti, $quantita);
            }

            $movimenti = collect();

            foreach ($varianti as $variante) {
                $delta = $tipo->segno() * $quantita[$variante->id];
                $nuovaGiacenza = $variante->giacenza + $delta;

                // Query diretta: il model impedisce modifiche alla giacenza fuori da questo service.
                IndumentoVariante::query()->whereKey($variante->id)->update(['giacenza' => $nuovaGiacenza]);

                $movimenti->push(MovimentoMagazzino::create([
                    'variante_id' => $variante->id,
                    'tipo' => $tipo,
                    'quantita' => $delta,
                    'giacenza_risultante' => $nuovaGiacenza,
                    'origine_type' => $origine?->getMorphClass(),
                    'origine_id' => $origine?->getKey(),
                    'user_id' => $operatore->id,
                    'data_movimento' => $data,
                    'note' => $note,
                ]));
            }

            return $movimenti;
        });
    }

    /**
     * Rettifica inventariale di una singola variante. La motivazione è obbligatoria.
     *
     * @param  int  $delta  positivo per aumentare, negativo per ridurre
     */
    public function rettifica(IndumentoVariante $variante, int $delta, User $operatore, string $motivazione): MovimentoMagazzino
    {
        if ($delta === 0) {
            throw new InvalidArgumentException('La rettifica deve avere una quantità diversa da zero.');
        }

        if (trim($motivazione) === '') {
            throw new InvalidArgumentException('La motivazione della rettifica è obbligatoria.');
        }

        $tipo = $delta > 0 ? TipoMovimento::RettificaPositiva : TipoMovimento::RettificaNegativa;

        return $this->registraMovimenti(
            $tipo,
            [['variante_id' => $variante->id, 'quantita' => abs($delta)]],
            $operatore,
            note: trim($motivazione),
        )->first();
    }

    /**
     * Somma le righe con la stessa variante e valida le quantità.
     *
     * @return array<int, int> variante_id => quantità totale, ordinato per ID
     */
    public function aggregaRighe(iterable $righe): array
    {
        $totali = [];

        foreach ($righe as $riga) {
            $varianteId = filter_var($riga['variante_id'] ?? null, FILTER_VALIDATE_INT);
            $quantita = filter_var($riga['quantita'] ?? null, FILTER_VALIDATE_INT);

            if ($varianteId === false || $varianteId <= 0) {
                throw new InvalidArgumentException('Variante non valida.');
            }

            if ($quantita === false || $quantita <= 0) {
                throw new InvalidArgumentException('Le quantità devono essere numeri interi maggiori di zero.');
            }

            $totali[$varianteId] = ($totali[$varianteId] ?? 0) + $quantita;
        }

        ksort($totali);

        return $totali;
    }

    /**
     * Varianti la cui giacenza non coincide con la somma dei movimenti.
     *
     * @return Collection<int, object{id: int, giacenza: int, saldo_movimenti: int}>
     */
    public function discrepanze(): Collection
    {
        $saldi = DB::table('movimenti_magazzino')
            ->select('variante_id', DB::raw('SUM(quantita) as saldo'))
            ->groupBy('variante_id');

        return DB::table('indumento_varianti as v')
            ->leftJoinSub($saldi, 's', 's.variante_id', '=', 'v.id')
            ->select('v.id', 'v.giacenza', DB::raw('COALESCE(s.saldo, 0) as saldo_movimenti'))
            ->whereRaw('v.giacenza <> COALESCE(s.saldo, 0)')
            ->orderBy('v.id')
            ->get()
            ->map(function (object $riga) {
                $riga->giacenza = (int) $riga->giacenza;
                $riga->saldo_movimenti = (int) $riga->saldo_movimenti;

                return $riga;
            });
    }

    /**
     * @param  array<int, int>  $ids  già ordinati
     * @return Collection<int, IndumentoVariante>
     */
    private function bloccaVarianti(array $ids): Collection
    {
        $varianti = IndumentoVariante::query()
            ->with('indumento:id,nome,codice')
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($varianti->count() !== count($ids)) {
            throw new InvalidArgumentException('Una o più varianti non esistono.');
        }

        return $varianti;
    }

    /**
     * @param  Collection<int, IndumentoVariante>  $varianti
     * @param  array<int, int>  $quantita
     */
    private function verificaDisponibilita(Collection $varianti, array $quantita): void
    {
        $mancanze = $varianti
            ->filter(fn (IndumentoVariante $v) => $v->giacenza < $quantita[$v->id])
            ->map(fn (IndumentoVariante $v) => [
                'variante_id' => $v->id,
                'descrizione' => "{$v->indumento->nome} (taglia {$v->taglia})",
                'disponibile' => $v->giacenza,
                'richiesta' => $quantita[$v->id],
            ])
            ->values()
            ->all();

        if ($mancanze !== []) {
            throw new GiacenzaInsufficienteException($mancanze);
        }
    }
}
