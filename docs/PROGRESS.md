# Avanzamento

## Fase 1 — Fondamenta e interfaccia ✅ (8 ottobre 2026)

**Completato**
- Laravel 12.69, Livewire 3.8, Tailwind 4, Lucide, Inter locale; configurazione `it` / `Europe/Rome`.
- Configurazione `config/vestiario.php` (branding, demo mode) e variabili `VESTIARIO_*`.
- Analisi branding del sito ufficiale; palette e tema (vedi UI_GUIDELINES.md).
- Autenticazione: login Livewire, rate limit, utenti attivi/disattivati, logout, comando `vestiario:crea-utente`.
- Layout: sidebar collassabile, header, menu utente, toast, componenti UI condivisi.
- Migration complete per tutte le tabelle di dominio, con FK, indici, unique e CHECK (MySQL/MariaDB).
- Model e relazioni; enum di dominio; morph map; modalità strict (anti N+1) in sviluppo.
- `MagazzinoService` (nucleo): movimenti con lock ordinati, aggregazione righe, verifica disponibilità, rettifiche, riconciliazione. Comando `magazzino:verifica`.
- Seeder: categorie iniziali (idempotente), dati demo coerenti con i movimenti.
- Dashboard con KPI reali, ultime consegne, scorte da controllare, azioni rapide (disabilitate fino alle fasi relative).
- Test: 41 (autenticazione, dashboard, MagazzinoService, seeder, vincoli DB, enum). Verdi su SQLite e su MariaDB 10.4.

**Verifiche eseguite**
- `php artisan test` (SQLite) → 39 passati, 2 saltati (CHECK solo MySQL).
- `DB_CONNECTION=mariadb DB_DATABASE=ferretti_vestiario_test php artisan test` → 41 passati.
- `php artisan migrate:fresh --seed` + `magazzino:verifica` → giacenze allineate.
- Login reale e dashboard verificati in Chrome headless a 1440×900 e 1366×768, nessun errore in console.

**Non ancora presente (di proposito)**
- Pagine Magazzino, Carico, Colleghi, Commesse, Catalogo, Storico movimenti → Fase 2 ✅.
- Nuova consegna, elenco/dettaglio consegne, annullamento → Fase 3 ✅.
- PHPWord e documenti → Fase 4 ✅.
- Impostazioni → Fase 5.

## Fase 2 — Catalogo e magazzino ✅ (8 ottobre 2026)

**Completato**
- `CaricoService`: testata + righe aggregate per variante + movimenti in un'unica transazione; rifiuta date future e taglie/indumenti disattivati. Il seeder demo lo usa.
- **Carico merce** (`/carichi/nuovo`): righe indumento → taglia → quantità, anteprima della nuova giacenza, riepilogo, ultimi carichi con link ai movimenti.
- **Magazzino** (`/magazzino`): ricerca, categoria, disattivati, filtro rapido per stato con conteggi; drawer con giacenza, **rettifica inventariale** (motivazione obbligatoria, mai sotto zero) e ultimi movimenti.
- **Storico movimenti** (`/movimenti`): filtri per indumento, tipo, periodo, operatore; filtri contestuali per taglia e per carico.
- **Catalogo** (`/catalogo`): elenco, form indumento con taglie (gruppi rapidi S–XXL / 39–46 / Unica, riordino, scorta minima, disattivazione; eliminazione solo per taglie senza movimenti), **categorie** (`/catalogo/categorie`).
- **Colleghi** e **Commesse**: elenco con ricerca, stato, numero consegne e ultima consegna; creazione/modifica nel drawer.
- Paginazione condivisa (trait `ConPaginazione`, vista `livewire/partials/paginazione`), componenti `textarea` e `checkbox`.
- Voci della sidebar e azioni rapide della dashboard ora attive.

**Verifiche eseguite**
- `php artisan test` (SQLite) → 65 passati, 2 saltati (CHECK solo MySQL).
- `DB_CONNECTION=mariadb DB_DATABASE=ferretti_vestiario_test php artisan test` → 67 passati.
- `magazzino:verifica` sul DB di sviluppo → giacenze allineate.
- Tutte le pagine della Fase 2 aperte in Chrome headless (1440×900 e 1366×768) con interazioni reali (filtri, drawer, compilazione carico): nessun errore in console o di rete.

**Scelte da confermare** → vedi PENDING_REQUIREMENTS.md (date future, eliminazione taglie, gruppi di taglie).

## Fase 3 — Consegne ✅ (8 ottobre 2026)

**Completato**
- `ConsegnaService`:
  - `registra()`: in un'unica transazione numero annuale `AAAA-NNNNN`, righe aggregate con snapshot e scarico. Prima di aprirla rifiuta colleghi, commesse e taglie disattivati e le date future; il doppio invio è gestito con `idempotency_key`.
  - `annulla()`: lock della consegna, motivazione obbligatoria, compensazione delle giacenze; un secondo annullamento viene rifiutato.
- Numerazione su tabella contatore `numerazione_consegne` (migration `2026_10_08_100200`): vedi DATABASE.md.
- **Nuova consegna** (`/consegne/nuova`): ricerca di collega e commessa (Invio sceglie il primo), ricerca articoli con disponibilità, taglie con disponibilità (le esaurite non sono selezionabili), segnalazione immediata delle quantità eccedenti, gestione della disponibilità cambiata nel frattempo.
- **Elenco consegne** (`/consegne`): ricerca per numero o collega, filtri per commessa, stato e periodo; filtro per collega o commessa aperto dai rispettivi elenchi.
- **Dettaglio** (`/consegne/{id}`): righe da snapshot, destinatario, link ai movimenti (nuovo filtro `consegna` nello storico), annullamento con finestra di conferma e banner per le consegne annullate.
- Seeder demo convertito a `ConsegnaService`, con 2 consegne annullate dimostrative.
- Nuova eccezione `OperazioneNonConsentitaException` (messaggio mostrabile all'operatore).

**Verifiche eseguite**
- `php artisan test` (SQLite) → 81 passati, 2 saltati.
- Suite su MariaDB (`ferretti_vestiario_test`) → 83 passati.
- **Concorrenza reale su MariaDB**:
  - processi PHP paralleli: 200 conferme simultanee → 0 errori, progressivi consecutivi, giacenze esatte;
  - scorte contese (30 capi, 80 richieste) → esattamente 30 consegne, nessuna giacenza negativa, nessun numero saltato.
  - La prima versione, basata su `MAX() FOR UPDATE`, andava in deadlock: sostituita dal contatore.
- Flusso completo in Chrome headless: ricerca collega/commessa/articolo, avviso di eccedenza, conferma, dettaglio, annullamento. Nessun errore in console o di rete.

**Nota sul DB di sviluppo**: la verifica ha registrato e poi annullato la consegna 2026-00046 con l'operatore tecnico "Verifica UI" (`verifica.ui@example.invalid`), poi disattivato. Si eliminano con `php artisan migrate:fresh --seed`.

## Fase 4 — Documenti Word ✅ (8 ottobre 2026)

**Completato**
- PHPWord 1.4 (`phpoffice/phpword`).
- `DocumentoConsegnaService`:
  - genera il documento dal modello `.docx` con `TemplateProcessor` **dopo** il commit della consegna e lo archivia su disco privato;
  - registra hash SHA-256 e versione del modello, e non rigenera mai un documento riuscito;
  - in caso di errore registra stato `fallito`, messaggio e tentativi, senza toccare la consegna;
  - lock sulla riga del documento contro generazioni doppie; rifiuta segnaposto sconosciuti nel modello.
- Modello **provvisorio** `resources/templates/consegna.docx`, creato dal comando `php artisan vestiario:modello-word [--force]`. Segnaposto e sostituzione del modello sono descritti in **DOCUMENTI.md**.
- Download autenticato `GET /consegne/{id}/documento`, che restituisce sempre il file archiviato: se il file manca indica il ripristino da backup, se l'hash non corrisponde scrive un avviso nel log.
- Dopo la conferma della consegna il documento si scarica automaticamente. Nel dettaglio c'è il pannello "Documento Word": scarica, oppure **Riprova** dopo un errore, oppure **Genera documento** per le consegne che non ne hanno uno.
- Configurazione: `VESTIARIO_TEMPLATE_CONSEGNA`, `VESTIARIO_DOCUMENTI_DISCO`. Disattivato `serve` sul disco `local`: nessun URL diretto ai file privati.
- I test usano sempre un disco finto (`tests/TestCase.php`): nessun file di test nello storage reale.

**Verifiche eseguite**
- `php artisan test` (SQLite) → 91 passati, 2 saltati; MariaDB → 93 passati.
- Contenuto del `.docx` verificato nei test: dati, righe clonate, escaping di `& < >`, nessun segnaposto residuo.
- Documento di esempio aperto in Microsoft Word e convertito in PDF: impaginazione corretta, nessun avviso di riparazione.
- Chrome headless: conferma consegna con download automatico e "Genera documento" su una consegna demo; file scaricati con il nome atteso, nessun errore in console o di rete.

**Nota sul DB di sviluppo**: la verifica ha creato la consegna 2026-00047 ("Verifica UI") e i documenti di 2026-00047 e 2026-00001 in `storage/app/private/consegne/2026/`.

## Fase 5 — Rifinitura ⏳
## Fase 6 — Analisi documenti aziendali ⏸ (in attesa di Excel e modello Word)
