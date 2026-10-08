<div>
    <div class="mb-8">
        <h1 class="text-xl font-semibold tracking-tight text-slate-900">Accedi</h1>
        <p class="mt-1 text-[13px] text-slate-500">Inserisci le tue credenziali personali per continuare.</p>
    </div>

    <form wire:submit="login" class="space-y-4" novalidate>
        <x-ui.field label="Email" for="email">
            <x-ui.input
                id="email"
                type="email"
                wire:model="email"
                icon="mail"
                autocomplete="username"
                autofocus
                required
                class="h-9"
            />
        </x-ui.field>

        <x-ui.field label="Password" for="password">
            <x-ui.input
                id="password"
                type="password"
                wire:model="password"
                icon="lock"
                autocomplete="current-password"
                required
                class="h-9"
            />
        </x-ui.field>

        <label class="flex w-fit cursor-pointer items-center gap-2 text-[13px] text-slate-600">
            <input type="checkbox" wire:model="ricordami" class="size-4 rounded border-line-strong text-primary-600 accent-primary-600">
            Ricordami su questo computer
        </label>

        <x-ui.button type="submit" size="lg" class="w-full" wire:loading.attr="disabled" wire:target="login">
            <span wire:loading.remove wire:target="login">Accedi</span>
            <span wire:loading wire:target="login" class="inline-flex items-center gap-1.5">
                <x-lucide-loader-circle class="size-4 animate-spin" aria-hidden="true" /> Verifica in corso…
            </span>
        </x-ui.button>
    </form>

    <p class="mt-8 text-xs text-slate-500">
        Non hai un accesso? Contatta il referente del magazzino.
    </p>
</div>
