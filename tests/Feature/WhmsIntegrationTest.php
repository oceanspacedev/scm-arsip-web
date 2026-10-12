<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\ProgramDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WhmsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'whms-test-token';

    private const DOC_UUID = '6f1c2b9e-3d4a-4c8b-9e2f-1a2b3c4d5e6f';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whms.token' => self::TOKEN]);
        Storage::fake('arsip_documents');
    }

    public function test_requests_without_a_valid_token_are_rejected()
    {
        $this->getJson('/api/integrations/whms/programs?kode_gudang=GDG-BDG-01')->assertStatus(401);

        $this->withToken('salah')
            ->getJson('/api/integrations/whms/programs?kode_gudang=GDG-BDG-01')
            ->assertStatus(401);
    }

    public function test_lists_only_programs_of_the_requested_warehouse()
    {
        $withMemo = $this->makeProgram('PRG-1', 'GDG-BDG-01', 'PO-001');
        $this->makeProgram('PRG-2', 'GDG-BDG-01', 'PO-002');
        $this->makeProgram('PRG-3', 'GDG-SBY-02', 'PO-003');
        $this->makeDocument($withMemo, 'memo');

        $response = $this->withToken(self::TOKEN)
            ->getJson('/api/integrations/whms/programs?kode_gudang='.urlencode(' gdg-bdg-01 '))
            ->assertOk();

        // Program yang belum punya Memo/DO tampil lebih dulu
        $this->assertSame(['PRG-2', 'PRG-1'], array_column($response->json('programs'), 'id'));
        $this->assertSame([false, true], array_map('boolval', array_column($response->json('programs'), 'has_memo')));
    }

    public function test_lists_programs_of_all_warehouses_when_no_code_is_given()
    {
        $this->makeProgram('PRG-1', 'GDG-BDG-01', 'PO-001');
        $this->makeProgram('PRG-2', 'GDG-SBY-02', 'PO-002');
        Program::create(['id' => 'PRG-3', 'title' => 'Tanpa gudang', 'supplier' => 'PT Vendor Test', 'category' => 'Lainnya']);

        $response = $this->withToken(self::TOKEN)
            ->getJson('/api/integrations/whms/programs')
            ->assertOk();

        $ids = array_column($response->json('programs'), 'id');
        sort($ids);
        $this->assertSame(['PRG-1', 'PRG-2'], $ids);
    }

    public function test_stores_memo_for_a_program_of_the_same_warehouse()
    {
        $program = $this->makeProgram('PRG-1', 'GDG-BDG-01', 'PO-001');
        $this->makeDocument($program, 'invoice');
        $this->makeDocument($program, 'faktur');

        $this->sendMemo('PRG-1', 'GDG-BDG-01', self::DOC_UUID)
            ->assertCreated()
            ->assertJsonPath('document_id', 'whms-'.self::DOC_UUID);

        $document = ProgramDocument::findOrFail('whms-'.self::DOC_UUID);
        $this->assertSame('memo', $document->type);
        $this->assertSame('memo-po-001.pdf', $document->file_name);
        $this->assertStringEndsWith('.pdf', $document->file_path);
        Storage::disk('arsip_documents')->assertExists(basename($document->file_path));
        $this->assertSame('Lengkap', $program->fresh()->status);
    }

    public function test_rejects_a_program_that_belongs_to_another_warehouse()
    {
        $this->makeProgram('PRG-1', 'GDG-SBY-02', 'PO-001');

        $this->sendMemo('PRG-1', 'GDG-BDG-01', self::DOC_UUID)->assertNotFound();

        $this->assertSame(0, ProgramDocument::count());
    }

    public function test_resending_the_same_whms_document_does_not_duplicate_it()
    {
        $this->makeProgram('PRG-1', 'GDG-BDG-01', 'PO-001');

        $this->sendMemo('PRG-1', 'GDG-BDG-01', self::DOC_UUID)->assertCreated();
        $this->sendMemo('PRG-1', 'GDG-BDG-01', self::DOC_UUID)->assertOk()->assertJsonPath('document_id', 'whms-'.self::DOC_UUID);

        $this->assertSame(1, ProgramDocument::count());
    }

    public function test_rejects_files_that_are_not_pdf_or_images()
    {
        $this->makeProgram('PRG-1', 'GDG-BDG-01', 'PO-001');

        $this->sendMemo('PRG-1', 'GDG-BDG-01', self::DOC_UUID, UploadedFile::fake()->create('shell.php', 1, 'application/x-php'))
            ->assertStatus(422);

        $this->assertSame(0, ProgramDocument::count());
    }

    private function sendMemo(string $programId, string $kodeGudang, string $whmsDocumentId, ?UploadedFile $file = null)
    {
        return $this->withToken(self::TOKEN)->post('/api/integrations/whms/documents', [
            'program_id' => $programId,
            'kode_gudang' => $kodeGudang,
            'whms_document_id' => $whmsDocumentId,
            'uploaded_by' => 'Petugas Gudang',
            'file' => $file ?? UploadedFile::fake()->create('memo-po-001.pdf', 120, 'application/pdf'),
        ], ['Accept' => 'application/json']);
    }

    private function makeProgram(string $id, string $kodeGudang, string $po): Program
    {
        return Program::create([
            'id' => $id,
            'title' => "Program {$id}",
            'supplier' => 'PT Vendor Test',
            'kode_gudang' => $kodeGudang,
            'po_sj_number' => $po,
            'category' => 'Lainnya',
        ]);
    }

    private function makeDocument(Program $program, string $type): ProgramDocument
    {
        return ProgramDocument::create([
            'id' => "doc-{$program->id}-{$type}",
            'program_id' => $program->id,
            'type' => $type,
            'file_name' => "{$type}.pdf",
            'file_path' => "uploads/documents/{$type}.pdf",
            'uploaded_at' => now(),
        ]);
    }
}
