<?php

namespace Tests\Feature\Consegne;

use App\Enums\StatoDocumento;
use App\Enums\TipoMovimento;
use App\Exceptions\OperazioneNonConsentitaException;
use App\Livewire\Consegne\Dettaglio;
use App\Livewire\Consegne\NuovaConsegna;
use App\Models\Collega;
use App\Models\Commessa;
use App\Models\Consegna;
use App\Models\Indumento;
use App\Models\IndumentoVariante;
use App\Models\User;
use App\Services\ConsegnaService;
use App\Services\DocumentoConsegnaService;
use App\Services\MagazzinoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class DocumentoConsegnaTest extends TestCase
{
    use RefreshDatabase;

    private User $operatore;

    private DocumentoConsegnaService $documenti;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->operatore = User::factory()->create(['name' => 'Laura Magazzino']);
        $this->actingAs($this->operatore);
        $this->documenti = app(DocumentoConsegnaService::class);
    }

    public function test_genera_e_archivia_il_documento_con_dati_e_righe(): void
    {
        $consegna = $this->consegna(note: "Primo giorno & cantiere <nord>\nportare badge");

        $documento = $this->documenti->genera($consegna);

        $this->assertSame(StatoDocumento::Generato, $documento->stato);
        $this->assertSame('DAngelo_Nicolo_2026-03-10_Consegna_2026-00001.docx', $documento->nome_file);
        $this->assertSame('consegne/2026/DAngelo_Nicolo_2026-03-10_Consegna_2026-00001.docx', $documento->percorso);
        $this->assertSame(1, $documento->tentativi);
        $this->assertNotNull($documento->generato_at);
        $this->assertStringStartsWith('consegna.docx@', $documento->template_versione);
        Storage::disk('local')->assertExists($documento->percorso);
        $this->assertSame(hash('sha256', Storage::disk('local')->get($documento->percorso)), $documento->hash_sha256);

        $xml = $this->xmlDocumento($documento->percorso);
        $this->assertStringContainsString('2026-00001', $xml);
        $this->assertStringContainsString('Angelo Nicolò', $xml);
        $this->assertStringContainsString('M-77', $xml);
        $this->assertStringContainsString('Cantiere Sud', $xml);
        $this->assertStringContainsString('Laura Magazzino', $xml);
        $this->assertStringContainsString('Polo blu', $xml);
        $this->assertStringContainsString('Giacca', $xml);
        $this->assertStringContainsString('Primo giorno &amp; cantiere &lt;nord&gt; — portare badge', $xml);
        $this->assertStringNotContainsString('${', $xml);
        // Una riga clonata per ogni articolo
        $this->assertSame(1, substr_count($xml, 'POL-1'));
        $this->assertSame(1, substr_count($xml, 'GIA-1'));
    }

    public function test_un_documento_generato_non_viene_rigenerato(): void
    {
        $consegna = $this->consegna();
        $primo = $this->documenti->genera($consegna);
        $this->travel(1)->hours();

        $secondo = $this->documenti->genera($consegna);

        $this->assertTrue($primo->generato_at->equalTo($secondo->generato_at));
        $this->assertSame(1, $secondo->tentativi);
    }

    public function test_errore_di_generazione_registrato_e_ritentabile_senza_toccare_la_consegna(): void
    {
        $consegna = $this->consegna();
        $giacenze = IndumentoVariante::pluck('giacenza', 'id')->all();
        config(['vestiario.documenti.template' => storage_path('non-esiste.docx')]);

        $documento = $this->documenti->genera($consegna);

        $this->assertSame(StatoDocumento::Fallito, $documento->stato);
        $this->assertStringContainsString('Modello Word non trovato', $documento->ultimo_errore);
        $this->assertSame(1, $documento->tentativi);
        $this->assertFalse($consegna->fresh()->isAnnullata());
        $this->assertSame($giacenze, IndumentoVariante::pluck('giacenza', 'id')->all());

        config(['vestiario.documenti.template' => resource_path('templates/consegna.docx')]);
        $documento = $this->documenti->genera($consegna);

        $this->assertSame(StatoDocumento::Generato, $documento->stato);
        $this->assertNull($documento->ultimo_errore);
        $this->assertSame(2, $documento->tentativi);
    }

    public function test_un_modello_con_segnaposto_sconosciuti_viene_rifiutato(): void
    {
        $modello = tempnam(sys_get_temp_dir(), 'modello_');
        $zip = new ZipArchive;
        copy(resource_path('templates/consegna.docx'), $modello);
        $zip->open($modello);
        $zip->addFromString('word/document.xml', str_replace('${note}', '${campo_inventato}', $zip->getFromName('word/document.xml')));
        $zip->close();
        config(['vestiario.documenti.template' => $modello]);

        $documento = $this->documenti->genera($this->consegna());

        $this->assertSame(StatoDocumento::Fallito, $documento->stato);
        $this->assertStringContainsString('campo_inventato', $documento->ultimo_errore);
        @unlink($modello);
    }

    public function test_consegna_annullata_senza_documento_non_si_genera(): void
    {
        $consegna = $this->consegna();
        app(ConsegnaService::class)->annulla($consegna, $this->operatore, 'Errore');

        $this->expectException(OperazioneNonConsentitaException::class);
        $this->documenti->genera($consegna);
    }

    public function test_download_del_file_archiviato(): void
    {
        $consegna = $this->consegna();
        $documento = $this->documenti->genera($consegna);

        $this->get(route('consegne.documento', $consegna))
            ->assertOk()
            ->assertDownload($documento->nome_file);

        // Il file archiviato viene restituito tale e quale, anche se il catalogo cambia.
        Indumento::query()->update(['nome' => 'Nome cambiato']);
        $risposta = $this->get(route('consegne.documento', $consegna));
        $this->assertSame($documento->hash_sha256, hash('sha256', $risposta->streamedContent()));
    }

    public function test_download_rifiutato_se_manca_il_documento_o_il_file(): void
    {
        $consegna = $this->consegna();

        $this->get(route('consegne.documento', $consegna))
            ->assertRedirect(route('consegne.show', $consegna))
            ->assertSessionHas('toast.type', 'error');

        $documento = $this->documenti->genera($consegna);
        Storage::disk('local')->delete($documento->percorso);

        $this->get(route('consegne.documento', $consegna))
            ->assertRedirect(route('consegne.show', $consegna))
            ->assertSessionHas('toast.message', fn ($m) => str_contains($m, 'backup'));
    }

    public function test_il_download_richiede_l_accesso(): void
    {
        $consegna = $this->consegna();
        $this->documenti->genera($consegna);
        auth()->logout();

        $this->get(route('consegne.documento', $consegna))->assertRedirect(route('login'));
    }

    public function test_la_conferma_genera_il_documento_e_avvia_il_download(): void
    {
        $variante = $this->varianteDisponibile('Polo blu', 'M', 3);
        $collega = Collega::factory()->create();
        $commessa = Commessa::factory()->create();

        Livewire::test(NuovaConsegna::class)
            ->call('scegliCollega', $collega->id)
            ->call('scegliCommessa', $commessa->id)
            ->call('aggiungiArticolo', $variante->indumento_id)
            ->set('righe.0.variante_id', (string) $variante->id)
            ->call('conferma')
            ->assertHasNoErrors();

        $consegna = Consegna::sole();
        $this->assertSame(StatoDocumento::Generato, $consegna->documento->stato);
        $this->assertTrue(session('scarica_documento'));

        $this->get(route('consegne.show', $consegna))->assertSee(route('consegne.documento', $consegna), escape: false);
    }

    public function test_se_la_generazione_fallisce_la_consegna_resta_e_si_riprova_dal_dettaglio(): void
    {
        $variante = $this->varianteDisponibile('Polo blu', 'M', 3);
        config(['vestiario.documenti.template' => storage_path('non-esiste.docx')]);

        Livewire::test(NuovaConsegna::class)
            ->call('scegliCollega', Collega::factory()->create()->id)
            ->call('scegliCommessa', Commessa::factory()->create()->id)
            ->call('aggiungiArticolo', $variante->indumento_id)
            ->set('righe.0.variante_id', (string) $variante->id)
            ->call('conferma')
            ->assertHasNoErrors();

        $consegna = Consegna::sole();
        $this->assertSame(StatoDocumento::Fallito, $consegna->documento->stato);
        $this->assertSame('warning', session('toast.type'));
        $this->assertSame(2, $variante->fresh()->giacenza);

        config(['vestiario.documenti.template' => resource_path('templates/consegna.docx')]);

        Livewire::test(Dettaglio::class, ['consegna' => $consegna])
            ->assertSee('Generazione non riuscita')
            ->call('generaDocumento')
            ->assertDispatched('scarica-documento', url: route('consegne.documento', $consegna))
            ->assertSee('Scarica documento');
    }

    private function consegna(?string $note = null): Consegna
    {
        $polo = $this->varianteDisponibile('Polo blu', 'M', 5, 'POL-1');
        $giacca = $this->varianteDisponibile('Giacca', 'L', 5, 'GIA-1');

        return app(ConsegnaService::class)->registra(
            Collega::factory()->create(['cognome' => "D'Angelo", 'nome' => 'Nicolò', 'matricola' => 'M-77']),
            Commessa::factory()->create(['codice' => 'C-9', 'nome' => 'Cantiere Sud']),
            [['variante_id' => $polo->id, 'quantita' => 2], ['variante_id' => $giacca->id, 'quantita' => 1]],
            $this->operatore,
            Carbon::create(2026, 3, 10, 8, 45),
            $note,
        );
    }

    private function varianteDisponibile(string $nome, string $taglia, int $quantita, ?string $codice = null): IndumentoVariante
    {
        $variante = IndumentoVariante::factory()
            ->for(Indumento::factory()->state(array_filter(['nome' => $nome, 'codice' => $codice])))
            ->create(['taglia' => $taglia]);

        app(MagazzinoService::class)->registraMovimenti(TipoMovimento::Carico, [['variante_id' => $variante->id, 'quantita' => $quantita]], $this->operatore, data: Carbon::create(2026, 1, 1));

        return $variante;
    }

    private function xmlDocumento(string $percorso): string
    {
        $file = tempnam(sys_get_temp_dir(), 'doc_');
        file_put_contents($file, Storage::disk('local')->get($percorso));
        $zip = new ZipArchive;
        $zip->open($file);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        @unlink($file);

        return $xml;
    }
}
