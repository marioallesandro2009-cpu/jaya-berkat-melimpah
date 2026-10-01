<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\HasTranslatableFields;
use App\Support\Links;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

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
#[Fillable(['slug', 'name', 'description', 'color', 'sort_order', 'is_active', 'translation_status'])]
class ProductCategory extends Model implements HasTranslatableFields
{
    use FlushesFrontendCache, HasTranslations, Sortable;

    public const TRANSLATABLE = ['name', 'description'];

    public const DEFAULT_COLOR = '#7CC4E4';

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $category): void {
            $category->slug = Str::slug((string) ($category->slug ?: $category->translation('name', 'en'))) ?: 'category-'.Str::lower(Str::random(6));

            if (! preg_match('/^#[0-9a-fA-F]{6}$/', (string) $category->color)) {
                $category->color = self::DEFAULT_COLOR;
            }
        });
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
            'color' => $this->color,
            'url' => Links::page('products').'?category='.$this->slug,
        ];
    }
}
