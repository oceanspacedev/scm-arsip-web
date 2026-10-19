<?php

namespace Tests\Feature;

use App\Models\DocumentUploadLog;
use App\Models\Program;
use App\Models\ProgramDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Menu Riwayat: tiap dokumen yang masuk tercatat beserta hasilnya
 */
class DocumentUploadLogTest extends TestCase
{
    use RefreshDatabase;

    private const INVOICE = [
        'document_type' => 'invoice',
        'invoice_no' => '791/TOP/IX/26',
        'sj_number' => '2609000804',
        'program_name' => 'PROGRAM DEALER AUG 2026 C100I',
        'description' => 'MARKETING SERVICE FEE',
        'bill_to' => 'PT. ARIS KEEP GOING',
        'supplier_name' => 'CV TOP SELULAR',
    ];

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
            'id' => 'P1',
            'title' => 'Program Dealer Aug 2026 C100i',
            'company_name' => 'CV. TOP SELULAR',
            'supplier' => 'PT. ARIS KEEP GOING',
            'po_sj_number' => '2609000804',
            'category' => 'Marketing Service Fee',
            'kode_gudang' => 'RF',
        ]);
    }

    protected function tearDown(): void
    {
        foreach (ProgramDocument::pluck('file_path') as $path) {
            @unlink(public_path($path));
        }
        parent::tearDown();
    }

    private function fakeAi(array $reply): void
    {
        Http::fake(['ai.test/*' => Http::response(['choices' => [['message' => ['content' => json_encode($reply)]]]])]);
    }

    private function autoUpload(array $extra = [])
    {
        return $this->post('/api/documents/auto-upload', [
            'file' => UploadedFile::fake()->createWithContent('791-TOP-IX-26.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF"),
            'uploaded_by' => 'Reza',
            ...$extra,
        ], ['Accept' => 'application/json', 'X-User-Role' => 'Admin SCM']);
    }

    public function test_matched_upload_is_recorded()
    {
        $this->fakeAi(self::INVOICE);

        $logId = $this->autoUpload()->assertJsonPath('status', 'matched')->json('log_id');

        $log = DocumentUploadLog::findOrFail($logId);
        $this->assertSame('auto', $log->source);
        $this->assertSame('matched', $log->status);
        $this->assertSame('P1', $log->program_id);
        $this->assertSame('No. SJ 2609000804', $log->reason);
        $this->assertSame('791/TOP/IX/26', $log->number);
        $this->assertSame('Reza', $log->uploaded_by);
        $this->assertNotNull($log->document_id);
    }

    public function test_chosen_program_updates_the_same_history_row()
    {
        $this->fakeAi(['sj_number' => '1111111111'] + self::INVOICE);
        $logId = $this->autoUpload()->assertJsonPath('status', 'review')->json('log_id');

        $this->autoUpload(['program_id' => 'P1', 'document_type' => 'invoice', 'number' => '791/TOP/IX/26', 'log_id' => $logId])
            ->assertJsonPath('status', 'matched')
            ->assertJsonPath('log_id', $logId);

        $this->assertSame(1, DocumentUploadLog::count());
        $log = DocumentUploadLog::find($logId);
        $this->assertSame('matched', $log->status);
        $this->assertSame('Dipilih manual', $log->reason);
        $this->assertNull($log->message);
    }

    public function test_skipped_file_is_recorded_as_skipped()
    {
        $this->fakeAi(['sj_number' => '1111111111'] + self::INVOICE);
        $logId = $this->autoUpload()->json('log_id');

        $this->postJson("/api/document-logs/{$logId}/skip")->assertOk();

        $this->assertSame('skipped', DocumentUploadLog::find($logId)->status);
    }

    public function test_manual_upload_from_the_program_page_is_recorded()
    {
        $this->post('/api/programs/P1/documents', [
            'document_type' => 'mou',
            'file' => UploadedFile::fake()->createWithContent('memo.pdf', '%PDF-1.4 test'),
            'uploaded_by' => 'Budi',
        ], ['Accept' => 'application/json', 'X-User-Role' => 'Admin SCM'])->assertOk();

        $log = DocumentUploadLog::firstOrFail();
        $this->assertSame(['manual', 'matched', 'mou', 'P1', 'Budi'], [$log->source, $log->status, $log->document_type, $log->program_id, $log->uploaded_by]);
    }

    public function test_memo_from_whms_is_recorded()
    {
        config(['services.whms.token' => 'whms-test-token']);
        Storage::fake('arsip_documents');

        $this->withToken('whms-test-token')->post('/api/integrations/whms/documents', [
            'program_id' => 'P1',
            'kode_gudang' => 'RF',
            'whms_document_id' => '6f1c2b9e-3d4a-4c8b-9e2f-1a2b3c4d5e6f',
            'uploaded_by' => 'Petugas Gudang',
            'file' => UploadedFile::fake()->create('memo-po.pdf', 120, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertCreated();

        $log = DocumentUploadLog::firstOrFail();
        $this->assertSame(['whms', 'matched', 'Petugas Gudang'], [$log->source, $log->status, $log->uploaded_by]);
    }

    public function test_history_lists_latest_first_and_can_be_filtered()
    {
        DocumentUploadLog::create(['file_name' => 'lama.pdf', 'source' => 'auto', 'status' => 'failed', 'message' => 'Gagal.']);
        DocumentUploadLog::create(['file_name' => 'baru.pdf', 'source' => 'auto', 'status' => 'matched', 'program_id' => 'P1']);

        $this->getJson('/api/document-logs')
            ->assertOk()
            ->assertJsonPath('logs.0.file_name', 'baru.pdf')
            ->assertJsonPath('logs.0.program.title', 'Program Dealer Aug 2026 C100i')
            ->assertJsonPath('meta.total', 2);

        $this->getJson('/api/document-logs?status=failed')->assertJsonCount(1, 'logs')->assertJsonPath('logs.0.file_name', 'lama.pdf');
        $this->getJson('/api/document-logs?q=dealer')->assertJsonCount(1, 'logs')->assertJsonPath('logs.0.file_name', 'baru.pdf');
    }
}
