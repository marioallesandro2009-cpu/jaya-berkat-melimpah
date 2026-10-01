<?php

namespace App\Support;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Turns media into the payload the frontend expects. The shape is identical
 * whether or not WebP conversions exist: when a conversion is missing, both
 * sizes point at the original file.
 *
 * @phpstan-type ImageSize array{url: string, width: int, height: int}
 * @phpstan-type ImagePayload array{large: ImageSize, small: ImageSize, alt: string}
 */
final class MediaPresenter
{
    /**
     * Read and remember the original dimensions of an uploaded image.
     */
    public static function storeDimensions(Media $media): void
    {
        if ($media->hasCustomProperty('width')) {
            return;
        }

        [$width, $height] = self::readDimensions($media);

        if ($width > 0) {
            $media->setCustomProperty('width', $width);
            $media->setCustomProperty('height', $height);
            $media->saveQuietly();
        }
    }

    /**
     * @return ImagePayload|null
     */
    public static function image(?Media $media, string $large, string $small, int $largeWidth, int $smallWidth, ?string $alt = null): ?array
    {
        if (! $media) {
            return null;
        }

        return [
            'large' => self::size($media, $large, $largeWidth),
            'small' => self::size($media, $small, $smallWidth),
            'alt' => (string) $alt,
        ];
    }

    /**
     * srcset for an image payload; a single candidate when both sizes are the original file.
     *
     * @param  ImagePayload  $image
     */
    public static function srcset(array $image): string
    {
        ['large' => $large, 'small' => $small] = $image;

        if ($small['url'] === $large['url']) {
            return $large['url'].' '.$large['width'].'w';
        }

        return $small['url'].' '.$small['width'].'w, '.$large['url'].' '.$large['width'].'w';
    }

    /**
     * The original file with its dimensions.
     *
     * @return ImageSize
     */
    public static function original(Media $media): array
    {
        [$width, $height] = self::originalDimensions($media);

        return ['url' => $media->getUrl(), 'width' => $width, 'height' => $height];
    }

    /**
     * Logo payload: the original file (PNG/WebP/JPEG) plus the WebP version when available.
     *
     * @return array{url: string, webp: string|null, width: int, height: int}|null
     */
    public static function logo(?Media $media, string $webpConversion): ?array
    {
        if (! $media) {
            return null;
        }

        [$width, $height] = self::originalDimensions($media);

        return [
            'url' => $media->getUrl(),
            'webp' => self::hasConversion($media, $webpConversion) ? $media->getUrl($webpConversion) : null,
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * @return ImageSize
     */
    private static function size(Media $media, string $conversion, int $targetWidth): array
    {
        [$width, $height] = self::originalDimensions($media);

        if (! self::hasConversion($media, $conversion)) {
            return ['url' => $media->getUrl(), 'width' => $width, 'height' => $height];
        }

        // Conversions use Fit::Max, so images are never upscaled.
        $scaledWidth = $width > 0 ? min($targetWidth, $width) : $targetWidth;
        $scaledHeight = $width > 0 ? (int) round($height * $scaledWidth / $width) : 0;

        return ['url' => $media->getUrl($conversion), 'width' => $scaledWidth, 'height' => $scaledHeight];
    }

    /** Favicon sizes: browser tab (32), iOS home screen (180), web app manifest (192). */
    public const FAVICON_SIZES = [32, 180, 192];

    /**
     * Favicon payload: the original file plus one URL per size in FAVICON_SIZES
     * (the original when that size was not generated, e.g. for an ICO).
     *
     * @return array{url: string, mime: string, width: int, height: int, sizes: array<int, string>}|null
     */
    public static function favicon(?Media $media): ?array
    {
        if (! $media) {
            return null;
        }

        [$width, $height] = self::originalDimensions($media);
        $sizes = [];

        foreach (self::FAVICON_SIZES as $size) {
            $sizes[$size] = self::hasConversion($media, "favicon_{$size}") ? $media->getUrl("favicon_{$size}") : $media->getUrl();
        }

        return ['url' => $media->getUrl(), 'mime' => (string) $media->mime_type, 'width' => $width, 'height' => $height, 'sizes' => $sizes];
    }

    /**
     * A conversion file exists and is still registered: after switching conversions
     * off (IMAGE_CONVERSIONS=off) older media still list their generated WebP files,
     * but getUrl() would throw for a conversion that is no longer registered.
     */
    private static function hasConversion(Media $media, string $conversion): bool
    {
        return ImageConversions::enabled() && $media->hasGeneratedConversion($conversion);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function originalDimensions(Media $media): array
    {
        if ($media->hasCustomProperty('width')) {
            return [(int) $media->getCustomProperty('width'), (int) $media->getCustomProperty('height')];
        }

        return self::readDimensions($media);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function readDimensions(Media $media): array
    {
        $path = $media->getPath();

        $size = is_file($path) ? @getimagesize($path) : false;

        return $size ? [(int) $size[0], (int) $size[1]] : [0, 0];
    }
}
