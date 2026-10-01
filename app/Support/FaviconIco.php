<?php

namespace App\Support;

use GdImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * public/favicon.ico (16, 32 and 48 px), rebuilt from the favicon uploaded in
 * the admin. Browsers and crawlers that ask for /favicon.ico on their own see
 * the same icon as the <link rel="icon"> tags.
 */
final class FaviconIco
{
    public const SIZES = [16, 32, 48];

    /** Raster uploads GD can read; an .ico upload is copied as it is. */
    private const RASTER_MIMES = ['image/png', 'image/webp', 'image/jpeg'];

    private const ICO_MIMES = ['image/x-icon', 'image/vnd.microsoft.icon'];

    public static function path(): string
    {
        return (string) (config('site.favicon_ico_path') ?: public_path('favicon.ico'));
    }

    /**
     * Rebuild favicon.ico from a favicon media item. A missing GD extension or an
     * unsupported type keeps the current file.
     */
    public static function writeFromMedia(Media $media): bool
    {
        $mime = (string) $media->mime_type;

        if (! in_array($mime, [...self::RASTER_MIMES, ...self::ICO_MIMES], true)) {
            Log::info("favicon.ico not rebuilt: a {$mime} favicon cannot be converted; the current file is kept.");

            return false;
        }

        $contents = Storage::disk($media->disk)->get($media->getPathRelativeToRoot());

        if ($contents === null) {
            return false;
        }

        if (in_array($mime, self::ICO_MIMES, true)) {
            return self::put($contents);
        }

        $ico = self::fromImage($contents);

        return $ico !== null && self::put($ico);
    }

    /**
     * ICO file with one PNG-encoded entry per size (supported by every current
     * browser), the source scaled to fit and centred on a transparent square.
     */
    public static function fromImage(string $contents): ?string
    {
        if (! extension_loaded('gd')) {
            Log::warning('favicon.ico not rebuilt: the GD extension is not loaded.');

            return null;
        }

        $source = @imagecreatefromstring($contents);

        if (! $source instanceof GdImage) {
            return null;
        }

        $entries = [];

        foreach (self::SIZES as $size) {
            $entries[$size] = self::png($source, $size);
        }

        // ICONDIR, then one 16-byte ICONDIRENTRY per image, then the image data.
        $ico = pack('vvv', 0, 1, count($entries));
        $offset = 6 + 16 * count($entries);
        $data = '';

        foreach ($entries as $size => $png) {
            // Width and height 0 would mean 256; every size here is smaller.
            $ico .= pack('CCCCvvVV', $size, $size, 0, 0, 1, 32, strlen($png), $offset);
            $offset += strlen($png);
            $data .= $png;
        }

        return $ico.$data;
    }

    /**
     * @param  positive-int  $size
     */
    private static function png(GdImage $source, int $size): string
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = $size / max($width, $height);
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $image = imagecreatetruecolor($size, $size);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, (int) imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagecopyresampled(
            $image, $source,
            intdiv($size - $targetWidth, 2), intdiv($size - $targetHeight, 2), 0, 0,
            $targetWidth, $targetHeight, $width, $height,
        );

        ob_start();
        imagepng($image, null, 9);

        return (string) ob_get_clean();
    }

    private static function put(string $contents): bool
    {
        $path = self::path();

        if (! is_dir(dirname($path)) || @file_put_contents($path, $contents) === false) {
            Log::warning("favicon.ico could not be written to {$path}.");

            return false;
        }

        return true;
    }
}
