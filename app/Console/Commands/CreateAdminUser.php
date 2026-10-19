<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateAdminUser extends Command
{
    protected $signature = 'arsip:create-admin';

    protected $description = 'Buat akun Admin SCM untuk login (kata sandi diketik sendiri dan tidak ditampilkan)';

    public function handle(): int
    {
        $name = trim((string) $this->ask('Nama'));
        $email = Str::lower(trim((string) $this->ask('Email')));
        $phone = trim((string) $this->ask('No. WhatsApp untuk login OTP (kosongkan bila tidak ada)', ''));
        $password = (string) $this->secret('Kata sandi (minimal 8 karakter)');
        $passwordConfirmation = (string) $this->secret('Ulangi kata sandi');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password, 'password_confirmation' => $passwordConfirmation],
            ['name' => 'required|string|max:255', 'email' => 'required|email|max:255', 'password' => 'required|string|min:8|confirmed'],
            [
                'name.required' => 'Nama wajib diisi.',
                'email.required' => 'Email wajib diisi.',
                'email.email' => 'Format email tidak valid.',
                'password.min' => 'Kata sandi minimal 8 karakter.',
                'password.confirmed' => 'Ulangan kata sandi tidak sama.',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        $existing = User::where('email', $email)->first();
        if ($existing && !$this->confirm("Email {$email} sudah terdaftar. Jadikan Admin SCM dan ganti kata sandinya?", false)) {
            $this->line('Dibatalkan, tidak ada yang diubah.');
            return self::SUCCESS;
        }

        $user = $existing ?? new User(['email' => $email]);
        $user->fill([
            'name' => $name,
            'phone' => $phone !== '' ? $phone : $user->phone,
            'password' => $password, // di-hash otomatis oleh cast "hashed" pada model User
            'role' => 'Admin SCM',
            'status' => 'approved',
            'division' => $user->division ?: 'Supply Chain Management',
            'initials' => $this->initials($name),
            'approved_at' => $user->approved_at ?? now(),
        ])->save();

        $this->info(($existing ? 'Akun diperbarui' : 'Akun dibuat') . ": {$user->name} <{$user->email}> sebagai Admin SCM.");
        $this->line('Login di halaman Masuk dengan email dan kata sandi tadi.');

        return self::SUCCESS;
    }

    private function initials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name)) ?: [];

        return Str::upper(collect($words)->take(2)->map(fn ($word) => Str::substr($word, 0, 1))->implode('')) ?: 'AD';
    }
}
