<?php

namespace Database\Seeders;

use App\Models\CategoriaIndumento;
use App\Models\Collega;
use App\Models\Commessa;
use App\Models\Indumento;
use App\Models\IndumentoVariante;
use App\Models\User;
use App\Services\CaricoService;
use App\Services\ConsegnaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * DATI FITTIZI per sviluppo e dimostrazione.
 *
 * - Colleghi con matricola "DEMO-nnn", commesse con codice "DEMO-nnn".
 * - Le giacenze nascono da carichi e consegne registrati tramite
 *   MagazzinoService, quindi sono sempre riconciliabili con i movimenti.
 * - Non eseguire in produzione.
 */
class DemoSeeder extends Seeder
{
    private const TAGLIE_ABBIGLIAMENTO = ['S', 'M', 'L', 'XL', 'XXL'];

    private const TAGLIE_SCARPE = ['39', '40', '41', '42', '43', '44', '45', '46'];

    /** [categoria, codice, nome, taglie, scorta minima] */
    private const CATALOGO = [
        ['Pantaloni', 'PAN-001', 'Pantalone multitasche invernale', self::TAGLIE_ABBIGLIAMENTO, 6],
        ['Pantaloni', 'PAN-002', 'Pantalone estivo cotone', self::TAGLIE_ABBIGLIAMENTO, 6],
        ['Polo e magliette', 'POL-001', 'Polo manica corta', self::TAGLIE_ABBIGLIAMENTO, 10],
        ['Polo e magliette', 'TSH-001', 'T-shirt cotone', self::TAGLIE_ABBIGLIAMENTO, 10],
        ['Felpe', 'FEL-001', 'Felpa mezza zip', self::TAGLIE_ABBIGLIAMENTO, 5],
        ['Giacche', 'GIA-001', 'Giacca softshell', self::TAGLIE_ABBIGLIAMENTO, 4],
        ['Giacche', 'GIA-002', 'Giubbotto alta visibilità', self::TAGLIE_ABBIGLIAMENTO, 4],
        ['Scarpe antinfortunistiche', 'SCA-001', 'Scarpa bassa S3', self::TAGLIE_SCARPE, 3],
        ['Scarpe antinfortunistiche', 'SCA-002', 'Scarpa alta S3 invernale', self::TAGLIE_SCARPE, 2],
        ['Altri DPI', 'DPI-001', 'Guanti antitaglio', ['8', '9', '10'], 15],
        ['Altri DPI', 'DPI-002', 'Elmetto di protezione', ['Unica'], 5],
        ['Altri DPI', 'DPI-003', 'Occhiali di protezione', ['Unica'], 10],
    ];

    private const COMMESSE = [
        'Impianto trattamento acque – Lotto 2',
        'Revamping linea vapore',
        'Montaggio tubazioni area nord',
        'Ampliamento centrale termica',
        'Manutenzione straordinaria serbatoi',
        'Nuovo capannone logistico',
    ];

    public function run(CaricoService $carichi, ConsegnaService $consegne): void
    {
        $this->call(CategorieInizialiSeeder::class);

        $operatore = User::firstOrCreate(
            ['email' => 'operatore.demo@example.invalid'],
            // Utente tecnico non accessibile: password casuale mai mostrata.
            ['name' => 'Operatore demo', 'password' => Str::random(48), 'attivo' => false],
        );

        $varianti = $this->creaCatalogo();
        $colleghi = $this->creaColleghi();
        $commesse = $this->creaCommesse();

        $this->registraCarichi($carichi, $operatore, $varianti);
        $this->registraConsegne($consegne, $operatore, $varianti, $colleghi, $commesse);
    }

    /** @return Collection<int, IndumentoVariante> */
    private function creaCatalogo(): Collection
    {
        $categorie = CategoriaIndumento::pluck('id', 'nome');

        foreach (self::CATALOGO as [$categoria, $codice, $nome, $taglie, $scortaMinima]) {
            $indumento = Indumento::firstOrCreate(
                ['codice' => $codice],
                ['nome' => $nome, 'categoria_id' => $categorie[$categoria], 'attivo' => true],
            );

            foreach ($taglie as $ordine => $taglia) {
                IndumentoVariante::firstOrCreate(
                    ['indumento_id' => $indumento->id, 'taglia' => $taglia],
                    ['ordinamento' => ($ordine + 1) * 10, 'scorta_minima' => $scortaMinima],
                );
            }
        }

        return IndumentoVariante::with('indumento')->orderBy('id')->get();
    }

    /** @return Collection<int, Collega> */
    private function creaColleghi(): Collection
    {
        $faker = fake('it_IT');
        $faker->seed(2026);

        foreach (range(1, 28) as $i) {
            Collega::firstOrCreate(
                ['matricola' => sprintf('DEMO-%03d', $i)],
                ['nome' => $faker->firstName(), 'cognome' => $faker->lastName(), 'attivo' => $i <= 26],
            );
        }

        return Collega::attivi()->get();
    }

    /** @return Collection<int, Commessa> */
    private function creaCommesse(): Collection
    {
        foreach (self::COMMESSE as $i => $nome) {
            Commessa::firstOrCreate(
                ['codice' => sprintf('DEMO-%03d', $i + 1)],
                ['nome' => $nome, 'descrizione' => 'Commessa dimostrativa', 'attiva' => true],
            );
        }

        return Commessa::attive()->get();
    }

    /** @param Collection<int, IndumentoVariante> $varianti */
    private function registraCarichi(CaricoService $carichi, User $operatore, Collection $varianti): void
    {
        mt_srand(2026);

        foreach ([95, 60, 30, 8] as $giorniFa) {
            $data = Carbon::now()->subDays($giorniFa)->setTime(8, 30);

            // Il primo carico rifornisce tutto, i successivi solo una parte del catalogo.
            $selezione = $giorniFa === 95 ? $varianti : $varianti->random(intdiv($varianti->count(), 3));

            $righe = $selezione->map(fn (IndumentoVariante $v) => [
                'variante_id' => $v->id,
                'quantita' => $giorniFa === 95 ? mt_rand(4, 20) : mt_rand(3, 10),
            ])->values()->all();

            $carichi->registra($righe, $operatore, $data, 'Carico dimostrativo');
        }
    }

    /**
     * @param  Collection<int, IndumentoVariante>  $varianti
     * @param  Collection<int, Collega>  $colleghi
     * @param  Collection<int, Commessa>  $commesse
     */
    private function registraConsegne(
        ConsegnaService $consegne,
        User $operatore,
        Collection $varianti,
        Collection $colleghi,
        Collection $commesse,
    ): void {
        mt_srand(2027);
        $create = collect();

        // Date in ordine cronologico: la numerazione progressiva segue la data, come nell'uso reale.
        $date = collect(range(1, 45))
            ->map(fn () => Carbon::now()->subDays(mt_rand(0, 88))->setTime(mt_rand(7, 16), mt_rand(0, 59)))
            ->filter(fn (Carbon $data) => $data->isPast())
            ->sort();

        foreach ($date as $data) {
            $disponibili = $varianti->each->refresh()->filter(fn (IndumentoVariante $v) => $v->giacenza > 0);

            if ($disponibili->isEmpty()) {
                break;
            }

            $righe = $disponibili->random(min(mt_rand(1, 5), $disponibili->count()))
                ->map(fn (IndumentoVariante $v) => ['variante_id' => $v->id, 'quantita' => min($v->giacenza, mt_rand(1, 2))])
                ->values()
                ->all();

            $create->push($consegne->registra($colleghi->random(), $commesse->random(), $righe, $operatore, $data));
        }

        // Qualche annullamento, per mostrare anche questo stato.
        foreach ($create->slice(5, 2) as $consegna) {
            $consegne->annulla($consegna, $operatore, 'Taglia errata: consegna da rifare (dato dimostrativo)');
        }
    }
}
