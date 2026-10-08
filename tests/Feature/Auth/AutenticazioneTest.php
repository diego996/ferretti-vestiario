<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class AutenticazioneTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pagina_di_login_viene_mostrata(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSeeLivewire(Login::class)
            ->assertSee('Accedi');
    }

    public function test_un_ospite_viene_reindirizzato_al_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/')->assertRedirect('/login');
    }

    public function test_un_operatore_accede_con_credenziali_corrette(): void
    {
        $user = User::factory()->create(['password' => 'password-sicura-123']);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password-sicura-123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_credenziali_errate_mostrano_un_errore(): void
    {
        $user = User::factory()->create(['password' => 'password-sicura-123']);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'sbagliata')
            ->call('login')
            ->assertHasErrors(['email'])
            ->assertSet('password', '');

        $this->assertGuest();
    }

    public function test_un_operatore_disattivato_non_puo_accedere(): void
    {
        $user = User::factory()->create(['password' => 'password-sicura-123', 'attivo' => false]);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'password-sicura-123')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_una_sessione_di_un_operatore_disattivato_viene_chiusa(): void
    {
        $user = User::factory()->create(['attivo' => false]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_troppi_tentativi_bloccano_temporaneamente_l_accesso(): void
    {
        $user = User::factory()->create(['password' => 'password-sicura-123']);
        RateLimiter::clear(strtolower($user->email).'|127.0.0.1');

        $componente = Livewire::test(Login::class)->set('email', $user->email);

        foreach (range(1, 5) as $tentativo) {
            $componente->set('password', 'sbagliata')->call('login');
        }

        // Anche con la password giusta il sesto tentativo è bloccato.
        $componente->set('password', 'password-sicura-123')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();
        $this->assertStringContainsString('Troppi tentativi', $componente->errors()->first('email'));
    }

    public function test_logout(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }
}
