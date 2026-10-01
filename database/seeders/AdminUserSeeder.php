<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Convenience for local development only: creates the administrator named in .env
 * (ADMIN_EMAIL / ADMIN_PASSWORD) when that password is strong. It never runs in production
 * (use `php artisan admin:create`) and never changes an account that already exists, so
 * seeding again cannot reset a password.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->warn('Production: admin tidak dibuat oleh seeder. Jalankan: php artisan admin:create');

            return;
        }

        $email = config('site.admin.email');
        $password = config('site.admin.password');

        if (! $email || ! $password) {
            $this->command->warn('ADMIN_EMAIL / ADMIN_PASSWORD belum diisi di .env, admin tidak dibuat.');

            return;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->command->info("Akun {$email} sudah ada, tidak diubah.");

            return;
        }

        $validator = Validator::make(['password' => $password], ['password' => ['string', Password::default()]]);

        if ($validator->fails()) {
            $this->command->error('ADMIN_PASSWORD terlalu lemah, admin tidak dibuat: '.implode(' ', $validator->errors()->all()));

            return;
        }

        (new User)->forceFill([
            'name' => config('site.admin.name'),
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
            'is_admin' => true,
        ])->save();
    }
}
