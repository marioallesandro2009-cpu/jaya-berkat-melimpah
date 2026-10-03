<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasSampleFlag;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\RegistersWebpConversions;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\HasTranslatableFields;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A fish species in the catalogue ("Yellowfin Tuna", Thunnus albacares, Kihada Maguro).
 *
 * A species being suitable for sashimi (is_sashimi_suitable) says nothing about what the
 * company sells: that is decided per product (products.sashimi_grade) and per cut
 * (cut_species.available_as_product).
 *
 * @property int $id
 * @property int|null $category_id
 * @property string $slug
 * @property array<string, string|null>|null $common_name
 * @property string|null $scientific_name
 * @property string|null $japanese_name
 * @property array<string, string|null>|null $short_description
 * @property string|null $origin
 * @property string|null $habitat
 * @property bool $is_sashimi_suitable
 * @property string|null $sashimi_grade
 * @property string|null $sustainability_info
 * @property string|null $image_url
 * @property string|null $image_credit
 * @property array<string, string|null>|null $meta_title
 * @property array<string, string|null>|null $meta_description
 * @property bool $is_sample
 * @property bool $is_active
 * @property int $sort_order
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['category_id', 'slug', 'common_name', 'scientific_name', 'japanese_name', 'short_description', 'origin', 'habitat', 'is_sashimi_suitable', 'sashimi_grade', 'sustainability_info', 'image_url', 'image_credit', 'meta_title', 'meta_description', 'is_sample', 'is_active', 'sort_order', 'translation_status'])]
class Species extends Model implements HasMedia, HasTranslatableFields
{
    use FlushesFrontendCache, HasSampleFlag, HasTranslations, InteractsWithMedia, RegistersWebpConversions, Sortable;

    public const TRANSLATABLE = ['common_name', 'short_description', 'meta_title', 'meta_description'];

    protected $table = 'species';

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_sashimi_suitable' => 'boolean', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $species): void {
            $base = Str::slug((string) ($species->slug ?: $species->translation('common_name', 'en'))) ?: 'species';
            $slug = $base;

            for ($n = 2; static::query()->where('slug', $slug)->whereKeyNot($species->getKey() ?? 0)->exists(); $n++) {
                $slug = $base.'-'.$n;
            }

            $species->slug = $slug;
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
     * @return BelongsTo<ProductCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /**
     * Cuts this species is offered in. pivot.available_as_product: really sold as a product.
     *
     * @return BelongsToMany<Cut, $this>
     */
    public function cuts(): BelongsToMany
    {
        return $this->belongsToMany(Cut::class, 'cut_species')->withPivot(['available_as_product', 'sort_order'])->withTimestamps()->orderByPivot('sort_order');
    }

    /**
     * @return HasMany<SpeciesCut, $this>
     */
    public function cutLinks(): HasMany
    {
        return $this->hasMany(SpeciesCut::class, 'species_id');
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'species_id');
    }
}
