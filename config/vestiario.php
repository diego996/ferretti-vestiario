<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    |
    | Il nome dell'applicazione è APP_NAME. I percorsi del logo e della favicon
    | sono relativi alla cartella /public. Se il logo non è configurato viene
    | mostrato il branding provvisorio (icona + nome applicazione).
    |
    | Il logo ufficiale va inserito solo dopo aver verificato che il suo uso
    | interno sia autorizzato (vedi docs/PENDING_REQUIREMENTS.md).
    |
    */

    'company_name' => env('VESTIARIO_COMPANY_NAME', 'Ferretti Construction'),

    'logo_path' => env('VESTIARIO_LOGO_PATH') ?: null,

    // Variante del logo per sfondi scuri (sidebar). Se assente usa logo_path.
    'logo_dark_path' => env('VESTIARIO_LOGO_DARK_PATH') ?: null,

    'favicon_path' => env('VESTIARIO_FAVICON_PATH', 'brand/favicon.svg'),

    /*
    |--------------------------------------------------------------------------
    | Dati dimostrativi
    |--------------------------------------------------------------------------
    |
    | Quando attivo, l'interfaccia mostra un contrassegno "Dati demo" per
    | distinguere chiaramente i dati generati dai seeder dimostrativi.
    |
    */

    'demo_mode' => (bool) env('VESTIARIO_DEMO_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Documento di consegna (Word)
    |--------------------------------------------------------------------------
    |
    | Il documento è generato dal modello .docx indicato, sostituendo i
    | segnaposto ${...} (elenco in docs/DOCUMENTI.md). Il modello attuale è
    | provvisorio: si rigenera con `php artisan vestiario:modello-word`.
    | I file sono archiviati sul disco privato indicato, mai in /public.
    |
    */

    'documenti' => [
        'template' => env('VESTIARIO_TEMPLATE_CONSEGNA') ?: resource_path('templates/consegna.docx'),
        'disco' => env('VESTIARIO_DOCUMENTI_DISCO') ?: 'local',
        'cartella' => 'consegne',
    ],

];
