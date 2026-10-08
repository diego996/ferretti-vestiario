<?php

namespace App\Providers;

use App\Models\Carico;
use App\Models\Consegna;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Number;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Alias stabili per l'origine dei movimenti: il DB non dipende dai nomi delle classi.
        Relation::enforceMorphMap([
            'carico' => Carico::class,
            'consegna' => Consegna::class,
        ]);

        // In sviluppo segnala lazy loading (N+1) e attributi inesistenti.
        Model::shouldBeStrict(! $this->app->isProduction());

        Carbon::setLocale(config('app.locale'));
        Number::useLocale(config('app.locale'));
    }
}
