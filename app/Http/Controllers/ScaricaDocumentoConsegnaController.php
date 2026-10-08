<?php

namespace App\Http\Controllers;

use App\Enums\StatoDocumento;
use App\Models\Consegna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Download del documento archiviato. Non rigenera mai il file: restituisce
 * l'originale salvato al momento della consegna.
 */
class ScaricaDocumentoConsegnaController extends Controller
{
    public function __invoke(Consegna $consegna): StreamedResponse|RedirectResponse
    {
        $documento = $consegna->documento;

        if (! $documento || $documento->stato !== StatoDocumento::Generato) {
            return $this->errore($consegna, 'Il documento di questa consegna non è ancora stato generato.');
        }

        $disco = Storage::disk($documento->disco);

        if (! $disco->exists($documento->percorso)) {
            Log::error('Documento consegna archiviato non trovato', ['consegna' => $consegna->numero, 'percorso' => $documento->percorso]);

            return $this->errore($consegna, 'Il file archiviato non è stato trovato: va ripristinato dal backup dei documenti.');
        }

        if (hash('sha256', $disco->get($documento->percorso)) !== $documento->hash_sha256) {
            // Il file viene comunque consegnato: la segnalazione serve a chi amministra.
            Log::warning('Documento consegna modificato dopo l\'archiviazione', ['consegna' => $consegna->numero, 'percorso' => $documento->percorso]);
        }

        return $disco->download($documento->percorso, $documento->nome_file, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }

    private function errore(Consegna $consegna, string $messaggio): RedirectResponse
    {
        return redirect()
            ->route('consegne.show', $consegna)
            ->with('toast', ['type' => 'error', 'message' => $messaggio]);
    }
}
