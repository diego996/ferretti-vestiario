<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Accesso')]
class Login extends Component
{
    private const MAX_TENTATIVI = 5;

    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $ricordami = false;

    public function login(): void
    {
        $this->validate();
        $this->verificaLimiteTentativi();

        // Solo operatori attivi possono accedere.
        $credenziali = ['email' => $this->email, 'password' => $this->password, 'attivo' => true];

        if (! Auth::attempt($credenziali, $this->ricordami)) {
            RateLimiter::hit($this->chiaveLimite());
            Log::notice('Tentativo di accesso fallito', ['email' => $this->email, 'ip' => request()->ip()]);
            $this->reset('password');

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($this->chiaveLimite());
        session()->regenerate();

        $this->redirectIntended(route('dashboard', absolute: false), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }

    private function verificaLimiteTentativi(): void
    {
        if (! RateLimiter::tooManyAttempts($this->chiaveLimite(), self::MAX_TENTATIVI)) {
            return;
        }

        event(new Lockout(request()));

        $secondi = RateLimiter::availableIn($this->chiaveLimite());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', ['seconds' => $secondi, 'minutes' => ceil($secondi / 60)]),
        ]);
    }

    private function chiaveLimite(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
