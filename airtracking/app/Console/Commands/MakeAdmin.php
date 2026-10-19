<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'airtracking:make-admin {email} {--name=Admin}';
    protected $description = 'Buat akun admin AirTracking, atau jadikan akun yang sudah ada sebagai admin';

    public function handle(): int
    {
        $email = strtolower($this->argument('email'));
        $user = User::where('email', $email)->first();

        if ($user) {
            $user->update(['role' => 'admin']);
            $this->info("Akun {$email} sekarang admin.");
            return self::SUCCESS;
        }

        $pw = $this->secret('Kata sandi (minimal 8 karakter)');
        if (!is_string($pw) || strlen($pw) < 8) {
            $this->error('Kata sandi minimal 8 karakter.');
            return self::FAILURE;
        }

        User::create(['name' => $this->option('name'), 'email' => $email, 'password' => $pw, 'role' => 'admin']);
        $this->info("Admin {$email} dibuat.");
        return self::SUCCESS;
    }
}
