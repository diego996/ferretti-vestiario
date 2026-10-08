<?php

namespace App\Services;

use App\Enums\StatoDocumento;
use App\Exceptions\OperazioneNonConsentitaException;
use App\Models\Consegna;
use App\Models\ConsegnaRiga;
use App\Models\DocumentoConsegna;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;
use Throwable;

/**
 * Documento Word della consegna.
 *
 * Si genera dopo il commit della consegna (mai dentro la sua transazione):
 * un errore documentale non tocca consegna né movimenti, viene registrato e
 * si può ritentare. Una volta generato, il documento è archiviato e non viene
 * più rigenerato: il download restituisce sempre il file originale.
 */
class DocumentoConsegnaService
{
    /** Segnaposto della riga articolo, usato per clonare la riga della tabella. */
    public const SEGNAPOSTO_RIGA = 'riga_articolo';

    /**
     * Genera e archivia il documento, oppure restituisce quello già generato.
     * In caso di errore il documento resta "fallito" con il messaggio, senza eccezioni.
     *
     * @throws OperazioneNonConsentitaException consegna annullata senza documento
     */
    public function genera(Consegna $consegna): DocumentoConsegna
    {
        $documento = DocumentoConsegna::firstOrCreate(
            ['consegna_id' => $consegna->id],
            ['stato' => StatoDocumento::InAttesa, 'disco' => config('vestiario.documenti.disco')],
        );

        // Il lock evita due generazioni contemporanee dello stesso documento.
        return DB::transaction(function () use ($consegna, $documento) {
            $documento = DocumentoConsegna::query()->whereKey($documento->id)->lockForUpdate()->firstOrFail();

            if ($documento->stato === StatoDocumento::Generato) {
                return $documento;
            }

            if ($consegna->fresh()->isAnnullata()) {
                throw new OperazioneNonConsentitaException("La consegna {$consegna->numero} è annullata: il documento non si genera più.");
            }

            $documento->tentativi++;

            try {
                $documento->fill($this->scrivi($consegna) + [
                    'stato' => StatoDocumento::Generato,
                    'ultimo_errore' => null,
                    'generato_at' => now(),
                ]);
            } catch (Throwable $e) {
                Log::error('Generazione documento consegna fallita', [
                    'consegna' => $consegna->numero,
                    'tentativo' => $documento->tentativi,
                    'errore' => $e->getMessage(),
                ]);

                $documento->fill([
                    'stato' => StatoDocumento::Fallito,
                    'ultimo_errore' => Str::limit($e->getMessage(), 1000),
                ]);
            }

            $documento->save();

            return $documento;
        });
    }

    /** Nome file: Cognome_Nome_AAAA-MM-GG_Consegna_NUMERO.docx, solo caratteri sicuri. */
    public function nomeFile(Consegna $consegna): string
    {
        $parti = [
            $consegna->collega->cognome,
            $consegna->collega->nome,
            $consegna->data_consegna->format('Y-m-d'),
            'Consegna',
            $consegna->numero,
        ];

        $parti = array_filter(array_map(
            // Apostrofi eliminati (D'Angelo → DAngelo), ogni altro carattere non sicuro diventa "_".
            fn (string $parte) => trim(preg_replace('/[^A-Za-z0-9-]+/', '_', str_replace(["'", '’'], '', Str::ascii($parte))), '_'),
            $parti,
        ));

        return implode('_', $parti).'.docx';
    }

    /**
     * Valori dei segnaposto del modello (vedi docs/DOCUMENTI.md).
     *
     * @return array<string, string>
     */
    public function valori(Consegna $consegna): array
    {
        $consegna->loadMissing(['collega', 'commessa', 'operatore', 'righe']);

        return [
            'azienda' => (string) config('vestiario.company_name'),
            'numero' => $consegna->numero,
            'data' => $consegna->data_consegna->format('d/m/Y'),
            'ora' => $consegna->data_consegna->format('H:i'),
            'collega' => $consegna->collega->nominativo,
            'collega_nome' => $consegna->collega->nome,
            'collega_cognome' => $consegna->collega->cognome,
            'matricola' => $consegna->collega->matricola ?? '—',
            'commessa' => $consegna->commessa->etichetta,
            'commessa_codice' => $consegna->commessa->codice ?? '—',
            'commessa_nome' => $consegna->commessa->nome,
            'operatore' => $consegna->operatore->name,
            // Il modello ha una sola riga per le note: gli a capo diventano separatori.
            'note' => $consegna->note ? preg_replace('/\s*\R\s*/u', ' — ', $consegna->note) : '—',
            'totale_capi' => (string) $consegna->righe->sum('quantita'),
        ];
    }

    /**
     * @return array{nome_file: string, disco: string, percorso: string, template_versione: string, hash_sha256: string}
     */
    private function scrivi(Consegna $consegna): array
    {
        $modello = (string) config('vestiario.documenti.template');

        if (! is_file($modello)) {
            throw new RuntimeException("Modello Word non trovato: {$modello}");
        }

        // Caratteri come & < > nei dati non devono corrompere il documento.
        Settings::setOutputEscapingEnabled(true);

        $processore = new TemplateProcessor($modello);
        $processore->setValues($this->valori($consegna));

        $righe = $consegna->righe
            ->sortBy([['indumento_nome', 'asc'], ['id', 'asc']])
            ->values()
            ->map(fn (ConsegnaRiga $r) => [
                'riga_codice' => $r->indumento_codice ?? '—',
                self::SEGNAPOSTO_RIGA => $r->indumento_nome,
                'riga_taglia' => $r->taglia,
                'riga_quantita' => (string) $r->quantita,
            ])
            ->all();

        $processore->cloneRowAndSetValues(self::SEGNAPOSTO_RIGA, $righe);

        $mancanti = $processore->getVariables();

        if ($mancanti !== []) {
            throw new RuntimeException('Segnaposto non riconosciuti nel modello: '.implode(', ', array_unique($mancanti)));
        }

        $temporaneo = tempnam(sys_get_temp_dir(), 'consegna_');

        try {
            $processore->saveAs($temporaneo);

            $disco = (string) config('vestiario.documenti.disco');
            $nomeFile = $this->nomeFile($consegna);
            $percorso = config('vestiario.documenti.cartella')."/{$consegna->anno}/{$nomeFile}";

            if (! Storage::disk($disco)->put($percorso, file_get_contents($temporaneo))) {
                throw new RuntimeException("Impossibile archiviare il documento in {$percorso}.");
            }

            return [
                'nome_file' => $nomeFile,
                'disco' => $disco,
                'percorso' => $percorso,
                'template_versione' => Str::limit(basename($modello), 36, '').'@'.substr(hash_file('sha256', $modello), 0, 12),
                'hash_sha256' => hash_file('sha256', $temporaneo),
            ];
        } finally {
            @unlink($temporaneo);
        }
    }
}
