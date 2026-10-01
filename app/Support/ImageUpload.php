<?php

namespace App\Support;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Shared limits and safety for image uploads in the admin.
 */
final class ImageUpload
{
    /** Longest side allowed for an uploaded image. */
    public const MAX_SIDE = 4000;

    /**
     * Rejects images whose width or height is above MAX_SIDE, with a message
     * that names the ideal size for this field (e.g. "1920 x 1080 px"), and
     * stores only a re-encoded copy under a random name (see secure()).
     */
    public static function limitDimensions(SpatieMediaLibraryFileUpload $upload, string $idealSize): SpatieMediaLibraryFileUpload
    {
        $max = self::MAX_SIDE;

        return self::secure($upload)
            ->rule("dimensions:max_width={$max},max_height={$max}")
            ->validationMessages([
                'dimensions' => "Gambar terlalu besar: sisi terpanjang maksimal {$max} px. Perkecil dulu ke ukuran ideal {$idealSize}, lalu unggah lagi.",
            ]);
    }

    /**
     * Replaces Filament's save step for media uploads. Same options as the original, but the
     * bytes are re-encoded by SafeImage and the file name is a fresh ULID with the extension
     * of the DETECTED mime type (the original used the client's extension, so a PNG sent as
     * "evil.php" would have been stored as a ".php" file).
     */
    public static function secure(SpatieMediaLibraryFileUpload $upload): SpatieMediaLibraryFileUpload
    {
        return $upload->saveUploadedFileUsing(static function (SpatieMediaLibraryFileUpload $component, TemporaryUploadedFile $file, ?Model $record): ?string {
            if (! method_exists($record, 'addMediaFromString')) {
                return null;
            }

            if (! $file->exists()) {
                return null;
            }

            $bytes = $file->get();

            if ($bytes === false) {
                return null;
            }

            try {
                $clean = SafeImage::clean($bytes, (string) $file->getMimeType());
            } catch (InvalidArgumentException $e) {
                throw ValidationException::withMessages([$component->getStatePath() => $e->getMessage()]);
            }

            $media = $record->addMediaFromString($clean['content'])
                ->addCustomHeaders([...['ContentType' => $clean['mime']], ...$component->getCustomHeaders()])
                ->usingFileName(Str::ulid().'.'.$clean['extension'])
                ->usingName($component->getMediaName($file) ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                ->storingConversionsOnDisk($component->getConversionsDisk() ?? '')
                ->withCustomProperties($component->getCustomProperties($file))
                ->withManipulations($component->getManipulations())
                ->withResponsiveImagesIf($component->hasResponsiveImages())
                ->withProperties($component->getProperties())
                ->toMediaCollection($component->getCollection() ?? 'default', $component->getDiskName());

            return $media->getAttributeValue('uuid');
        });
    }
}
