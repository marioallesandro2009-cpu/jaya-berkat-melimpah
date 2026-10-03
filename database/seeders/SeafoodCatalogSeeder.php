<?php

namespace Database\Seeders;

use App\Models\Cut;
use App\Models\ProcessingMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SiteSetting;
use App\Models\Species;
use Database\Seeders\Concerns\SeedsImages;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;

/**
 * Demo seafood catalogue: category -> species -> cut -> product (data in data/seafood_catalog.php).
 *
 * Safe to run again: rows are matched by slug / product code and updated with updateOrCreate(), but
 * only while they are still sample rows (is_sample). A species or product that was saved in the admin
 * is left alone, and categories, cuts and processing methods that already exist are never overwritten.
 *
 * Order: categories, processing methods, cuts, species (+ their cuts), products (+ methods, photos, gallery).
 */
class SeafoodCatalogSeeder extends Seeder
{
    use SeedsImages;

    /** @var array<string, mixed> */
    private array $data;

    /** @var array<string, array<string, string>> */
    private array $images;

    private string $brand;

    public function run(): void
    {
        $this->data = require database_path('seeders/data/seafood_catalog.php');
        $this->images = json_decode((string) file_get_contents(database_path('seeders/data/catalog-images.json')), true, 512, JSON_THROW_ON_ERROR);
        $this->brand = (string) (SiteSetting::query()->value('company_name') ?: 'PT Jaya Berkat Melimpah');

        $this->removeOldSamples();

        $categories = $this->categories();
        $methods = $this->processingMethods();
        $cuts = $this->cuts();
        $species = $this->species($categories, $cuts);
        $this->products($species, $cuts, $methods);
    }

    /**
     * The three sample products and the three water-type categories of the first version are replaced
     * by this catalogue (only when no one added products to those categories).
     */
    private function removeOldSamples(): void
    {
        Product::query()->whereNull('product_code')->get()
            ->filter(fn (Product $product): bool => in_array($product->translation('name', 'en'), ['Fresh Grouper', 'Yellowfin Tuna', 'Red Snapper'], true))
            ->each->delete();

        ProductCategory::query()->whereIn('slug', ['marine', 'brackish-water', 'freshwater'])->doesntHave('products')->get()->each->delete();
    }

    /**
     * @return array<string, ProductCategory>
     */
    private function categories(): array
    {
        $rows = [];

        foreach ($this->data['categories'] as $i => [$slug, $name, $description, $color, $imageKey]) {
            $category = ProductCategory::query()->where('slug', $slug)->first() ?? ProductCategory::query()->create([
                'slug' => $slug,
                'name' => ['en' => $name],
                'description' => ['en' => $description],
                'meta_title' => ['en' => "{$name} | {$this->brand}"],
                'meta_description' => ['en' => Str::limit($description, 155, '')],
                'color' => $color,
                'sort_order' => $i + 1,
                'is_active' => true,
                ...$this->imageFields($imageKey),
            ]);

            $this->attachSeedPhoto($category, $imageKey);
            $rows[$slug] = $category;
        }

        return $rows;
    }

    /**
     * @return array<string, ProcessingMethod>
     */
    private function processingMethods(): array
    {
        $rows = [];

        foreach ($this->data['processing_methods'] as $i => [$slug, $name, $type, $temperature, $description]) {
            $rows[$slug] = ProcessingMethod::query()->where('slug', $slug)->first() ?? ProcessingMethod::query()->create([
                'slug' => $slug,
                'name' => ['en' => $name],
                'type' => $type,
                'temperature' => $temperature,
                'description' => ['en' => $description],
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
        }

        return $rows;
    }

    /**
     * @return array<string, Cut>
     */
    private function cuts(): array
    {
        $rows = [];

        foreach ($this->data['cuts'] as $i => [$slug, $name, $region, $description]) {
            $rows[$slug] = Cut::query()->where('slug', $slug)->first() ?? Cut::query()->create([
                'slug' => $slug,
                'name' => ['en' => $name],
                'body_region' => $region,
                'description' => ['en' => $description],
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
        }

        return $rows;
    }

    /**
     * @param  array<string, ProductCategory>  $categories
     * @param  array<string, Cut>  $cuts
     * @return array<string, Species>
     */
    private function species(array $categories, array $cuts): array
    {
        $rows = [];

        foreach ($this->data['species'] as $i => [$slug, $categorySlug, , $name, $scientific, $japanese, $description, $origin, $habitat, $suitable, , , $imageKey, $cutMap]) {
            $existing = Species::query()->where('slug', $slug)->first();

            if ($existing && ! $existing->is_sample) {
                $rows[$slug] = $existing;

                continue;
            }

            $species = Species::query()->updateOrCreate(['slug' => $slug], [
                'category_id' => $categories[$categorySlug]->id,
                'common_name' => ['en' => $name],
                'scientific_name' => $scientific,
                'japanese_name' => $japanese,
                'short_description' => ['en' => $description],
                'origin' => $origin,
                'habitat' => $habitat,
                'is_sashimi_suitable' => $suitable,
                'sashimi_grade' => $suitable ? 'Sashimi Suitable' : 'General Seafood',
                // Left empty on purpose: sustainability depends on the fishery, stock and catch method.
                'sustainability_info' => null,
                'meta_title' => ['en' => "{$name} ({$scientific}) | {$this->brand}"],
                'meta_description' => ['en' => Str::limit($description, 155, '')],
                'is_sample' => true,
                'is_active' => true,
                'sort_order' => $i + 1,
                ...$this->imageFields($imageKey),
            ]);

            $sync = [];
            $n = 0;

            foreach ($cutMap as $cutSlug => $available) {
                $sync[$cuts[$cutSlug]->id] = ['available_as_product' => $available, 'sort_order' => ++$n];
            }

            $species->cuts()->sync($sync);
            $this->attachSeedPhoto($species, $imageKey);
            $rows[$slug] = $species;
        }

        return $rows;
    }

    /**
     * @param  array<string, Species>  $species
     * @param  array<string, Cut>  $cuts
     * @param  array<string, ProcessingMethod>  $methods
     */
    private function products(array $species, array $cuts, array $methods): void
    {
        $speciesRows = [];

        foreach ($this->data['species'] as $row) {
            $speciesRows[$row[0]] = $row;
        }

        foreach ($this->data['products'] as $i => [$speciesSlug, $cutSlug, $methodSlugs, $freezing, $grade, $featured, $imageKey, $reference, $galleryKeys]) {
            [$prefix, $categorySlug] = [$speciesRows[$speciesSlug][2], $speciesRows[$speciesSlug][1]];
            [$suffix, $codePart, $texture, $usage, $packaging, $sentence] = $this->data['cut_info'][$cutSlug];

            $name = "{$prefix} {$suffix}";
            $code = sprintf('%s-%s-%03d', $this->data['codes'][$speciesSlug], $codePart, 1);
            $existing = Product::query()->where('product_code', $code)->first();

            if ($existing && ! $existing->is_sample) {
                continue;
            }

            [$freezeLabel, $temperature] = Product::FREEZING[$freezing];
            $short = str_replace('{s}', $prefix, $sentence);
            $processing = implode(', ', array_map(
                fn (string $slug): string => (string) $methods[$slug]->translation('name', 'en'),
                array_values(array_filter($methodSlugs, fn (string $slug): bool => $methods[$slug]->type !== 'freshness')),
            ));
            $color = match ($cutSlug) {
                'akami' => 'Deep red',
                'chutoro' => 'Pink-red with fine marbling',
                'otoro' => 'Pale pink with heavy marbling',
                default => $speciesRows[$speciesSlug][10],
            };
            $origin = in_array($speciesSlug, ['yellowfin-tuna', 'bigeye-tuna'], true) ? 'Indonesia' : 'Origin confirmed per shipment';
            $cutName = (string) $cuts[$cutSlug]->translation('name', 'en');
            $species_ = $species[$speciesSlug];
            $imageFields = $this->imageFields($imageKey);

            $product = Product::query()->updateOrCreate(['product_code' => $code], [
                'category_id' => $species_->category_id,
                'species_id' => $species_->id,
                'cut_id' => $cuts[$cutSlug]->id,
                'name' => ['en' => $name],
                'slug' => Str::slug($name),
                'description' => ['en' => $short],
                'intro' => ['en' => $short],
                'content' => ['en' => "<p>{$short}</p><p>Typical uses: {$usage}.</p><p>Supplied {$freezeLabel} ({$temperature})".($processing !== '' ? ", {$processing}" : '').'. Packaging and specification can be agreed per order.</p>'],
                'image_alt' => ['en' => $name.($reference ? ' (reference photo)' : '')],
                'body_part' => $cuts[$cutSlug]->body_region,
                'cut_type' => $cutName,
                'freezing_method' => $freezing,
                'temperature' => $temperature,
                'sashimi_grade' => $grade,
                'color' => $color,
                'texture' => $texture,
                'flavor_profile' => $speciesRows[$speciesSlug][11],
                'typical_usage' => $usage,
                'packaging' => $packaging,
                'shelf_life' => $this->data['shelf_life'][$freezing],
                'origin' => $origin,
                'certification' => null,
                'image_is_reference' => $reference,
                'gallery_urls' => $galleryKeys === [] ? null : array_values(array_map(fn (string $key): string => $this->images[$key]['url'], $galleryKeys)),
                'specs' => array_map(fn (array $row): array => ['label' => ['en' => $row[0]], 'value' => ['en' => $row[1]]], array_values(array_filter([
                    ['Cut', $cutName],
                    $processing !== '' ? ['Processing', $processing] : null,
                    ['Storage', "{$freezeLabel}, {$temperature}"],
                    ['Typical usage', $usage],
                    ['Species', "{$species_->translation('common_name', 'en')} ({$species_->scientific_name})"],
                    ['Packaging', $packaging],
                    ['Shelf life', $this->data['shelf_life'][$freezing]],
                    ['Grade', $grade],
                ]))),
                'seo_title' => ['en' => "{$name} | {$this->brand}"],
                'seo_description' => ['en' => Str::limit("{$short} Supplied {$freezeLabel}.", 155, '')],
                'detail_status' => Product::PUBLISHED,
                'is_featured' => $featured,
                'is_sample' => true,
                'is_active' => true,
                'sort_order' => $i + 1,
                ...$imageFields,
            ]);

            $product->processingMethods()->sync(array_map(fn (string $slug): int => $methods[$slug]->id, $methodSlugs));
            $this->attachSeedPhoto($product, $imageKey);

            if ($galleryKeys !== [] && $product->getMedia('gallery')->isEmpty() && config('site.seed_service_images', true)) {
                foreach ($galleryKeys as $key) {
                    $product->addMedia($this->seedImagePath($this->images[$key]['file'], 1600, 1000))->preservingOriginal()->toMediaCollection('gallery');
                }
            }
        }
    }

    /**
     * Reference columns of a photo: the Commons file URL and a one-line credit.
     *
     * @return array{image_url: string, image_credit: string}
     */
    private function imageFields(string $key): array
    {
        $image = $this->images[$key];

        return [
            'image_url' => $image['url'],
            'image_credit' => Str::limit("{$image['title']} / {$image['author']} / {$image['license']} / {$image['source']}", 480, ''),
        ];
    }

    private function attachSeedPhoto(HasMedia $model, string $key): void
    {
        $this->attachServiceImage($model, 'image', $this->images[$key]['file'], 1600, 1000);
    }
}
