<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;

/**
 * Crea il modello Word PROVVISORIO del verbale di consegna.
 *
 * Il file generato contiene i segnaposto ${...} documentati in docs/DOCUMENTI.md.
 * Quando l'azienda fornirà il proprio modello basterà inserire gli stessi
 * segnaposto e indicarne il percorso in VESTIARIO_TEMPLATE_CONSEGNA.
 */
class CreaModelloWord extends Command
{
    protected $signature = 'vestiario:modello-word {--force : sovrascrive il modello esistente}';

    protected $description = 'Genera il modello Word provvisorio del verbale di consegna';

    private const NAVY = '0A2434';

    private const BORDO = 'BFC9D1';

    public function handle(): int
    {
        $destinazione = resource_path('templates/consegna.docx');

        if (is_file($destinazione) && ! $this->option('force')) {
            $this->components->error("Il modello esiste già: {$destinazione}. Usa --force per sovrascriverlo.");

            return self::FAILURE;
        }

        if (! is_dir(dirname($destinazione))) {
            mkdir(dirname($destinazione), 0775, true);
        }

        Settings::setOutputEscapingEnabled(true);
        IOFactory::createWriter($this->documento(), 'Word2007')->save($destinazione);

        $this->components->info("Modello creato: {$destinazione}");

        return self::SUCCESS;
    }

    private function documento(): PhpWord
    {
        $word = new PhpWord;
        $word->setDefaultFontName('Calibri');
        $word->setDefaultFontSize(10);
        $word->getDocInfo()->setTitle('Verbale di consegna vestiario e DPI')->setCreator('Gestione Vestiario');

        $etichetta = ['color' => '5B6B78', 'size' => 9];
        $valore = ['bold' => true, 'size' => 10];
        $intestazioneTabella = ['bold' => true, 'color' => 'FFFFFF', 'size' => 9];
        $cellaIntestazione = ['bgColor' => self::NAVY, 'valign' => 'center'];
        $senzaSpazio = ['spaceAfter' => 0];

        $sezione = $word->addSection([
            'marginTop' => 1000, 'marginBottom' => 1000, 'marginLeft' => 1100, 'marginRight' => 1100,
        ]);

        // Piè di pagina
        $sezione->addFooter()->addText(
            'Verbale ${numero} · generato da Gestione Vestiario · MODELLO PROVVISORIO da sostituire con il modello aziendale',
            ['size' => 8, 'color' => '8A97A3'],
            ['alignment' => Jc::CENTER],
        );

        // Intestazione
        $sezione->addText('${azienda}', ['bold' => true, 'size' => 12, 'color' => self::NAVY], $senzaSpazio);
        $sezione->addText('VERBALE DI CONSEGNA VESTIARIO E DPI', ['bold' => true, 'size' => 15, 'color' => self::NAVY], ['spaceBefore' => 120, 'spaceAfter' => 0]);
        $sezione->addText('N. ${numero} del ${data}', ['size' => 11, 'color' => '3D4D5A'], ['spaceAfter' => 240]);

        // Dati della consegna
        $dati = $sezione->addTable(['borderSize' => 4, 'borderColor' => self::BORDO, 'cellMargin' => 90, 'width' => 100 * 50, 'unit' => 'pct']);
        foreach ([
            ['Collega', '${collega}', 'Matricola', '${matricola}'],
            ['Commessa', '${commessa}', 'Data e ora', '${data} ${ora}'],
            ['Incaricato della consegna', '${operatore}', 'Numero', '${numero}'],
        ] as [$e1, $v1, $e2, $v2]) {
            $riga = $dati->addRow();
            $riga->addCell(2000)->addText($e1, $etichetta, $senzaSpazio);
            $riga->addCell(3600)->addText($v1, $valore, $senzaSpazio);
            $riga->addCell(1500)->addText($e2, $etichetta, $senzaSpazio);
            $riga->addCell(2400)->addText($v2, $valore, $senzaSpazio);
        }

        $sezione->addText('Articoli consegnati', ['bold' => true, 'size' => 11, 'color' => self::NAVY], ['spaceBefore' => 300, 'spaceAfter' => 100]);

        // Righe: la riga con ${riga_articolo} viene clonata per ogni articolo
        $word->addTableStyle('Articoli', ['borderSize' => 4, 'borderColor' => self::BORDO, 'cellMargin' => 80, 'alignment' => JcTable::CENTER]);
        $tabella = $sezione->addTable('Articoli');
        $intestazione = $tabella->addRow(null, ['tblHeader' => true]);
        $intestazione->addCell(1600, $cellaIntestazione)->addText('Codice', $intestazioneTabella, $senzaSpazio);
        $intestazione->addCell(5400, $cellaIntestazione)->addText('Articolo', $intestazioneTabella, $senzaSpazio);
        $intestazione->addCell(1300, $cellaIntestazione)->addText('Taglia', $intestazioneTabella, $senzaSpazio);
        $intestazione->addCell(1200, $cellaIntestazione)->addText('Quantità', $intestazioneTabella, ['alignment' => Jc::END, 'spaceAfter' => 0]);

        $riga = $tabella->addRow();
        $riga->addCell(1600)->addText('${riga_codice}', null, $senzaSpazio);
        $riga->addCell(5400)->addText('${riga_articolo}', null, $senzaSpazio);
        $riga->addCell(1300)->addText('${riga_taglia}', null, $senzaSpazio);
        $riga->addCell(1200)->addText('${riga_quantita}', null, ['alignment' => Jc::END, 'spaceAfter' => 0]);

        $totale = $tabella->addRow();
        $totale->addCell(8300, ['gridSpan' => 3])->addText('Totale capi', ['bold' => true], ['alignment' => Jc::END, 'spaceAfter' => 0]);
        $totale->addCell(1200)->addText('${totale_capi}', ['bold' => true], ['alignment' => Jc::END, 'spaceAfter' => 0]);

        $sezione->addText('Note: ${note}', ['size' => 9, 'color' => '3D4D5A'], ['spaceBefore' => 160]);

        // Dichiarazione (testo provvisorio: da sostituire con quello aziendale)
        $sezione->addText(
            'Il/La sottoscritto/a ${collega} dichiara di aver ricevuto in data ${data} gli indumenti e i dispositivi sopra elencati, '
            .'in buono stato, e si impegna a utilizzarli secondo le istruzioni ricevute e a conservarli con cura.',
            ['size' => 10],
            ['spaceBefore' => 300, 'spaceAfter' => 600, 'alignment' => Jc::BOTH],
        );

        // Firme
        $firme = $sezione->addTable(['cellMargin' => 80, 'alignment' => JcTable::CENTER]);
        $riga = $firme->addRow();
        foreach (['Firma del collega', 'Firma dell\'incaricato (${operatore})'] as $testo) {
            $cella = $riga->addCell(4600);
            $cella->addText('________________________________', ['color' => '8A97A3'], ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
            $cella->addText($testo, $etichetta, ['alignment' => Jc::CENTER]);
        }

        return $word;
    }
}
