# Architettura

## Stack

| Componente | Versione | Note |
|---|---|---|
| Laravel | 12.x | monolite, nessuna API separata |
| PHP | ≥ 8.2 | sviluppo su 8.2.12 (XAMPP) |
| Database | MySQL 8 (produzione) / MariaDB 10.4 (sviluppo locale XAMPP) | driver `mysql` o `mariadb` |
| Livewire | 3.x | include Alpine.js (plugin focus, persist…) |
| Tailwind CSS | 4.x via `@tailwindcss/vite` | tema in `resources/css/app.css` |
| Icone | `mallardduck/blade-lucide-icons` | `<x-lucide-nome-icona />` |
| Font | Inter (`@fontsource-variable/inter`) | servito localmente, nessun CDN |
| Test | PHPUnit 11 | SQLite in memoria (default) o MariaDB |
| Word | PHPWord 1.4 (`phpoffice/phpword`) | `TemplateProcessor` su modello `.docx` (vedi DOCUMENTI.md); richiede le estensioni `zip` e `xml` |

Non usati di proposito: React/Vue/Inertia, repository pattern, starter kit di autenticazione (il login è un singolo componente Livewire), axios (Livewire gestisce le richieste).

## Organizzazione del codice

```
app/
  Console/Commands/   vestiario:crea-utente, magazzino:verifica, vestiario:modello-word
  Enums/              TipoMovimento, StatoConsegna, StatoDocumento, StatoScorta
  Exceptions/         GiacenzaInsufficienteException, OperazioneNonConsentitaException
                      (messaggi mostrabili all'operatore)
  Http/               LogoutController, ScaricaDocumentoConsegnaController, middleware EnsureUserIsActive
  Livewire/           componenti a pagina intera: Dashboard, Auth\Login, Magazzino, Carichi,
                      Movimenti, Catalogo (Index, FormIndumento, Categorie), Colleghi, Commesse,
                      Consegne (Index, NuovaConsegna, Dettaglio)
  Livewire/Concerns/  ConPaginazione (paginazione condivisa; reset pagina sui filtri #[Url])
  Models/             entità Eloquent (nomi italiani, tabelle esplicite)
  Services/           logica di dominio (MagazzinoService, CaricoService, ConsegnaService,
                      DocumentoConsegnaService)
  Support/            Navigazione (voci sidebar), CheckConstraint (helper migration)
config/vestiario.php  branding, modalità demo, modello e disco dei documenti
resources/templates/  consegna.docx (modello Word provvisorio)
resources/views/
  components/layouts/ app (sidebar+header), guest (login), head
  components/ui/      componenti condivisi (vedi UI_GUIDELINES.md)
  livewire/           view dei componenti Livewire
```

## Principi

1. **La giacenza cambia solo in `MagazzinoService`.** Ogni variazione crea un movimento e aggiorna il saldo nella stessa transazione. Il model `IndumentoVariante` rifiuta modifiche a `giacenza` (eccezione su `updating`) e una variante nasce sempre a zero.
2. **Lock deterministici.** `registraMovimenti()` aggrega le righe per variante, ordina gli ID e acquisisce `lockForUpdate()` in ordine crescente: due operazioni concorrenti sulle stesse varianti non vanno in deadlock e la verifica di disponibilità avviene sui valori bloccati.
3. **Movimenti append-only.** `MovimentoMagazzino` non è aggiornabile né cancellabile dal model; le correzioni sono nuovi movimenti (rettifiche, compensazioni).
4. **Numero, stato e annullamento di una consegna non sono mass-assignable**: li imposta solo `ConsegnaService`.
   **Ordine dei lock**: nuova consegna → contatore dell'anno, poi varianti; annullamento → riga della consegna → varianti; carichi e rettifiche → solo varianti. Nessun ciclo possibile tra operazioni concorrenti.
5. **Generazione Word fuori dalla transazione**: la consegna viene salvata e committata, poi si genera il documento; un errore documentale non tocca i movimenti. Un documento generato è immutabile (hash registrato) e non viene mai rigenerato.
6. **Filtri nell'URL.** Nelle pagine elenco i filtri sono proprietà `#[Url]`: la pagina è condivisibile/ricaricabile e il cambio di un filtro riporta alla prima pagina (`ConPaginazione`).
7. **Funzioni non pronte = non cliccabili.** `App\Support\Navigazione` abilita una voce solo se la rotta esiste; altrimenti la mostra disabilitata con la fase prevista. Stesso criterio per pulsanti e azioni rapide.

## Service previsti

| Service | Stato | Responsabilità |
|---|---|---|
| `MagazzinoService` | ✅ Fase 1 (nucleo) | movimenti, giacenze, disponibilità, rettifiche, riconciliazione |
| `CaricoService` | ✅ Fase 2 | crea carico + righe e delega a `MagazzinoService` (tipo `carico`); rifiuta date future e varianti disattivate |
| `ConsegnaService` | ✅ Fase 3 | numerazione (contatore annuale con lock di riga), creazione atomica con snapshot, scarico, idempotenza, annullamento con compensazione |
| `DocumentoConsegnaService` | ✅ Fase 4 | modello PHPWord, archiviazione privata con hash, nessuna rigenerazione, stato di errore e nuovo tentativo |

`CaricoService` e `ConsegnaService` non devono toccare la giacenza direttamente: chiamano sempre `MagazzinoService::registraMovimenti()` all'interno della propria transazione (le transazioni annidate usano savepoint).

## Sicurezza

- Nessuna registrazione pubblica: gli operatori si creano con `php artisan vestiario:crea-utente`.
- Login con rate limit (5 tentativi per email+IP), rigenerazione sessione, password svuotata dopo un errore, log dei tentativi falliti.
- Operatori disattivati (`users.attivo = false`): non possono accedere e una sessione aperta viene chiusa dal middleware `EnsureUserIsActive`.
- CSRF attivo (Livewire e form di logout); `meta robots noindex`.
- Mass assignment controllato tramite `$fillable` espliciti.
- In produzione `APP_DEBUG=false`: errori generici all'utente, dettagli solo nei log.
- I documenti Word stanno sul disco `local` (`storage/app/private`), mai in `public`, e si scaricano solo tramite la rotta autenticata `consegne.documento`. Il disco ha `serve => false`: nessun URL firmato o diretto verso i file privati.
- Escaping XML dei dati inseriti nel Word (`Settings::setOutputEscapingEnabled(true)`): caratteri come `&` e `<` non corrompono il documento.

## Timezone e localizzazione

`APP_TIMEZONE=Europe/Rome` (configurabile). Le date vengono salvate nell'ora locale configurata. Locale `it` per Carbon, `Number` e messaggi di validazione (`lang/it`).

## Backup (da predisporre in produzione)

- **Database**: `mysqldump --single-transaction --routines ferretti_vestiario | gzip` giornaliero, con conservazione di almeno 30 giorni e una copia fuori dal server.
- **Documenti**: copia giornaliera di `storage/app/private/` (i Word archiviati sono parte dello storico e non vengono rigenerati).
- **Verifica**: dopo un ripristino eseguire `php artisan magazzino:verifica`.
- Database e documenti vanno salvati nello stesso momento per mantenere la coerenza tra consegne e file.
