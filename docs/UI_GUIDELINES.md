# Linee guida UI

Obiettivo: pulizia di un SaaS moderno + densità e velocità di un gestionale. Desktop first (1366×768, 1440×900, 1920×1080).

## Branding

Analisi del sito ufficiale https://www.ferretti-construction.com/it/ (8 ottobre 2026):

| Elemento | Valore rilevato | Fonte | Uso nell'app |
|---|---|---|---|
| Font | Inter | foglio Google Fonts del sito | font unico dell'app |
| Rosso logo | `#f12836` | `fill` del file `logo-ferretti-rosso.svg` | solo accento puntuale (voce attiva, marchio) |
| Navy "base" | `#0a2434` | variabile `--base` del tema del sito | sidebar, pannello login |
| "Primary" | `#5b99a6` | variabile `--primary` del tema | riferimento |
| "Action" | `#32a2c1` | variabile `--action` del tema | base della scala `primary` |
| "Accent" | `#bcc8a2` | variabile `--accent` del tema | non usato |

I valori provengono dai fogli di stile pubblici del sito, non da un manuale di identità: vanno confermati dall'azienda (vedi PENDING_REQUIREMENTS.md).

**Logo**: non incluso nel repository. Il logo ufficiale è un asset protetto: si attiva impostando `VESTIARIO_LOGO_PATH` (e `VESTIARIO_LOGO_DARK_PATH` per la sidebar scura) dopo l'autorizzazione all'uso interno. Fino ad allora viene mostrato un marchio provvisorio neutro (icona Lucide `shirt` + nome applicazione), senza imitare il logo. Favicon provvisoria in `public/brand/favicon.svg`, sostituibile con `VESTIARIO_FAVICON_PATH`.

## Palette (token Tailwind in `resources/css/app.css`)

| Token | Uso |
|---|---|
| `navy-950` `#0a2434` | sidebar, superfici istituzionali |
| `primary-600` `#1f7f9b` | pulsanti principali, link (contrasto bianco ≈ 4,6:1, AA) |
| `primary-700/800` | hover / active |
| `brand-500` `#f12836` | indicatore voce attiva, accento marchio. **Mai** per errori |
| `canvas` `#f4f6f8` | sfondo pagina |
| `line` / `line-strong` | bordi pannelli / bordi controlli |
| emerald | conferme, disponibile |
| amber | scorta bassa, avvisi |
| red | errori, esaurito, annullamenti |

Stati di disponibilità sempre con **colore + testo** (componente `x-ui.stock-status`).

## Tipografia

Inter variabile. Base tabelle/controlli 13 px; titoli pagina 15 px semibold; KPI 24 px semibold; etichette colonne 11 px maiuscolo. Numeri in tabelle e KPI con la classe `.tabular` (cifre a larghezza fissa).

## Layout

- Sidebar 248 px (60 px compressa, stato salvato nel browser). Voci non ancora disponibili: disabilitate con etichetta "Fase N".
- Header 56 px: titolo, breadcrumb opzionale, badge "Dati demo", **Nuova consegna**, menu utente.
- Contenuto: padding 24 px, larghezza max 1600 px.
- Pannelli: bordo 1 px, raggio 8 px, ombra quasi impercettibile. Niente gradienti, niente card giganti.

## Componenti (`resources/views/components/ui`)

| Componente | Note |
|---|---|
| `button` | varianti primary/secondary/ghost/danger/success, taglie sm/md/lg, `href` → link, `disabled` |
| `badge` | toni neutral/primary/success/warning/danger, `dot` |
| `stock-status` | badge da `StatoScorta` |
| `panel` | titolo, icona, slot `actions` e `footer`, `flush` per tabelle a filo |
| `kpi` | indicatore numerico con icona e nota |
| `field` + `input` + `select` | label, errore contestuale (`role=alert`, `aria-invalid`), hint |
| `empty-state` | stato vuoto con icona e azione opzionale |
| `skeleton` | righe segnaposto durante il caricamento |
| `toasts` | notifiche: `$this->dispatch('toast', type: 'success', message: '…')` o `session()->flash('toast', [...])` |
| `confirm-modal` | conferma azioni irreversibili; `$dispatch('open-confirm', {id})` |
| `drawer` | pannello laterale (storico movimenti); `$dispatch('open-drawer', {id})` |
| `textarea`, `checkbox` | stessi stati di errore di `input`; checkbox con etichetta inline |
| `brand` | logo configurato o marchio provvisorio |

Paginazione: trait `App\Livewire\Concerns\ConPaginazione` (50 righe per pagina, vista `livewire/partials/paginazione`, "Righe X–Y di Z").

Schema delle pagine elenco: barra filtri in un pannello (ricerca con debounce, select, "Azzera filtri" solo se servono, azione principale a destra) → tabella in pannello `flush` → paginazione. Creazione/modifica di anagrafiche semplici nel `drawer`; l'indumento, che ha le taglie, ha una pagina propria.

Tabelle: classe `.data-table` (intestazioni compatte, righe 36 px, hover leggero).

## Accessibilità

Focus visibile ovunque (`:focus-visible`), link "Vai al contenuto", `aria-current` sulla voce attiva, `aria-disabled` + `title` sulle funzioni future, modali/drawer con focus trap ed Esc, errori collegati ai campi.

## Regole

- Nessun pulsante finto: se la funzione non esiste è disabilitato e lo dice.
- Niente modali per operazioni ordinarie: usarle solo per conferme distruttive.
- Niente grafici decorativi.
- Feedback di caricamento su ogni azione che chiama il server (`wire:loading`).
