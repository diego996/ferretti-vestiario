# Flussi operativi

Stato: ✅ implementato · 🔜 fase successiva (indicata).

## Accesso ✅
1. L'amministratore crea l'operatore: `php artisan vestiario:crea-utente`.
2. L'operatore accede con email e password personali. Dopo 5 tentativi falliti l'accesso è bloccato temporaneamente.
3. Un operatore disattivato non può accedere; se ha una sessione aperta viene disconnesso.

## Carico merce ✅
1. *Carico merce*: per ogni riga indumento → taglia → quantità (solo indumenti e taglie attivi); accanto compare la giacenza attuale e quella risultante.
2. Data operativa modificabile (default: ora, non futura), note facoltative (fornitore, DDT…).
3. Conferma: in un'unica transazione si creano carico, righe (aggregate per variante), movimenti positivi e aggiornamento giacenze.
4. Il pannello *Ultimi carichi* porta ai movimenti del singolo carico.

## Rettifica inventariale ✅
*Magazzino* → *Dettaglio* sulla taglia → Aumenta/Riduci, quantità e motivazione obbligatoria. Una rettifica negativa non può portare la giacenza sotto zero. Il movimento resta nello storico con la motivazione.

## Anagrafiche ✅
- *Catalogo indumenti*: nuovo/modifica indumento con le taglie. Le taglie nascono a giacenza zero; una taglia con movimenti non si elimina ma si disattiva. Il nome di una taglia si può correggere: le consegne conservano lo snapshot.
- *Categorie*, *Colleghi*, *Commesse*: creazione e modifica nel pannello laterale. Non si cancellano: si disattivano (restano nello storico).

## Storico movimenti ✅
Elenco di tutti i movimenti con filtri per indumento, tipo, periodo e operatore. Dal drawer del Magazzino (*Storico completo*) e dagli ultimi carichi si apre già filtrato.

## Nuova consegna ✅
1. Collega e commessa: si digita (cognome, nome, matricola / codice, nome) e si sceglie dall'elenco; **Invio** sceglie il primo risultato. Solo colleghi e commesse attivi. Dal link `/consegne/nuova?collega=ID` il collega è già impostato.
2. Articoli: si cerca per nome o codice (l'elenco mostra le taglie e i capi disponibili) e Invio aggiunge la riga. Per ogni riga si sceglie la taglia, che mostra la disponibilità (le esaurite non sono selezionabili), e la quantità. Se un articolo ha una sola taglia disponibile è già selezionata.
3. Quantità oltre la disponibilità (anche sommando più righe della stessa taglia) segnalate subito in rosso.
4. Le righe sono solo in bozza nel componente: nessun movimento finché non si conferma. La conferma è un pulsante (non un invio di form): un Invio accidentale non registra nulla.
5. Conferma (pulsante disabilitato durante l'invio + `idempotency_key`): in un'unica transazione
   - lock sul contatore dell'anno → numero progressivo `AAAA-NNNNN`;
   - consegna + righe aggregate con snapshot (codice, nome, taglia);
   - lock varianti in ordine, verifica disponibilità sui valori bloccati, movimenti `scarico_consegna`.
   Se nel frattempo la disponibilità è cambiata, le righe interessate vengono segnalate e non si salva nulla.
6. Dopo il commit si genera il documento Word e il browser lo scarica; si apre il dettaglio della consegna. Se la generazione non riesce, la consegna resta valida e un avviso invita a riprovare dal dettaglio.

## Elenco e dettaglio consegne ✅
Elenco con ricerca (numero o collega), commessa, stato, periodo. Dagli elenchi Colleghi e Commesse il numero di consegne apre l'elenco filtrato. Il dettaglio mostra righe (snapshot), destinatario, operatore e porta ai movimenti di magazzino della consegna.

## Annullamento consegna ✅
Dal dettaglio, *Annulla consegna* → finestra di conferma con motivazione obbligatoria. In transazione: lock della consegna, stato `annullata`, chi/quando/motivo, movimenti `compensazione_annullamento` (+) per ogni riga con data = momento dell'annullamento. La consegna e il Word originale restano nello storico (il documento resta scaricabile), con un banner che spiega chi l'ha annullata e perché. Un secondo annullamento viene rifiutato, anche se due operatori ci provano contemporaneamente.

## Documento Word ✅
Nome file `Cognome_Nome_AAAA-MM-GG_Consegna_NUMERO.docx` (sanitizzato). Archiviato su storage privato, riscaricabile dal dettaglio senza rigenerazione. Se la generazione fallisce: consegna e movimenti restano invariati, l'errore viene registrato e si preme *Riprova*. Le consegne senza documento (demo, importate) hanno *Genera documento*. Dettagli, segnaposto e sostituzione del modello: **DOCUMENTI.md**.

## Verifica giacenze ✅
`php artisan magazzino:verifica` confronta giacenza e somma movimenti per ogni variante. Consigliato dopo ripristini da backup o interventi manuali sul DB.

## Dati demo ✅
`php artisan migrate:fresh --seed` (solo fuori produzione) crea catalogo, 28 colleghi (`DEMO-nnn`), 6 commesse (`DEMO-nnn`), carichi e consegne passando dai service: le giacenze sono riconciliate. Con `VESTIARIO_DEMO_MODE=true` l'header mostra il contrassegno "Dati demo".
