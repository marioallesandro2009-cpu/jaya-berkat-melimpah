<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Species;
use App\Support\FrontendData;
use App\Support\Links;
use App\Support\Locales;
use App\Support\Seo;
use App\Support\StructuredData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Public "photo credits" page: author, licence and source of every photograph that is used under a free
 * licence (CC BY and CC BY-SA require the author to be named where the photo is used or on a page linked from
 * the site). The list comes from the photos that are on the site right now: the credit is stored with the media
 * (custom property "credit"), so it disappears by itself when a photo is replaced or deleted in the admin.
 */
class CreditsController extends Controller
{
    private const CACHE_KEY = 'jbm.photo-credits.exists';

    public function __invoke(): View
    {
        $data = FrontendData::all();
        $credits = self::credits();
        $seo = Seo::page($data, 'credits', (string) $data['settings']['texts']['credits_title'], (string) $data['settings']['texts']['credits_intro']);

        Locales::setPagePaths(Links::pagePaths('credits'));

        return view('credits', [
            ...$data,
            'credits' => $credits,
            'seo' => $seo,
            'jsonLd' => StructuredData::page($data, $seo),
        ]);
    }

    /**
     * Whether any photo with a credit is on the site (the footer shows the link only then). Cached for a few
     * minutes: it is asked on every page.
     */
    public static function exists(): bool
    {
        return (bool) Cache::remember(self::CACHE_KEY, 300, fn (): bool => self::credits() !== []);
    }

    /**
     * One entry per photograph (the same photo can be used by several products).
     *
     * @return list<array{title: string, author: string, license: string, licenseUrl: string|null, source: string}>
     */
    public static function credits(): array
    {
        $found = [];

        Media::query()
            ->whereIn('model_type', [Product::class, Species::class, ProductCategory::class])
            ->get()
            ->each(function (Media $media) use (&$found): void {
                $credit = $media->getCustomProperty('credit');

                if (! is_array($credit) || blank($credit['source'] ?? null)) {
                    return;
                }

                $found[(string) $credit['source']] ??= [
                    'title' => (string) ($credit['title'] ?? ''),
                    'author' => (string) ($credit['author'] ?? ''),
                    'license' => (string) ($credit['license'] ?? ''),
                    'licenseUrl' => self::licenseUrl((string) ($credit['license'] ?? '')),
                    'source' => (string) $credit['source'],
                ];
            });

        $credits = array_values($found);
        usort($credits, fn (array $a, array $b): int => strcasecmp($a['title'], $b['title']));

        return $credits;
    }

    /**
     * "CC BY-SA 4.0" -> https://creativecommons.org/licenses/by-sa/4.0/ ; "CC BY-SA 3.0 it" -> .../3.0/it/ ; "CC0" -> zero.
     */
    public static function licenseUrl(string $license): ?string
    {
        if (preg_match('/^CC BY(-SA)? (\d\.\d)(?: ([a-z]{2}))?$/i', trim($license), $m)) {
            return 'https://creativecommons.org/licenses/by'.(strtoupper($m[1]) === '-SA' ? '-sa' : '').'/'.$m[2].'/'.(isset($m[3]) ? strtolower($m[3]).'/' : '');
        }

        return strtoupper(trim($license)) === 'CC0' ? 'https://creativecommons.org/publicdomain/zero/1.0/' : null;
    }
}
