<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProgramDuplicateValidationTest extends TestCase
{
    use RefreshDatabase;

    private function makeProgram(string $id, ?string $company, ?string $po, string $category, array $extra = []): Program
    {
        return Program::create(array_merge([
            'id' => $id,
            'title' => "Program {$id}",
            'supplier' => 'PT Vendor Test',
            'company_name' => $company,
            'po_sj_number' => $po,
            'category' => $category,
        ], $extra));
    }

    private function storePayload(?string $company, ?string $po, string $category): array
    {
        return [
            'program_name' => 'Program Baru',
            'supplier' => 'PT Vendor Test',
            'company_name' => $company,
            'po_sj_number' => $po,
            'category' => $category,
            'dpp' => 1000000,
        ];
    }

    public function test_store_rejects_same_company_po_and_category()
    {
        $this->makeProgram('1', 'PT Alpha', 'PO-001', 'Bonus');

        $res = $this->postJson('/api/programs', $this->storePayload('pt alpha', ' po-001 ', 'BONUS'), [
            'X-User-Role' => 'Admin SCM'
        ]);

        $res->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('duplicate.id', '1');
        $this->assertStringContainsString('sudah terdaftar', $res->json('message'));
        $this->assertEquals(1, Program::count());
    }

    public function test_store_allows_same_po_with_different_company_or_category()
    {
        $this->makeProgram('1', 'PT Alpha', 'PO-001', 'Bonus');

        $this->postJson('/api/programs', $this->storePayload('PT Beta', 'PO-001', 'Bonus'))->assertStatus(201);
        $this->postJson('/api/programs', $this->storePayload('PT Alpha', 'PO-001', 'Rebate'))->assertStatus(201);

        $this->assertEquals(3, Program::count());
    }

    public function test_store_without_po_is_not_checked_for_duplicates()
    {
        $this->postJson('/api/programs', $this->storePayload('PT Alpha', '', 'Bonus'))->assertStatus(201);
        $this->postJson('/api/programs', $this->storePayload('PT Alpha', '', 'Bonus'))->assertStatus(201);

        $this->assertEquals(2, Program::count());
    }

    public function test_update_rejects_changing_into_existing_combination()
    {
        $this->makeProgram('1', 'PT Alpha', 'PO-001', 'Bonus');
        $other = $this->makeProgram('2', 'PT Alpha', 'PO-002', 'Bonus');

        $res = $this->putJson('/api/programs/2', ['po_sj_number' => 'PO-001'], [
            'X-User-Role' => 'Admin SCM'
        ]);

        $res->assertStatus(409)->assertJsonPath('duplicate.id', '1');
        $this->assertEquals('PO-002', $other->fresh()->po_sj_number);
    }

    public function test_update_without_changing_combination_is_allowed_even_for_existing_duplicates()
    {
        // Data lama yang sudah terlanjur dobel sebelum validasi ada
        $this->makeProgram('1', 'PT Alpha', 'PO-001', 'Bonus');
        $legacy = $this->makeProgram('2', 'PT Alpha', 'PO-001', 'Bonus');

        $res = $this->putJson('/api/programs/2', [
            'company_name' => 'PT Alpha',
            'po_sj_number' => 'PO-001',
            'category' => 'Bonus',
            'invoice_number' => 'INV/2026/777',
        ], [
            'X-User-Role' => 'Admin SCM'
        ]);

        $res->assertStatus(200);
        $this->assertEquals('INV/2026/777', $legacy->fresh()->invoice_no);
    }

    public function test_import_skips_duplicate_rows_and_imports_the_rest()
    {
        $this->makeProgram('1', 'PT Alpha', 'PO-001', 'Bonus');

        $rows = [
            ['program_name' => 'Sudah ada', 'company_name' => ' pt alpha ', 'po_sj_number' => 'po-001', 'category' => 'Bonus'],
            ['program_name' => 'Baru', 'company_name' => 'PT Alpha', 'po_sj_number' => 'PO-002', 'category' => 'Bonus'],
            ['program_name' => 'PO sama beda company', 'company_name' => 'PT Beta', 'po_sj_number' => 'PO-001', 'category' => 'Bonus'],
            ['program_name' => 'Ganda di file', 'company_name' => 'PT Alpha', 'po_sj_number' => 'PO-002 ', 'category' => 'bonus'],
            ['program_name' => 'Tanpa PO 1', 'company_name' => 'PT Alpha', 'po_sj_number' => '', 'category' => 'Bonus'],
            ['program_name' => 'Tanpa PO 2', 'company_name' => 'PT Alpha', 'po_sj_number' => '', 'category' => 'Bonus'],
        ];

        // Frontend mengirim baris sebagai string JSON di FormData bersama file mentahan
        $res = $this->post('/api/programs/import', ['programs' => json_encode($rows)], [
            'Accept' => 'application/json',
            'X-User-Role' => 'Admin SCM'
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('imported_count', 4)
            ->assertJsonPath('skipped_count', 2)
            ->assertJsonPath('duplicates.0.reason', 'registered')
            ->assertJsonPath('duplicates.0.existing_id', '1')
            ->assertJsonPath('duplicates.0.row', 1)
            ->assertJsonPath('duplicates.1.reason', 'in_file')
            ->assertJsonPath('duplicates.1.row', 4);
        $this->assertStringContainsString('2 data ditolak', $res->json('message'));

        $this->assertEquals(5, Program::count());
        $this->assertEquals(1, Program::where('po_sj_number', 'PO-002')->count());
        $this->assertEquals(2, Program::whereNull('po_sj_number')->count());
    }

    public function test_import_is_rejected_when_all_rows_are_duplicates()
    {
        $this->makeProgram('1', 'PT Alpha', 'PO-001', 'Bonus');

        $res = $this->postJson('/api/programs/import', ['programs' => [
            ['program_name' => 'Sudah ada', 'company_name' => 'PT Alpha', 'po_sj_number' => 'PO-001', 'category' => 'Bonus'],
        ]], [
            'X-User-Role' => 'Admin SCM'
        ]);

        $res->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('imported_count', 0);
        $this->assertStringContainsString('Upload ditolak', $res->json('message'));
        $this->assertEquals(1, Program::count());
    }

    public function test_import_row_updating_its_own_record_is_not_a_duplicate()
    {
        $this->makeProgram('7', 'PT Alpha', 'PO-001', 'Bonus');

        $res = $this->postJson('/api/programs/import', ['programs' => [
            ['id' => '7', 'program_name' => 'Judul Diperbarui', 'company_name' => 'PT Alpha', 'po_sj_number' => 'PO-001', 'category' => 'Bonus'],
        ]], [
            'X-User-Role' => 'Admin SCM'
        ]);

        $res->assertStatus(200)->assertJsonPath('imported_count', 1);
        $this->assertEquals('Judul Diperbarui', Program::find('7')->title);
        $this->assertEquals(1, Program::count());
    }
}
