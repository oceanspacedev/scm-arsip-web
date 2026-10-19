<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    private function runCommand(string $email, string $password, string $confirmation)
    {
        return $this->artisan('arsip:create-admin')
            ->expectsQuestion('Nama', 'Reza Admin')
            ->expectsQuestion('Email', $email)
            ->expectsQuestion('No. WhatsApp untuk login OTP (kosongkan bila tidak ada)', '081200000001')
            ->expectsQuestion('Kata sandi (minimal 8 karakter)', $password)
            ->expectsQuestion('Ulangi kata sandi', $confirmation);
    }

    public function test_creates_an_approved_admin_who_can_log_in()
    {
        $this->runCommand('Admin.Baru@Example.com', 'rahasia-kuat-1', 'rahasia-kuat-1')->assertSuccessful();

        $user = User::where('email', 'admin.baru@example.com')->firstOrFail();
        $this->assertSame('Admin SCM', $user->role);
        $this->assertSame('approved', $user->status);
        $this->assertSame('RA', $user->initials);
        $this->assertTrue(Hash::check('rahasia-kuat-1', $user->password));

        $this->postJson('/api/auth/validate-password', ['email' => 'admin.baru@example.com', 'password' => 'rahasia-kuat-1'])
            ->assertOk()
            ->assertJsonPath('user.role', 'Admin SCM');
    }

    public function test_rejects_a_short_or_mismatched_password()
    {
        $this->runCommand('admin@example.com', 'pendek', 'pendek')->assertFailed();
        $this->runCommand('admin@example.com', 'rahasia-kuat-1', 'rahasia-kuat-2')->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
    }

    public function test_existing_account_is_only_changed_after_confirmation()
    {
        User::create(['name' => 'Lama', 'email' => 'lama@example.com', 'password' => 'kata-sandi-lama', 'role' => 'Staff Gudang', 'status' => 'approved']);

        $this->runCommand('lama@example.com', 'rahasia-kuat-1', 'rahasia-kuat-1')
            ->expectsConfirmation('Email lama@example.com sudah terdaftar. Jadikan Admin SCM dan ganti kata sandinya?', 'no')
            ->assertSuccessful();
        $this->assertSame('Staff Gudang', User::where('email', 'lama@example.com')->value('role'));

        $this->runCommand('lama@example.com', 'rahasia-kuat-1', 'rahasia-kuat-1')
            ->expectsConfirmation('Email lama@example.com sudah terdaftar. Jadikan Admin SCM dan ganti kata sandinya?', 'yes')
            ->assertSuccessful();
        $user = User::where('email', 'lama@example.com')->first();
        $this->assertSame('Admin SCM', $user->role);
        $this->assertTrue(Hash::check('rahasia-kuat-1', $user->password));
    }
}
