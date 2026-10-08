# Ferretti | Gestione Vestiario

Gestionale interno per vestiario da lavoro, DPI e consegne ai colleghi per commessa.
Laravel 12 · Livewire 3 · Tailwind 4 · MySQL 8 / MariaDB.

Documentazione in [`docs/`](docs): architettura, database, flussi, linee guida UI, avanzamento e questioni aperte.

## Requisiti

PHP ≥ 8.2 (estensioni `pdo_mysql`, `mbstring`, `intl`, `zip`, `gd`, `fileinfo`), Composer 2, Node 20+, MySQL 8 o MariaDB ≥ 10.4.

## Avvio locale

```bash
composer install
npm install
cp .env.example .env          # poi impostare DB_* e, se serve, DB_CONNECTION=mysql
php artisan key:generate

# database (es. MariaDB di XAMPP)
mysql -u root -e "CREATE DATABASE ferretti_vestiario CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php artisan migrate --seed    # fuori produzione include i dati demo

php artisan vestiario:crea-utente   # crea il proprio accesso
npm run build                       # oppure "npm run dev" durante lo sviluppo
php artisan serve                   # http://127.0.0.1:8000
```

## Comandi utili

| Comando | Scopo |
|---|---|
| `php artisan vestiario:crea-utente` | crea un operatore (non esiste registrazione pubblica) |
| `php artisan magazzino:verifica` | confronta giacenze e somma dei movimenti |
| `php artisan migrate:fresh --seed` | ricrea il DB con dati demo (**solo sviluppo**) |
| `php artisan test` | test su SQLite in memoria |
| `DB_CONNECTION=mariadb DB_DATABASE=ferretti_vestiario_test php artisan test` | test su MariaDB/MySQL, inclusi i vincoli CHECK (creare prima il DB) |
| `vendor/bin/pint` | formattazione codice |

## Configurazione

| Variabile | Default | Note |
|---|---|---|
| `APP_NAME` | `Ferretti \| Gestione Vestiario` | nome applicazione |
| `APP_TIMEZONE` | `Europe/Rome` | timezone operativa |
| `VESTIARIO_COMPANY_NAME` | `Ferretti Construction` | |
| `VESTIARIO_LOGO_PATH` / `VESTIARIO_LOGO_DARK_PATH` | vuoto | percorso in `public/`; vuoto = marchio provvisorio |
| `VESTIARIO_FAVICON_PATH` | `brand/favicon.svg` | |
| `VESTIARIO_DEMO_MODE` | `false` | mostra il contrassegno "Dati demo" |

## Produzione (promemoria)

`APP_ENV=production`, `APP_DEBUG=false`, HTTPS, `php artisan optimize`, backup giornaliero di database e `storage/app/private` (vedi `docs/ARCHITECTURE.md`).
