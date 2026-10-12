<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\ProgramDocument;
use App\Services\DocumentAiAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DocumentAiExtractionTest extends TestCase
{
    use RefreshDatabase;

    private const INVOICE_REPLY = '{"invoice_no": "INV-2026-000022", "faktur_number": "010.002-26.55910294", "faktur_date": "2026-09-21", "dpp_amount": 15315315, "ppn_amount": 1684685, "total_amount": 17000000, "payment_status": "PAID"}';

    private const FAKTUR_REPLY = '{"invoice_no": "INV-2026-000022", "faktur_number": "04002600333766027", "faktur_date": "2026-09-21", "dpp_amount": 15315315, "ppn_amount": 1684685, "total_amount": 17000000, "payment_status": "PAID"}';

    /** Berkas yang tersimpan lokal (S3 dimatikan di phpunit.xml), dihapus setelah tes */
    private array $storedFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config([
            'services.ai.base_url' => 'https://ai.test/v1',
            'services.ai.api_key' => 'test-key',
            'services.ai.model' => 'test-model',
        ]);

        Program::create([
            'id' => '1',
            'title' => 'Program Test',
            'supplier' => 'PT Vendor Test',
            'invoice_no' => '2602000033',
            'dpp_amount' => 450450,
            'total_amount' => 450450,
            'payment_status' => 'WAITING PAYMENT',
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->storedFiles as $path) {
            @unlink($path);
        }
        parent::tearDown();
    }

    private function fakeAiReply(string $content): void
    {
        Http::fake(['ai.test/*' => Http::response(['choices' => [['message' => ['content' => $content]]]])]);
    }

    private function upload(string $type, array $extra = [])
    {
        $response = $this->post('/api/programs/1/documents', [
            'document_type' => $type,
            'file' => UploadedFile::fake()->createWithContent('INV-2026-000022.pdf', '%PDF-1.4 test'),
            ...$extra,
        ], [
            'Accept' => 'application/json',
            'X-User-Role' => 'Admin SCM'
        ]);

        $response->assertOk();
        $document = ProgramDocument::where('id', $response->json('document.id'))->firstOrFail();
        $this->storedFiles[] = public_path($document->file_path);

        return $document;
    }

    /** AI hanya boleh mengisi No. Invoice / No. Faktur; DPP, PPN, total, tanggal, dan status bayar tetap */
    private function assertProgramNumbers(string $invoiceNo, ?string $fakturNumber): void
    {
        $program = Program::find('1');
        $this->assertSame($invoiceNo, $program->invoice_no);
        $this->assertSame($fakturNumber, $program->faktur_number);
        $this->assertEquals(450450, $program->dpp_amount);
        $this->assertEquals(0, $program->ppn_amount);
        $this->assertEquals(450450, $program->total_amount);
        $this->assertNull($program->faktur_date);
        $this->assertSame('WAITING PAYMENT', $program->payment_status);
    }

    public function test_json_followed_by_extra_text_is_still_read()
    {
        $this->fakeAiReply(self::INVOICE_REPLY . "\n→ skipped: [DB persistence, multi-page loop], add when [batch processing required].");
        $file = tempnam(sys_get_temp_dir(), 'ai_') . '.pdf';
        file_put_contents($file, '%PDF-1.4 test');

        $result = DocumentAiAnalysisService::analyzeFile($file, 'invoice');
        @unlink($file);

        $this->assertTrue($result['success']);
        $this->assertSame('INV-2026-000022', $result['invoice_no']);
        $this->assertSame(17000000.0, $result['total_amount']);
    }

    public function test_reading_an_invoice_fills_only_the_invoice_number()
    {
        $this->fakeAiReply(self::INVOICE_REPLY);
        $document = $this->upload('invoice', ['async_ai' => '1']);

        $this->postJson("/api/programs/1/documents/{$document->id}/analyze")
            ->assertOk()
            ->assertJsonPath('message', 'No. Invoice terisi otomatis: INV-2026-000022');

        $this->assertProgramNumbers('INV-2026-000022', null);
    }

    public function test_uploading_an_invoice_without_async_ai_fills_only_the_invoice_number()
    {
        $this->fakeAiReply(self::INVOICE_REPLY);

        $this->upload('invoice');

        $this->assertProgramNumbers('INV-2026-000022', null);
    }

    public function test_reading_a_faktur_fills_only_the_faktur_number()
    {
        $this->fakeAiReply(self::FAKTUR_REPLY);
        $document = $this->upload('faktur_pajak', ['async_ai' => '1']);

        $this->postJson("/api/programs/1/documents/{$document->id}/analyze")
            ->assertOk()
            ->assertJsonPath('message', 'No. Faktur terisi otomatis: 04002600333766027');

        $this->assertProgramNumbers('2602000033', '04002600333766027');
    }

    public function test_uploading_a_faktur_without_async_ai_fills_only_the_faktur_number()
    {
        $this->fakeAiReply(self::FAKTUR_REPLY);

        $this->upload('faktur_pajak');

        $this->assertProgramNumbers('2602000033', '04002600333766027');
    }

    public function test_memo_documents_are_not_read_by_ai()
    {
        $this->fakeAiReply(self::INVOICE_REPLY);
        $document = $this->upload('mou');

        $this->postJson("/api/programs/1/documents/{$document->id}/analyze")->assertStatus(422);

        Http::assertNothingSent();
        $this->assertProgramNumbers('2602000033', null);
    }

    public function test_invoice_without_a_number_asks_for_manual_input()
    {
        $this->fakeAiReply('{"invoice_no": null, "dpp_amount": 15315315}');
        $document = $this->upload('invoice', ['async_ai' => '1']);

        $this->postJson("/api/programs/1/documents/{$document->id}/analyze")
            ->assertStatus(422)
            ->assertJsonPath('message', 'AI tidak menemukan No. Invoice di dokumen ini. Isi secara manual.');

        $this->assertProgramNumbers('2602000033', null);
    }
}
