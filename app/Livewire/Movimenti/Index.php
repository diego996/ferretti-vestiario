<?php

namespace App\Livewire\Movimenti;

use App\Enums\TipoMovimento;
use App\Livewire\Concerns\ConPaginazione;
use App\Models\Carico;
use App\Models\Consegna;
use App\Models\IndumentoVariante;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

#[Title('Storico movimenti')]
class Index extends Component
{
    use ConPaginazione;

    #[Url(as: 'q', except: '')]
    public string $cerca = '';

    #[Url(except: '')]
    public string $tipo = '';

    #[Url(except: '')]
    public string $dal = '';

    #[Url(except: '')]
    public string $al = '';

    #[Url(except: '')]
    public string $operatore = '';

    /** Filtri "contestuali" aperti da altre pagine (drawer Magazzino, ultimi carichi, dettaglio consegna). */
    #[Url(except: '')]
    public string $variante = '';

    #[Url(except: '')]
    public string $carico = '';

    #[Url(except: '')]
    public string $consegna = '';

    public function azzeraFiltri(): void
    {
        $this->reset('cerca', 'tipo', 'dal', 'al', 'operatore', 'variante', 'carico', 'consegna');
        $this->resetPage();
    }

    public function rimuoviContesto(): void
    {
        $this->reset('variante', 'carico', 'consegna');
        $this->resetPage();
    }

    #[Computed]
    public function movimenti(): LengthAwarePaginator
    {
        $dal = $this->data($this->dal);
        $al = $this->data($this->al);

        return MovimentoMagazzino::query()
            ->with(['variante.indumento:id,codice,nome', 'operatore:id,name', 'origine'])
            ->when(TipoMovimento::tryFrom($this->tipo), fn (Builder $q, TipoMovimento $tipo) => $q->where('tipo', $tipo))
            ->when($dal, fn (Builder $q) => $q->where('data_movimento', '>=', $dal->startOfDay()))
            ->when($al, fn (Builder $q) => $q->where('data_movimento', '<=', $al->endOfDay()))
            ->when($this->operatore !== '', fn (Builder $q) => $q->where('user_id', (int) $this->operatore))
            ->when($this->variante !== '', fn (Builder $q) => $q->where('variante_id', (int) $this->variante))
            ->when($this->carico !== '', fn (Builder $q) => $q->whereMorphedTo('origine', Carico::class)->where('origine_id', (int) $this->carico))
            ->when($this->consegna !== '', fn (Builder $q) => $q->whereMorphedTo('origine', Consegna::class)->where('origine_id', (int) $this->consegna))
            ->when(trim($this->cerca) !== '', fn (Builder $q) => $q->whereHas('variante.indumento', fn (Builder $i) => $i->cerca($this->cerca)))
            ->latest('data_movimento')
            ->latest('id')
            ->paginate(self::PER_PAGINA);
    }

    /** @return Collection<int, User> operatori che hanno registrato almeno un movimento */
    #[Computed]
    public function operatori(): Collection
    {
        return User::query()
            ->whereIn('id', MovimentoMagazzino::query()->select('user_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /** Etichetta del filtro contestuale attivo (variante o carico), se presente. */
    #[Computed]
    public function contesto(): ?string
    {
        if ($this->variante !== '') {
            $v = IndumentoVariante::with('indumento:id,nome')->find((int) $this->variante);

            return $v ? "{$v->indumento->nome} — taglia {$v->taglia}" : 'Taglia inesistente';
        }

        if ($this->carico !== '') {
            $c = Carico::find((int) $this->carico);

            return $c ? 'Carico del '.$c->data_carico->format('d/m/Y H:i') : 'Carico inesistente';
        }

        if ($this->consegna !== '') {
            $c = Consegna::find((int) $this->consegna);

            return $c ? "Consegna {$c->numero}" : 'Consegna inesistente';
        }

        return null;
    }

    public function render()
    {
        return view('livewire.movimenti.index');
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
