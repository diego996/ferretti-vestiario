<?php

namespace Tests\Unit;

use App\Enums\StatoScorta;
use App\Enums\TipoMovimento;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StatoScortaTest extends TestCase
{
    #[DataProvider('casi')]
    public function test_stato_in_base_a_giacenza_e_scorta_minima(int $giacenza, int $scortaMinima, StatoScorta $atteso): void
    {
        $this->assertSame($atteso, StatoScorta::per($giacenza, $scortaMinima));
    }

    public static function casi(): array
    {
        return [
            'zero senza scorta minima' => [0, 0, StatoScorta::Esaurito],
            'zero con scorta minima' => [0, 5, StatoScorta::Esaurito],
            'sotto la scorta' => [3, 5, StatoScorta::ScortaBassa],
            'uguale alla scorta' => [5, 5, StatoScorta::ScortaBassa],
            'sopra la scorta' => [6, 5, StatoScorta::Disponibile],
            'senza scorta minima' => [1, 0, StatoScorta::Disponibile],
        ];
    }

    public function test_segno_dei_tipi_di_movimento(): void
    {
        $this->assertSame(1, TipoMovimento::Carico->segno());
        $this->assertSame(1, TipoMovimento::RettificaPositiva->segno());
        $this->assertSame(1, TipoMovimento::CompensazioneAnnullamento->segno());
        $this->assertSame(-1, TipoMovimento::ScaricoConsegna->segno());
        $this->assertSame(-1, TipoMovimento::RettificaNegativa->segno());
    }
}
