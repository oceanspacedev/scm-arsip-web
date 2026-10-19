<?php

namespace Tests\Feature;

use App\Models\Otp;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Jalan pintas akun demo (kode OTP master, OTP di respons, hapus semua data) tidak boleh ada lagi
 */
class LoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(); // gateway WhatsApp

        User::create([
            'name' => 'Budi Santoso',
            'email' => 'admin@scm.corp',
            'phone' => '081234567890',
            'password' => Hash::make('password123'),
            'role' => 'Admin SCM',
            'status' => 'approved',
        ]);
    }

    public function test_master_otp_code_is_rejected()
    {
        $this->postJson('/api/auth/verify-otp', ['otp' => '123456', 'identifier' => 'someone@example.com'])
            ->assertStatus(422);

        $this->assertGuest();
    }

    public function test_scm_corp_account_needs_the_real_otp()
    {
        $this->postJson('/api/auth/send-otp', ['identifier' => 'admin@scm.corp'])->assertOk();

        $this->postJson('/api/auth/verify-otp', ['otp' => '000000', 'identifier' => 'admin@scm.corp'])
            ->assertStatus(422);

        $this->assertGuest();
    }

    public function test_otp_is_not_returned_in_the_response_and_works_only_once()
    {
        $this->postJson('/api/auth/send-otp', ['identifier' => 'admin@scm.corp'])
            ->assertOk()
            ->assertJsonPath('otp', null);

        $code = Otp::latest('id')->value('otp_code');

        $this->postJson('/api/auth/verify-otp', ['otp' => $code, 'identifier' => 'admin@scm.corp'])
            ->assertOk()
            ->assertJsonPath('user.email', 'admin@scm.corp');
        $this->postJson('/api/auth/verify-otp', ['otp' => $code, 'identifier' => 'admin@scm.corp'])
            ->assertStatus(422);
    }

    public function test_reset_all_data_endpoint_no_longer_exists()
    {
        Program::create(['id' => '1', 'title' => 'Program Test', 'supplier' => 'PT Vendor Test']);

        $this->postJson('/api/admin/reset-data')->assertNotFound();

        $this->assertDatabaseHas('programs', ['id' => '1']);
    }
}
