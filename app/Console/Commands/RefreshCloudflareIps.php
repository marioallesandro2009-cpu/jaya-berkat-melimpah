<?php

namespace App\Console\Commands;

use App\Support\CloudflareIps;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

class RefreshCloudflareIps extends Command
{
    protected $signature = 'security:cloudflare-ips';

    protected $description = 'Fetch Cloudflare\'s current proxy IP ranges (used by TRUSTED_PROXIES=cloudflare)';

    public function handle(): int
    {
        $ranges = [];

        try {
            foreach (['https://www.cloudflare.com/ips-v4', 'https://www.cloudflare.com/ips-v6'] as $url) {
                $response = Http::timeout(15)->get($url)->throw();
                array_push($ranges, ...array_values(array_filter(array_map('trim', explode("\n", $response->body())))));
            }
        } catch (Throwable $e) {
            $this->error('Could not fetch the list: '.$e->getMessage().' (the bundled list stays in use)');

            return self::FAILURE;
        }

        // Only plain IP/CIDR lines are accepted, so a bad response cannot widen the trust.
        $valid = array_values(array_filter($ranges, fn (string $range): bool => preg_match('#^[0-9a-fA-F:.]+/\d{1,3}$#', $range) === 1));

        if (count($valid) < 10) {
            $this->error('The answer does not look like Cloudflare\'s list; nothing saved.');

            return self::FAILURE;
        }

        file_put_contents(CloudflareIps::path(), json_encode($valid, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->info(count($valid).' ranges saved to '.CloudflareIps::path());

        return self::SUCCESS;
    }
}
