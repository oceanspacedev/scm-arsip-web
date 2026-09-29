<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Program;
use App\Services\DocumentAiAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PaymentStatusAndAiTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_has_payment_status()
    {
        $program = Program::create([
            'id' => 'PRG-TEST-001',
            'title' => 'Pengadaan Test SCM',
            'supplier' => 'PT Vendor Testing',
            'dpp_amount' => 10000000,
            'ppn_amount' => 1100000,
            'total_amount' => 11100000,
            'payment_status' => 'WAITING PAYMENT',
        ]);

        $this->assertEquals('WAITING PAYMENT', $program->payment_status);

        // Update to cbd
        $program->update(['payment_status' => 'cbd']);
        $this->assertEquals('cbd', $program->fresh()->payment_status);

        // Update to tempo
        $program->update(['payment_status' => 'tempo']);
        $this->assertEquals('tempo', $program->fresh()->payment_status);

        // Update to PAID
        $program->update(['payment_status' => 'PAID']);
        $this->assertEquals('PAID', $program->fresh()->payment_status);

        $program->delete();
    }

    public function test_api_program_update_payment_status()
    {
        $program = Program::create([
            'id' => 'PRG-TEST-002',
            'title' => 'Pengadaan Test API',
            'supplier' => 'PT Vendor API',
            'payment_status' => 'WAITING PAYMENT',
        ]);

        $response = $this->putJson("/api/programs/{$program->id}", [
            'payment_status' => 'cbd',
        ], [
            'X-User-Role' => 'Admin SCM'
        ]);

        $response->assertStatus(200);
        $this->assertEquals('cbd', $program->fresh()->payment_status);

        $program->delete();
    }

    public function test_ai_analysis_service_extracts_data()
    {
        // Test with mock-like sample PDF containing invoice and faktur text
        $tmp = tempnam(sys_get_temp_dir(), 'test_doc_') . '.pdf';
        $pdfContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>/Contents 4 0 R>>endobj\n4 0 obj<</Length 55>>stream\nBT /F1 12 Tf (NO INVOICE: INV/2026/099 FAKTUR: 010.002-26.12345678) Tj ET\nendstream\nendobj\nxref\n0 5\n0000000000 65535 f \n0000000009 00000 n \n0000000052 00000 n \n0000000101 00000 n \n0000000201 00000 n \ntrailer<</Size 5/Root 1 0 R>>\nstartxref\n305\n%%EOF";
        file_put_contents($tmp, $pdfContent);

        $result = DocumentAiAnalysisService::analyzeFile($tmp, 'invoice');
        @unlink($tmp);

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['invoice_no']);
        $this->assertStringContainsString('INV', $result['invoice_no']);
    }
}
