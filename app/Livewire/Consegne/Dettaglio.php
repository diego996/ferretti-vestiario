<?php

namespace App\Livewire\Consegne;

use App\Enums\StatoDocumento;
use App\Exceptions\OperazioneNonConsentitaException;
use App\Models\Consegna;
use App\Services\ConsegnaService;
use App\Services\DocumentoConsegnaService;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Dettaglio extends Component
{
    #[Locked]
    public int $consegnaId;

    public string $motivo = '';

    public function mount(Consegna $consegna): void
    {
        $this->consegnaId = $consegna->id;
    }

    public function annulla(ConsegnaService $consegne): void
    {
        $this->validate(
            ['motivo' => ['required', 'string', 'min:5', 'max:1000']],
            attributes: ['motivo' => 'motivazione'],
        );

        try {
            $annullata = $consegne->annulla($this->consegna(), auth()->user(), $this->motivo);
        } catch (OperazioneNonConsentitaException $e) {
            $this->dispatch('close-confirm', id: 'annulla-consegna');
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        $this->reset('motivo');
        $this->dispatch('close-confirm', id: 'annulla-consegna');
        $this->dispatch('toast', type: 'success', message: "Consegna {$annullata->numero} annullata: i capi sono rientrati a magazzino.");
    }

    /** Genera il documento mancante o fallito; se riesce avvia il download. */
    public function generaDocumento(DocumentoConsegnaService $documenti): void
    {
        try {
            $documento = $documenti->genera($this->consegna());
        } catch (OperazioneNonConsentitaException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        if ($documento->stato !== StatoDocumento::Generato) {
            $this->dispatch('toast', type: 'error', message: 'Generazione non riuscita: '.$documento->ultimo_errore);

            return;
        }

        $this->dispatch('toast', type: 'success', message: 'Documento generato. Download in corso.');
        $this->dispatch('scarica-documento', url: route('consegne.documento', $this->consegnaId));
    }

    public function render()
    {
        $consegna = $this->consegna()->load([
            'collega', 'commessa', 'operatore:id,name', 'annullataDa:id,name', 'documento',
            'righe' => fn ($q) => $q->orderBy('indumento_nome')->orderBy('id'),
        ]);

        return view('livewire.consegne.dettaglio', ['consegna' => $consegna])
            ->title("Consegna {$consegna->numero}")
            ->layoutData(['breadcrumb' => [
                ['label' => 'Consegne', 'url' => route('consegne.index')],
                ['label' => $consegna->numero],
            ]]);
    }

    private function consegna(): Consegna
    {
        return Consegna::findOrFail($this->consegnaId);
    }
}
