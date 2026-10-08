# Questioni aperte

## Da fornire

| # | Materiale | Serve per | Impatto |
|---|---|---|---|
| 1 | File Excel del magazzino | Fase 6: importazione | Colonne, regole carico/scarico, taglie reali, possibilità di ricostruire le consegne storiche |
| 2 | Modello Word aziendale di consegna e testo della dichiarazione di ricevuta | Fase 6 | Oggi si usa un modello provvisorio (piè di pagina "MODELLO PROVVISORIO"). Per sostituirlo bastano gli stessi segnaposto: vedi DOCUMENTI.md |
| 3 | Logo ufficiale (SVG/PNG) e autorizzazione all'uso interno | Branding | Si attiva con `VESTIARIO_LOGO_PATH`; oggi marchio provvisorio |
| 4 | Conferma palette | Branding | Colori ricavati dal CSS pubblico del sito, non da un manuale ufficiale |

## Ipotesi adottate (da confermare)

| Ipotesi | Dove | Alternativa se non confermata |
|---|---|---|
| Numero consegna annuale `AAAA-NNNNN`, riparte da 1 ogni anno | `consegne.anno/progressivo/numero` | Numerazione continua senza anno |
| "Scorta bassa" = giacenza ≤ scorta minima (uguale compreso) | `StatoScorta` | Strettamente minore |
| Il KPI "sotto scorta" include le varianti esaurite | Dashboard | Conteggi separati |
| Il collega è identificato in elenco come "Cognome Nome" | `Collega::nominativo` | "Nome Cognome" |
| Commessa con codice facoltativo ma univoco se presente | `commesse.codice` | Codice obbligatorio |
| Data operativa di consegna modificabile (come per i carichi), mai futura | `ConsegnaService` | Solo data/ora corrente |
| Il numero di una consegna annullata non viene riutilizzato | `numerazione_consegne` | Riassegnazione del numero |
| I movimenti di compensazione hanno la data dell'annullamento (non quella della consegna) | `ConsegnaService::annulla` | Data della consegna originale |
| Annullamento consentito a qualsiasi operatore, senza limiti di tempo | Dettaglio consegna | Solo entro N giorni / solo alcuni utenti |
| Una consegna confermata non si modifica: si annulla e se ne registra una nuova | Fase 3 | Modifica con storico delle versioni |
| Il documento si scarica in automatico dopo la conferma (non si stampa direttamente) | Nuova consegna | Apertura dell'anteprima di stampa / PDF |
| Formato solo `.docx` | `DocumentoConsegnaService` | Anche PDF (richiede LibreOffice o simili sul server) |
| Un file archiviato mancante non viene rigenerato: va ripristinato dal backup | Download | Rigenerazione con il modello attuale |
| Le consegne demo non hanno documento finché non si preme "Genera documento" | Seeder | Generazione massiva nel seeder |
| Nessuna gestione colore delle varianti | Schema | Aggiungere campo/varianti per colore |
| Un solo documento archiviato per consegna; nessuna nuova versione dopo la generazione riuscita | `documenti_consegna.consegna_id` unique | Versioning dei documenti |
| La data di un carico non può essere futura | `CaricoService` | Ammettere carichi pre-datati |
| Una taglia si elimina solo se non ha movimenti; altrimenti si disattiva | Catalogo | Solo disattivazione |
| Categorie, colleghi e commesse non si eliminano, si disattivano | Anagrafiche | Eliminazione se mai usati |
| Gruppi rapidi di taglie: S–XXL, 39–46, Unica | `FormIndumento::PRESET` | Gruppi ricavati dall'Excel (Fase 6) |
| Rettifica possibile anche su taglie disattivate | Magazzino | Solo taglie attive |
| Gli operatori vengono creati da riga di comando (nessuna gestione utenti in UI) | `vestiario:crea-utente` | Pagina utenti in Impostazioni (Fase 5) |

## Importazione Excel — da analizzare quando arrivano i file
1. Struttura workbook/fogli (un file per tipologia, un foglio per taglia?).
2. Mappatura tipologie → indumenti/categorie, nomi foglio → taglie.
3. Significato delle righe: carichi, scarichi, rettifiche; colonne data/ora/quantità/tipo.
4. Presenza di destinatario e commessa negli scarichi (per ricostruire le consegne) oppure import solo come saldi iniziali.
5. Normalizzazione e deduplicazione (taglie scritte in modi diversi, omonimi).
6. Validazione: saldo finale Excel = giacenza calcolata.
7. Procedura verificabile: anteprima, report differenze, import in transazione, `magazzino:verifica`.

L'import non va implementato prima di aver visto i file.

## Ambiente
- In sviluppo si usa MariaDB 10.4 (XAMPP). La produzione prevista è MySQL 8: lo schema è compatibile con entrambi; eseguire la suite su MySQL 8 prima del rilascio.
