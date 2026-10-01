<?php

namespace Database\Seeders\Concerns;

use App\Support\PlaceholderImage;
use Spatie\MediaLibrary\HasMedia;

trait SeedsImages
{
    /**
     * Attach database/seeders/images/{name}.(png|jpg|jpeg|webp) to a collection.
     * A placeholder PNG is generated first when the file does not exist.
     */
    protected function attachSeedImage(HasMedia $model, string $collection, string $name, int $width, int $height): void
    {
        if ($model->getMedia($collection)->isNotEmpty()) {
            return;
        }

        $model->addMedia($this->seedImagePath($name, $width, $height))
            ->preservingOriginal()
            ->toMediaCollection($collection);
    }

    /**
     * Photos of the service pages (services, fleet, locations). Tests skip them unless
     * they ask for them (config site.seed_service_images = false): they are the
     * slowest part of seeding the site.
     */
    protected function attachServiceImage(HasMedia $model, string $collection, string $name, int $width, int $height): void
    {
        if (config('site.seed_service_images', true)) {
            $this->attachSeedImage($model, $collection, $name, $width, $height);
        }
    }

    protected function seedImagePath(string $name, int $width, int $height): string
    {
        foreach (['png', 'jpg', 'jpeg', 'webp'] as $extension) {
            $path = database_path("seeders/images/{$name}.{$extension}");

            if (is_file($path)) {
                return $this->smallCopy($path) ?? $path;
            }
        }

        $placeholder = storage_path("app/private/seed-placeholders/{$name}.png");

        if (! is_file($placeholder)) {
            PlaceholderImage::create($placeholder, $width, $height, "{$name}.png");
            $this->command->warn("Gambar {$name} tidak ditemukan, placeholder dibuat.");
        }

        return $placeholder;
    }

    /**
     * Tests only (config site.seed_image_max_width): a downscaled copy with the
     * same file name, cached on disk, so seeding the full site with its WebP
     * conversions stays fast. Null when not configured or not a raster image.
     */
    private function smallCopy(string $path): ?string
    {
        $max = (int) config('site.seed_image_max_width');
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // Only the large photos: logos and the favicon keep their real size.
        if ($max <= 0 || ! extension_loaded('gd') || ! in_array($extension, ['jpg', 'jpeg', 'png'], true)
            || (int) (getimagesize($path)[0] ?? 0) <= 1000) {
            return null;
        }

        $copy = storage_path("framework/testing/seed-images/{$max}/".basename($path));

        if (is_file($copy) && filemtime($copy) >= filemtime($path)) {
            return $copy;
        }

        $source = @imagecreatefromstring((string) file_get_contents($path));

        if (! $source) {
            return null;
        }

        $scaled = imagescale($source, $max);

        if ($scaled === false) {
            return null;
        }

        @mkdir(dirname($copy), 0777, true);
        imagesavealpha($scaled, true);
        $extension === 'png' ? imagepng($scaled, $copy) : imagejpeg($scaled, $copy, 80);

        return $copy;
    }
}
