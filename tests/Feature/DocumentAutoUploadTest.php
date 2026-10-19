<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\ProgramDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DocumentAutoUploadTest extends TestCase
{
    use RefreshDatabase;

    /** Invoice contoh: CV TOP SELULAR → PT. ARIS KEEP GOING, No Surat Jalan 2609000804 */
    private const INVOICE = [
        'document_type' => 'invoice',
        'invoice_no' => '791/TOP/IX/26',
        'sj_number' => '2609000804',
        'program_name' => 'PROGRAM DEALER AUG 2026 C100I',
        'description' => 'MARKETING SERVICE FEE',
        'bill_to' => 'PT. ARIS KEEP GOING',
        'supplier_name' => 'CV TOP SELULAR',
        'dpp_amount' => 25443694,
        'ppn_amount' => 3053243,
    ];

    /** Faktur pajak contoh untuk invoice di atas (tanpa No. Surat Jalan) */
    private const FAKTUR = [
        'document_type' => 'faktur_pajak',
        'faktur_number' => '04002600372891514',
        'program_name' => 'PROGRAM DEALER AUG 2026 C100I',
        'description' => 'MARKETING SERVICE FEE',
        'bill_to' => 'ARIS KEEP GOING',
        'supplier_name' => 'TOP SELULAR',
        'dpp_amount' => 25443694,
        'ppn_amount' => 3053243,
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

        $this->program('P1', '2609000804');
        $this->program('P2', '2609000999', ['dpp_amount' => 2313743, 'ppn_amount' => 277649]);
    }

    protected function tearDown(): void
    {
        foreach (ProgramDocument::pluck('file_path') as $path) {
            @unlink(public_path($path));
        }
        parent::tearDown();
    }

    /** Seperti data hasil impor: company berisi penjual, supplier berisi pembeli */
    private function program(string $id, string $sj, array $overrides = []): Program
    {
        return Program::create(array_merge([
            'id' => $id,
            'title' => 'Program Dealer Aug 2026 C100i',
            'company_name' => 'CV. TOP SELULAR',
            'supplier' => 'PT. ARIS KEEP GOING',
            'po_sj_number' => $sj,
            'category' => 'Marketing Service Fee',
            'dpp_amount' => 25443694,
            'ppn_amount' => 3053243,
        ], $overrides));
    }

    private function fakeAi(array $reply): void
    {
        Http::fake(['ai.test/*' => Http::response(['choices' => [['message' => ['content' => json_encode($reply)]]]])]);
    }

    private function upload(array $extra = [], array $headers = [], string $name = 'dokumen.pdf')
    {
        return $this->post('/api/documents/auto-upload', [
            'file' => UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF"),
            ...$extra,
        ], ['Accept' => 'application/json', 'X-User-Role' => 'Admin SCM', ...$headers]);
    }

    public function test_invoice_goes_to_the_program_with_the_same_sj_number()
    {
        $this->fakeAi(self::INVOICE);

        $this->upload()
            ->assertOk()
            ->assertJsonPath('status', 'matched')
            ->assertJsonPath('document_type', 'invoice')
            ->assertJsonPath('reason', 'No. SJ 2609000804')
            ->assertJsonPath('program.id', 'P1');

        $program = Program::find('P1');
        $this->assertSame('791/TOP/IX/26', $program->invoice_no);
        $this->assertSame(1, $program->documents()->where('type', 'invoice')->count());
        $this->assertSame(0, Program::find('P2')->documents()->count());
    }

    public function test_same_sj_on_several_programs_is_resolved_by_category_and_name()
    {
        $this->program('P3', '2609000804', ['category' => 'Price Protection', 'title' => 'Program Price Protection Aug 2026']);
        $this->fakeAi(self::INVOICE);

        $this->upload()->assertOk()->assertJsonPath('status', 'matched')->assertJsonPath('program.id', 'P1');
    }

    public function test_programs_that_cannot_be_told_apart_are_left_for_the_user()
    {
        $this->program('P4', '2609000804');
        $this->fakeAi(self::INVOICE);

        $this->upload()
            ->assertOk()
            ->assertJsonPath('status', 'review')
            ->assertJsonCount(2, 'candidates');

        $this->assertSame(0, ProgramDocument::count(), 'Berkas tidak boleh disimpan sebelum dipilih');
    }

    public function test_unknown_sj_suggests_programs_by_name()
    {
        $this->fakeAi(['sj_number' => '1111111111', 'invoice_no' => '1/X/26'] + self::INVOICE);

        $response = $this->upload()->assertOk()->assertJsonPath('status', 'review');

        $this->assertContains('P1', array_column($response->json('candidates'), 'id'));
        $this->assertSame(0, ProgramDocument::count());
    }

    public function test_invoice_number_from_the_import_is_also_a_match()
    {
        Program::find('P2')->update(['invoice_no' => '791/TOP/IX/26']);
        $this->fakeAi(['sj_number' => null] + self::INVOICE);

        $this->upload()
            ->assertOk()
            ->assertJsonPath('status', 'matched')
            ->assertJsonPath('program.id', 'P2')
            ->assertJsonPath('reason', 'No. Invoice 791/TOP/IX/26');
    }

    public function test_faktur_goes_to_the_program_with_the_same_faktur_number()
    {
        Program::find('P2')->update(['faktur_number' => '04002600372891514']);
        $this->fakeAi(self::FAKTUR);

        $this->upload()
            ->assertOk()
            ->assertJsonPath('status', 'matched')
            ->assertJsonPath('document_type', 'faktur_pajak')
            ->assertJsonPath('reason', 'No. Faktur 04002600372891514')
            ->assertJsonPath('program.id', 'P2');

        $this->assertSame(1, Program::find('P2')->documents()->where('type', 'faktur')->count());
    }

    public function test_faktur_without_a_known_number_is_matched_by_dpp_and_ppn()
    {
        $this->fakeAi(self::FAKTUR);

        $this->upload()
            ->assertOk()
            ->assertJsonPath('status', 'matched')
            ->assertJsonPath('reason', 'DPP Rp 25.443.694')
            ->assertJsonPath('program.id', 'P1');

        $program = Program::find('P1');
        $this->assertSame('04002600372891514', $program->faktur_number);
        $this->assertEquals(25443694, $program->dpp_amount, 'Nominal tidak boleh diubah');
    }

    public function test_same_dpp_for_an_unrelated_party_is_not_enough()
    {
        $this->fakeAi(['bill_to' => 'PT LAIN SEKALI', 'supplier_name' => 'CV ENTAH', 'program_name' => 'PROGRAM XYZ'] + self::FAKTUR);

        $this->upload()->assertOk()->assertJsonPath('status', 'review');

        $this->assertSame(0, ProgramDocument::count());
    }

    public function test_other_documents_are_not_stored()
    {
        $this->fakeAi(['document_type' => 'other', 'invoice_no' => null, 'sj_number' => null]);

        $this->upload()->assertOk()->assertJsonPath('status', 'failed');

        $this->assertSame(0, ProgramDocument::count());
    }

    public function test_program_chosen_by_the_user_is_used_without_reading_again()
    {
        $this->upload(['program_id' => 'P2', 'document_type' => 'faktur_pajak', 'number' => '04002600372891515'])
            ->assertOk()
            ->assertJsonPath('status', 'matched')
            ->assertJsonPath('document_type', 'faktur_pajak')
            ->assertJsonPath('reason', 'Dipilih manual');

        Http::assertNothingSent();
        $program = Program::find('P2');
        $this->assertSame('04002600372891515', $program->faktur_number);
        $this->assertSame(1, $program->documents()->where('type', 'faktur')->count());
    }

    public function test_same_file_twice_is_reported_as_duplicate()
    {
        $this->fakeAi(self::INVOICE);

        $this->upload()->assertJsonPath('status', 'matched');
        $this->upload()->assertOk()->assertJsonPath('status', 'duplicate');

        $this->assertSame(1, ProgramDocument::count());
    }

    public function test_warehouse_staff_cannot_upload_invoices_or_fakturs()
    {
        $this->upload([], ['X-User-Role' => 'Staff Gudang'])->assertForbidden();

        Http::assertNothingSent();
    }
}
