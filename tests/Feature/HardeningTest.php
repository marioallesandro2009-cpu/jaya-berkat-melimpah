<?php

use App\Models\ContactMessage;
use App\Models\Post;
use App\Support\LoginThrottle;
use App\Support\SafeImage;
use Database\Seeders\PostSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

beforeEach(fn () => seedSite());

function leadFrom(string $when): ContactMessage
{
    $lead = ContactMessage::query()->create(['name' => 'Old Lead', 'email' => 'old@lead.test', 'locale' => 'en', 'ip' => '203.0.113.9', 'user_agent' => 'Test Browser']);
    $lead->forceFill(['created_at' => now()->sub($when)])->saveQuietly();

    return $lead;
}

it('erases the IP of old inquiries and deletes very old ones (leads:prune)', function () {
    $fresh = leadFrom('2 days');
    $aging = leadFrom('61 days');
    $ancient = leadFrom('400 days');

    Artisan::call('leads:prune');

    expect($fresh->fresh()->ip)->toBe('203.0.113.9')
        ->and($aging->fresh()->ip)->toBeNull()
        ->and($aging->fresh()->user_agent)->toBeNull()
        ->and(ContactMessage::query()->whereKey($ancient->id)->exists())->toBeFalse();
});

it('locks an admin login after repeated failures, per email and IP', function () {
    Cache::flush();
    $throttle = new LoginThrottle('198.51.100.7', 'admin@example.test');

    expect($throttle->secondsUntilAvailable())->toBe(0);

    foreach (range(1, 12) as $i) {
        $throttle->fail();
    }

    expect($throttle->secondsUntilAvailable())->toBeGreaterThan(0)
        // another address from a different IP is not locked by this attacker
        ->and((new LoginThrottle('203.0.113.50', 'someone@else.test'))->secondsUntilAvailable())->toBe(0);

    $throttle->clear();
});

it('refuses SVG and script uploads and forces the extension from the detected type', function () {
    expect(fn () => SafeImage::clean('<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>', 'image/svg+xml'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => SafeImage::clean('<?php echo 1;', 'application/x-httpd-php'))->toThrow(InvalidArgumentException::class)
        ->and(SafeImage::extensionFor('image/png'))->toBe('png')
        ->and(SafeImage::extensionFor('image/jpeg'))->toBe('jpg');
});

it('re-encodes an image so hidden payloads do not survive', function () {
    $jpeg = (string) file_get_contents(database_path('seeders/images/dummy-product-1.jpg'));
    $payload = $jpeg.'<?php system($_GET["c"]); ?>';

    $clean = SafeImage::clean($payload, 'image/jpeg');

    expect($clean['extension'])->toBe('jpg')->and($clean['content'])->not->toContain('<?php')->not->toContain('system(');
});

it('never seeds the sample blog posts in production', function () {
    seedSite();
    Post::query()->get()->each->delete();
    app()->detectEnvironment(fn (): string => 'production');

    (new PostSeeder)->run();

    expect(Post::count())->toBe(0);
});
