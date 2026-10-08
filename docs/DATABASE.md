# Database

Charset `utf8mb4_unicode_ci`. Tutte le tabelle hanno `id` autoincrement e `created_at`/`updated_at`.
Le FK verso dati storici usano `RESTRICT`: non si può cancellare fisicamente ciò che è referenziato da movimenti, carichi o consegne (si disattiva con il flag `attivo`/`attiva`).

I vincoli `CHECK` sono creati solo su MySQL/MariaDB (`App\Support\CheckConstraint`); su SQLite (test) l'integrità è garantita dai service.

## Schema

```
categorie_indumenti 1─* indumenti 1─* indumento_varianti 1─* movimenti_magazzino
                                              │  1─* carico_righe   *─1 carichi   ─┐
                                              │  1─* consegna_righe *─1 consegne  ─┤ origine (polimorfica)
colleghi 1─* consegne *─1 commesse                     consegne 1─1 documenti_consegna
users 1─* carichi / consegne / movimenti (operatore)
```

## Tabelle

### `users`
Laravel standard + `attivo` (bool, default true). Login per email.

### `categorie_indumenti`
| Campo | Tipo | Note |
|---|---|---|
| nome | varchar(100) | **unique** |
| descrizione | varchar nullable | |
| ordinamento | smallint unsigned | ordine di visualizzazione |
| attiva | bool | |

Categorie iniziali da `CategorieInizialiSeeder` (idempotente), modificabili dall'anagrafica: non sono valori fissi nel codice.

### `indumenti`
| Campo | Tipo | Note |
|---|---|---|
| categoria_id | FK restrict | |
| codice | varchar(50) nullable | **unique se presente** (più NULL ammessi) |
| nome | varchar(150) | indice |
| descrizione | text nullable | |
| attivo | bool | indice (attivo, categoria_id) |

### `indumento_varianti`
| Campo | Tipo | Note |
|---|---|---|
| indumento_id | FK restrict | |
| taglia | varchar(20) | stringa libera (S, 42, 44/46, Unica…) |
| ordinamento | smallint unsigned | ordine taglie (S < M < L) |
| giacenza | int unsigned | **CHECK ≥ 0**, aggiornata solo da `MagazzinoService` |
| scorta_minima | int unsigned | |
| attiva | bool | |

**Unique (indumento_id, taglia)**. Indice su `giacenza`.

Stato disponibilità (`App\Enums\StatoScorta`): *Esaurito* se giacenza = 0; *Scorta bassa* se 0 < giacenza ≤ scorta minima; *Disponibile* altrimenti. Lo scope `daRiordinare()` (giacenza ≤ scorta minima, variante e indumento attivi) alimenta il KPI "Varianti sotto scorta".

### `colleghi`
nome, cognome (indice cognome+nome), matricola (nullable, **unique se presente**), attivo. Nessun vincolo su nome/cognome: gli omonimi sono ammessi e si usa sempre l'ID.

### `commesse`
codice (nullable, **unique se presente**), nome (indice), descrizione, attiva. Nessuna giacenza propria.

### `carichi` / `carico_righe`
`carichi`: data_carico (data operativa, può differire da created_at), user_id, note.
`carico_righe`: carico_id, variante_id, quantita (**CHECK > 0**), **unique (carico_id, variante_id)**: le righe duplicate vengono aggregate prima del salvataggio.

### `consegne`
| Campo | Tipo | Note |
|---|---|---|
| anno, progressivo | smallint / int | **unique (anno, progressivo)**: numerazione annuale |
| numero | varchar(20) | **unique**, formato `AAAA-NNNNN` (es. `2026-00042`) |
| collega_id, commessa_id | FK restrict | |
| data_consegna | datetime | |
| user_id | FK | operatore |
| stato | varchar(20) | **CHECK in (confermata, annullata)** |
| note | text nullable | |
| idempotency_key | uuid nullable **unique** | protezione dal doppio invio del form |
| annullata_at, annullata_da, motivo_annullamento | | valorizzati all'annullamento |

Indici: (stato, data_consegna), (collega_id, data_consegna), (commessa_id, data_consegna).

### `numerazione_consegne`
anno (smallint, **PK**), ultimo_progressivo (int unsigned). Una riga per anno, creata alla prima consegna dell'anno (partendo dal massimo progressivo esistente).

**Perché una tabella contatore.** `ConsegnaService` blocca la riga dell'anno con `SELECT … FOR UPDATE` e la incrementa nella stessa transazione della consegna. Un `MAX(progressivo) … FOR UPDATE` sulla tabella `consegne` prende *gap lock* che, con conferme simultanee, vanno in deadlock: verificato su MariaDB con 3 processi paralleli (46 conferme fallite su 120). Con il contatore: 4 processi × 50 conferme → 0 errori, progressivi consecutivi. Se la consegna fallisce (es. giacenza insufficiente) l'incremento viene annullato con la transazione: **nessun buco** nella numerazione.

### `consegna_righe`
consegna_id, variante_id, quantita (**CHECK > 0**), **unique (consegna_id, variante_id)**.
Snapshot descrittivo `indumento_codice`, `indumento_nome`, `taglia`: storico e documenti restano fedeli anche se il catalogo viene rinominato.

### `movimenti_magazzino`
| Campo | Tipo | Note |
|---|---|---|
| variante_id | FK restrict | |
| tipo | varchar(40) | **CHECK** su: carico, scarico_consegna, rettifica_positiva, rettifica_negativa, compensazione_annullamento |
| quantita | int con segno | **CHECK ≠ 0**; positiva aumenta, negativa riduce |
| giacenza_risultante | int unsigned | saldo dopo il movimento |
| origine_type, origine_id | nullable morph | `carico` / `consegna`; NULL per le rettifiche |
| user_id | FK | operatore |
| data_movimento | datetime | data operativa |
| note | text nullable | obbligatoria (lato service) per le rettifiche |

Indici: (variante_id, data_movimento), (tipo, data_movimento), data_movimento, (origine_type, origine_id).

**Riferimento polimorfico — scelta e limiti.** `origine` è polimorfico perché un movimento può nascere da operazioni diverse (carico, consegna, annullamento della stessa consegna) e altre origini potrebbero aggiungersi. Gli alias sono fissati con `Relation::enforceMorphMap` (`carico`, `consegna`), così il DB non dipende dai nomi delle classi.
Limite: MySQL non può imporre una FK su una colonna polimorfica. È mitigato da: creazione dei movimenti solo in `MagazzinoService`, FK `RESTRICT` su carichi/consegne verso le righe (nessuna cancellazione fisica), movimenti immutabili. In alternativa si potevano usare colonne nullable `carico_id` / `consegna_id` con FK reali: scartata per non moltiplicare colonne a ogni nuova origine.

**Riconciliazione.** Per ogni variante `giacenza = SUM(movimenti.quantita)`. Verifica: `php artisan magazzino:verifica` (exit code 1 e log warning in caso di differenze).

### `documenti_consegna`
consegna_id (**unique**: un documento archiviato per consegna), stato (**CHECK** in_attesa / generato / fallito), nome_file, disco, percorso, template_versione, hash_sha256, tentativi, ultimo_errore, generato_at. Un tentativo fallito aggiorna la stessa riga; il download non rigenera un documento già archiviato.
