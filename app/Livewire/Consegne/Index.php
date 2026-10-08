<?php

namespace App\Livewire\Consegne;

use App\Enums\StatoConsegna;
use App\Livewire\Concerns\ConPaginazione;
use App\Models\Collega;
use App\Models\Commessa;
use App\Models\Consegna;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

#[Title('Consegne')]
class Index extends Component
{
    use ConPaginazione;

    /** Numero consegna o nome del collega. */
    #[Url(as: 'q', except: '')]
    public string $cerca = '';

    #[Url(except: '')]
    public string $commessa = '';

    #[Url(except: '')]
    public string $stato = '';

    #[Url(except: '')]
    public string $dal = '';

    #[Url(except: '')]
    public string $al = '';

    /** Filtro contestuale aperto dall'elenco colleghi. */
    #[Url(except: '')]
    public string $collega = '';

    public function azzeraFiltri(): void
    {
        $this->reset('cerca', 'commessa', 'stato', 'dal', 'al', 'collega');
        $this->resetPage();
    }

    #[Computed]
    public function consegne(): LengthAwarePaginator
    {
        $dal = $this->data($this->dal);
        $al = $this->data($this->al);

        return Consegna::query()
            ->with(['collega:id,nome,cognome,matricola', 'commessa:id,codice,nome', 'operatore:id,name'])
            ->withSum('righe as totale_unita', 'quantita')
            ->when(StatoConsegna::tryFrom($this->stato), fn (Builder $q, StatoConsegna $stato) => $q->where('stato', $stato))
            ->when($this->commessa !== '', fn (Builder $q) => $q->where('commessa_id', (int) $this->commessa))
            ->when($this->collega !== '', fn (Builder $q) => $q->where('collega_id', (int) $this->collega))
            ->when($dal, fn (Builder $q) => $q->where('data_consegna', '>=', $dal->startOfDay()))
            ->when($al, fn (Builder $q) => $q->where('data_consegna', '<=', $al->endOfDay()))
            ->when(trim($this->cerca) !== '', function (Builder $q) {
                $testo = trim($this->cerca);
                $q->where(fn (Builder $w) => $w
                    ->where('numero', 'like', "%{$testo}%")
                    ->orWhereHas('collega', fn (Builder $c) => $c->cerca($testo)));
            })
            ->latest('data_consegna')
            ->latest('id')
            ->paginate(self::PER_PAGINA);
    }

    /** @return Collection<int, Commessa> */
    #[Computed]
    public function commesse(): Collection
    {
        return Commessa::query()->orderByDesc('attiva')->orderBy('nome')->get(['id', 'codice', 'nome', 'attiva']);
    }

    #[Computed]
    public function collegaFiltrato(): ?Collega
    {
        return $this->collega !== '' ? Collega::find((int) $this->collega) : null;
    }

    public function render()
    {
        return view('livewire.consegne.index');
    }

    private function data(string $valore): ?Carbon
    {
        if ($valore === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $valore);
        } catch (Throwable) {
            return null;
        }
    }
}
