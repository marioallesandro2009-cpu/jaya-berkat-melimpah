<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Re-encodes an uploaded image with GD before it is stored (SECURITY_AUDIT F-1, F-3):
 *
 * - the stored bytes are a fresh, decoded-and-encoded image, so anything hidden in the
 *   file (PHP code in a comment chunk or trailing data, a polyglot, EXIF/GPS) is gone;
 * - the file extension comes from the DETECTED mime type, never from the client's file
 *   name (a PNG uploaded as "evil.php" is stored as ".png");
 * - only PNG, JPEG, WebP and ICO are accepted; SVG is not.
 *
 * ICO cannot be decoded by GD: it is kept byte for byte after a header check (the
 * extension is still forced to ".ico", and the upload folder blocks script execution).
 */
final class SafeImage
{
    /** Accepted mime types and the extension each is stored with. */
    public const EXTENSIONS = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/x-icon' => 'ico',
        'image/vnd.microsoft.icon' => 'ico',
    ];

    public static function extensionFor(string $mime): ?string
    {
        return self::EXTENSIONS[strtolower($mime)] ?? null;
    }

    /**
     * @return array{content: string, mime: string, extension: string}
     *
     * @throws InvalidArgumentException when the bytes are not a valid image of an accepted type
     */
    public static function clean(string $bytes, string $mime): array
    {
        $extension = self::extensionFor($mime) ?? throw new InvalidArgumentException("Tipe file {$mime} tidak diizinkan.");

        if ($extension === 'ico') {
            if (strncmp($bytes, "\x00\x00\x01\x00", 4) !== 0) {
                throw new InvalidArgumentException('File ICO tidak valid.');
            }

            return ['content' => $bytes, 'mime' => 'image/x-icon', 'extension' => 'ico'];
        }

        if (! extension_loaded('gd')) {
            // Without GD nothing can be re-encoded; the extension is still forced from the
            // detected mime and the folder blocks execution.
            Log::warning('SafeImage: GD tidak aktif, gambar disimpan tanpa re-encode.');

            return ['content' => $bytes, 'mime' => $mime, 'extension' => $extension];
        }

        $source = @imagecreatefromstring($bytes);

        if ($source === false) {
            throw new InvalidArgumentException('Gambar rusak atau bukan gambar yang valid.');
        }

        if ($extension === 'jpg') {
            $source = self::applyExifOrientation($source, $bytes);
        }

        $canvas = self::toTruecolor($source, $extension !== 'jpg');

        ob_start();
        $written = match ($extension) {
            'png' => imagepng($canvas, null, 6),
            'webp' => imagewebp($canvas, null, 90),
            default => imagejpeg($canvas, null, 92),
        };
        $content = (string) ob_get_clean();

        if (! $written || $content === '') {
            throw new InvalidArgumentException('Gambar tidak dapat diproses.');
        }

        return ['content' => $content, 'mime' => $mime === 'image/jpg' ? 'image/jpeg' : $mime, 'extension' => $extension];
    }

    /**
     * A true-colour copy (palette images cannot be saved as WebP), shrunk to
     * ImageUpload::MAX_SIDE if larger, keeping transparency for PNG and WebP.
     */
    private static function toTruecolor(\GdImage $source, bool $keepAlpha): \GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $scale = min(1, ImageUpload::MAX_SIDE / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);

        if ($keepAlpha) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefill($canvas, 0, 0, (int) imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        } else {
            imagefill($canvas, 0, 0, (int) imagecolorallocate($canvas, 255, 255, 255));
        }

        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        return $canvas;
    }

    /**
     * Phone photos carry their rotation in EXIF, which this re-encode removes: turn the
     * pixels first so the photo is not shown sideways afterwards.
     */
    private static function applyExifOrientation(\GdImage $image, string $bytes): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data('data://image/jpeg;base64,'.base64_encode($bytes));
        $degrees = match ($exif['Orientation'] ?? 1) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($degrees === 0) {
            return $image;
        }

        return imagerotate($image, $degrees, 0) ?: $image;
    }
}
