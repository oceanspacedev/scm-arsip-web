<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateEmptyFieldsTest extends TestCase
{
    use RefreshDatabase;
    public function test_can_clear_invoice_and_faktur_fields()
    {
        $program = Program::create([
            'id' => '9999',
            'title' => 'Test Program',
            'supplier' => 'PT Test Supplier',
            'due_date' => '2026-03-01',
            'invoice_no' => 'INV/TEST/001',
            'faktur_number' => '010.000-26.00000001',
            'faktur_date' => '2026-03-01'
        ]);

        $res = $this->putJson("/api/programs/{$program->id}", [
            'invoice_number' => '',
            'faktur_number' => '',
            'faktur_date' => ''
        ], [
            'X-User-Role' => 'Admin SCM'
        ]);

        $res->assertStatus(200);
        $program->refresh();

        $this->assertNull($program->invoice_no, "invoice_no should be null, but got: " . var_export($program->invoice_no, true));
        $this->assertNull($program->faktur_number, "faktur_number should be null, but got: " . var_export($program->faktur_number, true));
        $this->assertNull($program->faktur_date, "faktur_date should be null, but got: " . var_export($program->faktur_date, true));
    }
}
