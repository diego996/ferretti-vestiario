<?php

namespace Tests\Feature\Anagrafiche;

use App\Livewire\Colleghi\Index as Colleghi;
use App\Livewire\Commesse\Index as Commesse;
use App\Models\Collega;
use App\Models\Commessa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ColleghiCommesseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_le_pagine_si_aprono(): void
    {
        Collega::factory()->create(['cognome' => 'Bianchi']);
        Commessa::factory()->create(['nome' => 'Cantiere Nord']);

        $this->get('/colleghi')->assertOk()->assertSee('Bianchi');
        $this->get('/commesse')->assertOk()->assertSee('Cantiere Nord');
    }

    public function test_crea_collega_e_rifiuta_matricola_duplicata(): void
    {
        Collega::factory()->create(['matricola' => 'M-1']);

        Livewire::test(Colleghi::class)
            ->call('nuovo')
            ->set('cognome', ' Rossi ')
            ->set('nome', 'Anna')
            ->set('matricola', 'M-1')
            ->call('salva')
            ->assertHasErrors('matricola')
            ->set('matricola', '')
            ->call('salva')
            ->assertHasNoErrors()
            ->assertDispatched('close-drawer')
            ->assertSee('Rossi Anna');

        $this->assertDatabaseHas('colleghi', ['cognome' => 'Rossi', 'nome' => 'Anna', 'matricola' => null]);
    }

    public function test_modifica_collega_e_filtri(): void
    {
        $collega = Collega::factory()->create(['cognome' => 'Verdi', 'nome' => 'Luca', 'matricola' => 'A-7']);
        Collega::factory()->create(['cognome' => 'Neri', 'attivo' => false]);

        Livewire::test(Colleghi::class)
            ->assertSee('Verdi')->assertDontSee('Neri')
            ->set('cerca', 'luca verdi')->assertSee('Verdi')
            ->set('cerca', 'A-7')->assertSee('Verdi')
            ->call('azzeraFiltri')->set('stato', 'disattivati')
            ->assertSee('Neri')->assertDontSee('Verdi')
            ->call('modifica', $collega->id)
            ->assertSet('matricola', 'A-7')
            ->set('attivo', false)
            ->call('salva')
            ->assertHasNoErrors();

        $this->assertFalse($collega->fresh()->attivo);
        $this->assertSame('A-7', $collega->fresh()->matricola);
    }

    public function test_crea_e_modifica_commessa(): void
    {
        $esistente = Commessa::factory()->create(['codice' => 'C-1', 'nome' => 'Vecchia']);

        Livewire::test(Commesse::class)
            ->call('nuova')
            ->set('codice', 'C-1')
            ->set('nome', 'Nuova linea')
            ->call('salva')
            ->assertHasErrors('codice')
            ->set('codice', 'C-2')
            ->call('salva')
            ->assertHasNoErrors()
            ->assertSee('Nuova linea')
            ->call('modifica', $esistente->id)
            ->set('attiva', false)
            ->call('salva')
            ->assertHasNoErrors()
            ->assertDontSee('Vecchia')
            ->set('stato', 'chiuse')
            ->assertSee('Vecchia');

        $this->assertDatabaseHas('commesse', ['codice' => 'C-2', 'nome' => 'Nuova linea', 'attiva' => true]);
    }
}
