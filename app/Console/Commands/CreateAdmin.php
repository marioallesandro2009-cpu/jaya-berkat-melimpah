<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Creates the first administrator (or resets one's password) on the server, replacing a
 * password in .env / the seeder. The password is asked for with hidden input, never taken from
 * an argument (it would end up in the shell history), and must follow the strong-password rule
 * of AppServiceProvider (12+ characters, upper and lower case, numbers, symbols; also checked
 * against known leaked passwords in production).
 */
class CreateAdmin extends Command
{
    protected $signature = 'admin:create
                            {--name= : Display name (asked when omitted)}
                            {--email= : Login email (asked when omitted)}
                            {--reset-password : Set a new password for an account that already exists}';

    protected $description = 'Create an administrator for the admin panel (interactive, hidden password)';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) ($this->option('email') ?: $this->ask('Email'))));

        if (Validator::make(['email' => $email], ['email' => ['required', 'email:rfc', 'max:190']])->fails()) {
            $this->error('Alamat email tidak valid.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user !== null && ! $this->option('reset-password')) {
            $this->error("Akun {$email} sudah ada. Pakai --reset-password untuk mengganti passwordnya.");

            return self::FAILURE;
        }

        if ($user === null && $this->option('reset-password')) {
            $this->error("Akun {$email} tidak ditemukan.");

            return self::FAILURE;
        }

        $name = $user !== null ? $user->name : trim((string) ($this->option('name') ?: $this->ask('Nama', 'Administrator')));

        if (! $this->input->isInteractive()) {
            $this->error('Password diminta lewat input tersembunyi; jalankan perintah ini secara interaktif.');

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password (min. 12 karakter: huruf besar & kecil, angka, simbol)');

        if ($password !== (string) $this->secret('Ulangi password')) {
            $this->error('Kedua password tidak sama.');

            return self::FAILURE;
        }

        $validator = Validator::make(['password' => $password], ['password' => ['required', 'string', Password::default()]]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user ??= new User;
        $user->forceFill([
            'name' => $name !== '' ? $name : 'Administrator',
            'email' => $email,
            'password' => $password,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'is_admin' => true,
        ])->save();

        $this->info($user->wasRecentlyCreated ? "Administrator {$email} dibuat." : "Password {$email} diganti.");

        return self::SUCCESS;
    }
}
