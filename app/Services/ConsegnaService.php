<?php

namespace App\Services;

use App\Enums\StatoConsegna;
use App\Enums\TipoMovimento;
use App\Exceptions\GiacenzaInsufficienteException;
use App\Exceptions\OperazioneNonConsentitaException;
use App\Models\Collega;
use App\Models\Commessa;
use App\Models\Consegna;
use App\Models\IndumentoVariante;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Registrazione e annullamento delle consegne.
 *
 * Creazione, numerazione, righe con snapshot e scarico avvengono in un'unica
 * transazione: o si salva tutto o niente. La giacenza è modificata solo da
 * MagazzinoService. Ordine dei lock: contatore dell'anno, poi varianti.
 */
class ConsegnaService
{
    private const TENTATIVI_TRANSAZIONE = 3;

    public function __construct(private readonly MagazzinoService $magazzino) {}

    /**
     * @param  iterable<array{variante_id: int|string, quantita: int|string}>  $righe
     * @param  string|null  $idempotencyKey  stesso valore = stessa consegna (protezione dal doppio invio)
     *
     * @throws InvalidArgumentException righe o data non valide
     * @throws OperazioneNonConsentitaException collega, commessa o taglie disattivati
     * @throws GiacenzaInsufficienteException
     */
    public function registra(
        Collega $collega,
        Commessa $commessa,
        iterable $righe,
        User $operatore,
        ?CarbonInterface $data = null,
        ?string $note = null,
        ?string $idempotencyKey = null,
    ): Consegna {
        if ($idempotencyKey && ($esistente = Consegna::where('idempotency_key', $idempotencyKey)->first())) {
            return $esistente;
        }

        $quantita = $this->magazzino->aggregaRighe($righe);

        if ($quantita === []) {
            throw new InvalidArgumentException('La consegna deve contenere almeno un articolo.');
        }

        $data ??= now();

        if ($data->isFuture()) {
            throw new InvalidArgumentException('La data della consegna non può essere futura.');
        }

        if (! $collega->attivo) {
            throw new OperazioneNonConsentitaException("Il collega {$collega->nominativo} è disattivato.");
        }

        if (! $commessa->attiva) {
            throw new OperazioneNonConsentitaException("La commessa {$commessa->etichetta} è chiusa.");
        }

        $varianti = $this->variantiConsegnabili(array_keys($quantita));

        $righeSnapshot = collect($quantita)
            ->map(fn (int $q, int $id) => [
                'variante_id' => $id,
                'quantita' => $q,
                'indumento_codice' => $varianti[$id]->indumento->codice,
                'indumento_nome' => $varianti[$id]->indumento->nome,
                'taglia' => $varianti[$id]->taglia,
            ])
            ->values()
            ->all();

        $this->predisponiContatore($data->year);

        try {
            // I tentativi ripetono la transazione solo in caso di deadlock/lock timeout.
            return DB::transaction(
                fn () => $this->crea($collega, $commessa, $righeSnapshot, $operatore, $data, $note, $idempotencyKey),
                self::TENTATIVI_TRANSAZIONE,
            );
        } catch (UniqueConstraintViolationException $e) {
            // Doppio invio concorrente: l'altra richiesta ha già salvato la consegna.
            if ($idempotencyKey && ($esistente = Consegna::where('idempotency_key', $idempotencyKey)->first())) {
                return $esistente;
            }

            throw $e;
        }
    }

    /**
     * Annulla una consegna confermata: i capi rientrano a magazzino con movimenti
     * di compensazione. Consegna, righe e documento restano nello storico.
     *
     * @throws OperazioneNonConsentitaException consegna già annullata
     */
    public function annulla(Consegna $consegna, User $operatore, string $motivo): Consegna
    {
        $motivo = trim($motivo);

        if ($motivo === '') {
            throw new InvalidArgumentException('La motivazione dell\'annullamento è obbligatoria.');
        }

        return DB::transaction(function () use ($consegna, $operatore, $motivo) {
            $bloccata = Consegna::query()->whereKey($consegna->id)->lockForUpdate()->firstOrFail();

            if ($bloccata->isAnnullata()) {
                throw new OperazioneNonConsentitaException("La consegna {$bloccata->numero} è già stata annullata.");
            }

            $bloccata->stato = StatoConsegna::Annullata;
            $bloccata->annullata_at = now();
            $bloccata->annullata_da = $operatore->id;
            $bloccata->motivo_annullamento = $motivo;
            $bloccata->save();

            $this->magazzino->registraMovimenti(
                TipoMovimento::CompensazioneAnnullamento,
                $bloccata->righe()->get(['variante_id', 'quantita'])->toArray(),
                $operatore,
                $bloccata,
                $bloccata->annullata_at,
                "Annullamento consegna {$bloccata->numero}: {$motivo}",
            );

            return $bloccata;
        });
    }

    /** @param  list<array<string, mixed>>  $righe */
    private function crea(Collega $collega, Commessa $commessa, array $righe, User $operatore, CarbonInterface $data, ?string $note, ?string $idempotencyKey): Consegna
    {
        $anno = $data->year;

        // Lock sulla riga del contatore: serializza l'assegnazione del progressivo.
        // Se la transazione fallisce (es. giacenza insufficiente) l'incremento si annulla: nessun buco.
        $ultimo = (int) DB::table('numerazione_consegne')->where('anno', $anno)->lockForUpdate()->value('ultimo_progressivo');
        DB::table('numerazione_consegne')->where('anno', $anno)->update(['ultimo_progressivo' => $ultimo + 1, 'updated_at' => now()]);

        $consegna = new Consegna([
            'collega_id' => $collega->id,
            'commessa_id' => $commessa->id,
            'data_consegna' => $data,
            'user_id' => $operatore->id,
            'note' => filled($note) ? trim($note) : null,
        ]);
        $consegna->anno = $anno;
        $consegna->progressivo = $ultimo + 1;
        $consegna->numero = Consegna::formattaNumero($anno, $ultimo + 1);
        $consegna->stato = StatoConsegna::Confermata;
        $consegna->idempotency_key = $idempotencyKey;
        $consegna->save();

        $consegna->righe()->createMany($righe);

        // Verifica la disponibilità sui valori bloccati: se manca qualcosa la transazione si annulla.
        $this->magazzino->registraMovimenti(TipoMovimento::ScaricoConsegna, $righe, $operatore, $consegna, $data);

        return $consegna;
    }

    /**
     * Crea la riga del contatore dell'anno, se manca, fuori dalla transazione
     * principale (così la transazione prende solo un lock esclusivo di riga).
     * Parte dal massimo progressivo esistente, per dati importati o pregressi.
     */
    private function predisponiContatore(int $anno): void
    {
        if (DB::table('numerazione_consegne')->where('anno', $anno)->exists()) {
            return;
        }

        DB::table('numerazione_consegne')->insertOrIgnore([
            'anno' => $anno,
            'ultimo_progressivo' => (int) Consegna::where('anno', $anno)->max('progressivo'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  list<int>  $ids
     * @return \Illuminate\Support\Collection<int, IndumentoVariante>
     */
    private function variantiConsegnabili(array $ids)
    {
        $varianti = IndumentoVariante::with('indumento:id,codice,nome,attivo')->whereIn('id', $ids)->get()->keyBy('id');

        if ($varianti->count() !== count($ids)) {
            throw new InvalidArgumentException('Una o più taglie non esistono.');
        }

        $disattivate = $varianti->filter(fn (IndumentoVariante $v) => ! $v->attiva || ! $v->indumento->attivo);

        if ($disattivate->isNotEmpty()) {
            $elenco = $disattivate->map(fn (IndumentoVariante $v) => "{$v->indumento->nome} ({$v->taglia})")->implode(', ');

            throw new OperazioneNonConsentitaException("Taglie non più consegnabili: {$elenco}.");
        }

        return $varianti;
    }
}
