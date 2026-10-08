# Documento di consegna (Word)

## Come funziona

1. Alla conferma, la consegna viene salvata e committata.
2. **Dopo** il commit, `DocumentoConsegnaService::genera()` compila il modello `.docx` e archivia il file sul disco privato:
   - percorso: `storage/app/private/consegne/AAAA/<nome file>`;
   - nome file: `Cognome_Nome_AAAA-MM-GG_Consegna_NUMERO.docx`, con accenti traslitterati, apostrofi eliminati e ogni altro carattere non sicuro sostituito da `_` (es. `DAngelo_Nicolo_2026-03-10_Consegna_2026-00001.docx`).
3. Il browser scarica il file. Il dettaglio della consegna permette di riscaricarlo in qualsiasi momento.
4. **Il documento non viene mai rigenerato** dopo una generazione riuscita: il download restituisce sempre il file originale, anche se nel frattempo il catalogo o il modello sono cambiati.
   - In `documenti_consegna` restano l'hash SHA-256 e la versione del modello usata.
   - Se il file risulta modificato, il download avviene comunque e viene registrato un avviso nel log.
5. **Se la generazione fallisce** (modello mancante, segnaposto sconosciuti, disco non scrivibile):
   - consegna e movimenti restano validi;
   - il documento passa a `fallito` con il messaggio di errore e il numero di tentativi, e l'errore viene registrato nel log;
   - dal dettaglio si preme **Riprova**.
6. Le consegne registrate senza documento (es. dati demo o importati) mostrano **Genera documento**. Per una consegna annullata il documento non si genera più; quello eventualmente già generato resta scaricabile.

Il download passa solo dalla rotta autenticata `GET /consegne/{id}/documento`. Il disco `local` non espone URL diretti (`serve => false`).

## Segnaposto del modello

Nel modello i segnaposto si scrivono `${nome}`.

| Segnaposto | Contenuto | Esempio |
|---|---|---|
| `${azienda}` | `VESTIARIO_COMPANY_NAME` | Ferretti Construction |
| `${numero}` | numero consegna | 2026-00042 |
| `${data}` / `${ora}` | data e ora della consegna | 10/03/2026 / 08:45 |
| `${collega}` | "Cognome Nome" | Rossi Anna |
| `${collega_cognome}` / `${collega_nome}` | separati | Rossi / Anna |
| `${matricola}` | matricola o "—" | M-77 |
| `${commessa}` | "CODICE · Nome" (o solo nome) | C-9 · Cantiere Sud |
| `${commessa_codice}` / `${commessa_nome}` | separati | C-9 / Cantiere Sud |
| `${operatore}` | operatore che ha registrato | Laura Bianchi |
| `${note}` | note (a capo → " — ") o "—" | |
| `${totale_capi}` | somma quantità | 8 |

**Riga articoli**: in una riga di tabella vanno messi `${riga_codice}`, `${riga_articolo}`, `${riga_taglia}` e `${riga_quantita}`. La riga che contiene `${riga_articolo}` viene ripetuta per ogni articolo, in ordine di nome. Codice, nome e taglia sono quelli registrati al momento della consegna (snapshot).

Si possono usare tutti i segnaposto, solo alcuni o ripeterli. Un segnaposto **non** presente in questo elenco fa fallire la generazione con un messaggio esplicito: così non si archivia un documento con campi non compilati.

## Sostituire il modello provvisorio con quello aziendale

1. Aprire il modello aziendale in Word e inserire i segnaposto.
   - Digitare ogni `${...}` di seguito, senza formattarne solo una parte: Word non deve spezzarlo in più "run".
   - Se un segnaposto non viene riconosciuto, ridigitarlo da capo.
2. Salvare il file, ad esempio in `resources/templates/consegna-aziendale.docx`, e indicarlo in `.env`: `VESTIARIO_TEMPLATE_CONSEGNA=/percorso/assoluto/consegna-aziendale.docx`. In alternativa sostituire direttamente `resources/templates/consegna.docx`.
3. Verificare con una consegna di prova. Il campo `template_versione` (nome file + hash) registra quale modello è stato usato per ogni documento.

I documenti già archiviati non cambiano.

Il modello provvisorio si ricrea con `php artisan vestiario:modello-word --force` e riporta nel piè di pagina "MODELLO PROVVISORIO". Anche il testo della dichiarazione di ricevuta è provvisorio, da sostituire con quello aziendale.

## Backup

I file in `storage/app/private/consegne` fanno parte dello storico: vanno salvati insieme al database (vedi ARCHITECTURE.md → Backup). Se un file manca, il dettaglio lo segnala e il download indica di ripristinarlo dal backup. Il file non viene rigenerato automaticamente.
