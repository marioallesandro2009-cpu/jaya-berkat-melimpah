<?php

namespace App\Support;

use Illuminate\Support\Facades\RateLimiter;

/**
 * Failed-login limits for the admin panel, on top of Filament's own 5-per-minute-per-IP limit.
 * Three counters, all fed only by FAILED attempts:
 *
 * - this email from this IP: 5 failures / 15 minutes (online guessing of one account),
 * - this email from anywhere: 10 failures / 30 minutes (a spread-out attack on one account),
 * - this IP against any account: 30 failures / 15 minutes (password spraying).
 *
 * Reaching any limit locks the login for that key for the rest of its window. The message the
 * visitor sees never says which limit was hit, or whether the email exists.
 */
final class LoginThrottle
{
    private readonly string $email;

    public function __construct(private readonly string $ip, string $email)
    {
        $this->email = mb_strtolower(trim($email));
    }

    /**
     * @return array<string, array{0: string, 1: int, 2: int}> name => [key, max attempts, window in seconds]
     */
    private function limits(): array
    {
        $hash = sha1($this->email);

        return [
            'account' => ["login:account:{$hash}|{$this->ip}", 5, 900],
            'email' => ["login:email:{$hash}", 10, 1800],
            'ip' => ["login:ip:{$this->ip}", 30, 900],
        ];
    }

    /**
     * Seconds until the login may be tried again; 0 when it is open.
     */
    public function secondsUntilAvailable(): int
    {
        $wait = 0;

        foreach ($this->limits() as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                $wait = max($wait, RateLimiter::availableIn($key));
            }
        }

        return $wait;
    }

    public function fail(): void
    {
        foreach ($this->limits() as [$key, , $window]) {
            RateLimiter::hit($key, $window);
        }
    }

    /**
     * A successful login clears this account-from-this-IP counter (the others keep counting).
     */
    public function clear(): void
    {
        RateLimiter::clear($this->limits()['account'][0]);
    }
}
