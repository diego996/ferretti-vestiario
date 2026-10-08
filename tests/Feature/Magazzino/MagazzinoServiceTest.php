<?php

namespace Tests\Feature\Magazzino;

use App\Enums\TipoMovimento;
use App\Exceptions\GiacenzaInsufficienteException;
use App\Models\IndumentoVariante;
use App\Models\MovimentoMagazzino;
use App\Models\User;
use App\Services\MagazzinoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MagazzinoServiceTest extends TestCase
{
    use RefreshDatabase;

    private MagazzinoService $magazzino;

    private User $operatore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->magazzino = app(MagazzinoService::class);
        $this->operatore = User::factory()->create();
    }

    public function test_un_carico_incrementa_la_giacenza_e_crea_il_movimento(): void
    {
        $variante = IndumentoVariante::factory()->create();

        $this->magazzino->registraMovimenti(TipoMovimento::Carico, [
            ['variante_id' => $variante->id, 'quantita' => 12],
        ], $this->operatore);

        $this->assertSame(12, $variante->fresh()->giacenza);
        $this->assertDatabaseHas('movimenti_magazzino', [
            'variante_id' => $variante->id,
            'tipo' => 'carico',
            'quantita' => 12,
            'giacenza_risultante' => 12,
            'user_id' => $this->operatore->id,
        ]);
    }

    public function test_righe_duplicate_vengono_aggregate(): void
    {
        $variante = IndumentoVariante::factory()->create();

        $movimenti = $this->magazzino->registraMovimenti(TipoMovimento::Carico, [
            ['variante_id' => $variante->id, 'quantita' => 3],
            ['variante_id' => (string) $variante->id, 'quantita' => '4'],
        ], $this->operatore);

        $this->assertCount(1, $movimenti);
        $this->assertSame(7, $movimenti->first()->quantita);
        $this->assertSame(7, $variante->fresh()->giacenza);
    }

    public function test_uno_scarico_oltre_la_disponibilita_non_modifica_nulla(): void
    {
        $a = IndumentoVariante::factory()->create();
        $b = IndumentoVariante::factory()->create();
        $this->carica($a, 5);
        $this->carica($b, 1);

        try {
            $this->magazzino->registraMovimenti(TipoMovimento::ScaricoConsegna, [
                ['variante_id' => $a->id, 'quantita' => 2],
                ['variante_id' => $b->id, 'quantita' => 3],
            ], $this->operatore);

            $this->fail('Attesa GiacenzaInsufficienteException');
        } catch (GiacenzaInsufficienteException $e) {
            $this->assertCount(1, $e->mancanze);
            $this->assertSame($b->id, $e->mancanze[0]['variante_id']);
        }

        // Operazione atomica: nemmeno la riga disponibile è stata scaricata.
        $this->assertSame(5, $a->fresh()->giacenza);
        $this->assertSame(1, $b->fresh()->giacenza);
        $this->assertSame(2, MovimentoMagazzino::count());
    }

    public function test_lo_scarico_produce_quantita_negative(): void
    {
        $variante = IndumentoVariante::factory()->create();
        $this->carica($variante, 10);

        $movimento = $this->magazzino->registraMovimenti(TipoMovimento::ScaricoConsegna, [
            ['variante_id' => $variante->id, 'quantita' => 4],
        ], $this->operatore)->first();

        $this->assertSame(-4, $movimento->quantita);
        $this->assertSame(6, $movimento->giacenza_risultante);
        $this->assertSame(6, $variante->fresh()->giacenza);
    }

    public function test_rettifica_positiva_e_negativa(): void
    {
        $variante = IndumentoVariante::factory()->create();
        $this->carica($variante, 10);

        $piu = $this->magazzino->rettifica($variante, 3, $this->operatore, 'Inventario: trovati 3 capi');
        $meno = $this->magazzino->rettifica($variante, -5, $this->operatore, 'Capi danneggiati');

        $this->assertSame(TipoMovimento::RettificaPositiva, $piu->tipo);
        $this->assertSame(TipoMovimento::RettificaNegativa, $meno->tipo);
        $this->assertSame(-5, $meno->quantita);
        $this->assertNull($meno->origine_type);
        $this->assertSame(8, $variante->fresh()->giacenza);
    }

    public function test_rettifica_negativa_oltre_la_giacenza_viene_rifiutata(): void
    {
        $variante = IndumentoVariante::factory()->create();
        $this->carica($variante, 2);

        $this->expectException(GiacenzaInsufficienteException::class);

        $this->magazzino->rettifica($variante, -3, $this->operatore, 'Prova');
    }

    public function test_la_rettifica_richiede_una_motivazione(): void
    {
        $variante = IndumentoVariante::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        $this->magazzino->rettifica($variante, 1, $this->operatore, '   ');
    }

    #[DataProvider('quantitaNonValide')]
    public function test_quantita_nulle_o_negative_vengono_rifiutate(mixed $quantita): void
    {
        $variante = IndumentoVariante::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        $this->magazzino->registraMovimenti(TipoMovimento::Carico, [
            ['variante_id' => $variante->id, 'quantita' => $quantita],
        ], $this->operatore);
    }

    public static function quantitaNonValide(): array
    {
        return [[0], [-2], ['1.5'], ['abc'], [null]];
    }

    public function test_varianti_inesistenti_vengono_rifiutate(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->magazzino->registraMovimenti(TipoMovimento::Carico, [
            ['variante_id' => 999, 'quantita' => 1],
        ], $this->operatore);
    }

    public function test_la_giacenza_non_e_modificabile_dal_model(): void
    {
        $variante = IndumentoVariante::factory()->create();

        $this->assertNotContains('giacenza', $variante->getFillable());

        $this->expectException(LogicException::class);
        $variante->forceFill(['giacenza' => 50])->save();
    }

    public function test_una_nuova_variante_parte_da_giacenza_zero(): void
    {
        $this->expectException(LogicException::class);

        $variante = IndumentoVariante::factory()->make();
        $variante->forceFill(['giacenza' => 10])->save();
    }

    public function test_i_movimenti_sono_immutabili(): void
    {
        $variante = IndumentoVariante::factory()->create();
        $movimento = $this->carica($variante, 1);

        $this->expectException(LogicException::class);
        $movimento->delete();
    }

    public function test_la_riconciliazione_rileva_le_discrepanze(): void
    {
        $ok = IndumentoVariante::factory()->create();
        $alterata = IndumentoVariante::factory()->create();
        $this->carica($ok, 5);
        $this->carica($alterata, 5);

        $this->assertTrue($this->magazzino->discrepanze()->isEmpty());
        $this->artisan('magazzino:verifica')->assertSuccessful();

        // Simula una modifica fuori dai service (es. SQL manuale).
        DB::table('indumento_varianti')->where('id', $alterata->id)->update(['giacenza' => 9]);

        $discrepanze = $this->magazzino->discrepanze();
        $this->assertCount(1, $discrepanze);
        $this->assertSame($alterata->id, $discrepanze->first()->id);
        $this->assertSame(9, $discrepanze->first()->giacenza);
        $this->assertSame(5, $discrepanze->first()->saldo_movimenti);
        $this->artisan('magazzino:verifica')->assertFailed();
    }

    private function carica(IndumentoVariante $variante, int $quantita): MovimentoMagazzino
    {
        return $this->magazzino->registraMovimenti(TipoMovimento::Carico, [
            ['variante_id' => $variante->id, 'quantita' => $quantita],
        ], $this->operatore)->first();
    }
}
