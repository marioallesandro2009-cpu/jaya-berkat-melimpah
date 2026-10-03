<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\RegistersWebpConversions;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\HasTranslatableFields;
use App\Support\Links;
use App\Support\MediaPresenter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A product category (e.g. marine, brackish water, freshwater). Products point to it with
 * category_id; deleting a category leaves its products uncategorised. The public /products page
 * filters by it with ?category=<slug>.
 *
 * @property int $id
 * @property string $slug
 * @property array<string, string|null>|null $name
 * @property array<string, string|null>|null $description
 * @property string $color
 * @property int $sort_order
 * @property bool $is_active
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['slug', 'name', 'description', 'meta_title', 'meta_description', 'image_url', 'image_credit', 'color', 'sort_order', 'is_active', 'translation_status'])]
class ProductCategory extends Model implements HasMedia, HasTranslatableFields
{
    use FlushesFrontendCache, HasTranslations, InteractsWithMedia, RegistersWebpConversions, Sortable;

    public const TRANSLATABLE = ['name', 'description', 'meta_title', 'meta_description'];

    public const DEFAULT_COLOR = '#7CC4E4';

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $category): void {
            $base = Str::slug((string) ($category->slug ?: $category->translation('name', 'en'))) ?: 'category';
            $slug = $base;

            for ($n = 2; static::query()->where('slug', $slug)->whereKeyNot($category->getKey() ?? 0)->exists(); $n++) {
                $slug = $base.'-'.$n;
            }

            $category->slug = $slug;

            if (! preg_match('/^#[0-9a-fA-F]{6}$/', (string) $category->color)) {
                $category->color = self::DEFAULT_COLOR;
            }
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile()->acceptsMimeTypes(SiteSetting::IMAGE_MIMES);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addWebpConversion('lg', 1200, 'image');
        $this->addWebpConversion('sm', 600, 'image');
    }

    /**
     * @return HasMany<Species, $this>
     */
    public function species(): HasMany
    {
        return $this->hasMany(Species::class, 'category_id');
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => (string) $this->translate('name'),
            'description' => $this->translate('description'),
            'color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) $this->color) ? $this->color : self::DEFAULT_COLOR,
            'url' => Links::page('products').'?category='.$this->slug,
            'pageUrl' => $this->detailPath(),
        ];
    }

    /**
     * Path of the category page: /products/tuna, /id/produk/tuna.
     */
    public function detailPath(?string $locale = null): string
    {
        return Links::page('products', $locale).'/'.$this->slug;
    }

    /**
     * The category's own photo (only the category page needs it, so it is not part of toFrontend()).
     *
     * @return array<string, mixed>|null
     */
    public function imagePayload(): ?array
    {
        return MediaPresenter::image($this->getFirstMedia('image'), 'lg', 'sm', 1600, 1000, (string) $this->translate('name'));
    }
}
