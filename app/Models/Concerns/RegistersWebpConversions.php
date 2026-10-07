<?php

namespace App\Models\Concerns;

use App\Support\ImageConversions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @mixin InteractsWithMedia
 */
trait RegistersWebpConversions
{
    /**
     * Register a WebP conversion, but only when conversions are enabled
     * (see App\Support\ImageConversions and IMAGE_CONVERSIONS in .env).
     *
     * @param  string|list<string>  $collections
     */
    protected function addWebpConversion(string $name, int $width, string|array $collections, int $quality = 78): void
    {
        if (! ImageConversions::enabled()) {
            return;
        }

        // Conversion options first: the image manipulations return the driver type.
        $conversion = $this->addMediaConversion($name)
            ->performOnCollections(...(array) $collections)
            ->nonQueued();

        if (! ImageConversions::canOptimize()) {
            $conversion->nonOptimized();
        }

        $conversion
            ->format('webp')
            ->fit(Fit::Max, $width, $width * 4)
            ->quality($quality);
    }
}
