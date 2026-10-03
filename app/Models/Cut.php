<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\HasTranslatableFields;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A cut or body part (Whole / Round, Loin, Saku, Akami, Chutoro, Otoro, Fillet, Steak...).
 * Which cuts a species is offered in, and which of them are sold as products, lives in the
 * cut_species pivot (available_as_product).
 *
 * @property int $id
 * @property string $slug
 * @property array<string, string|null>|null $name
 * @property array<string, string|null>|null $description
 * @property string|null $body_region
 * @property bool $is_active
 * @property int $sort_order
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['slug', 'name', 'description', 'body_region', 'is_active', 'sort_order', 'translation_status'])]
class Cut extends Model implements HasTranslatableFields
{
    use FlushesFrontendCache, HasTranslations, Sortable;

    public const TRANSLATABLE = ['name', 'description'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $cut): void {
            $base = Str::slug((string) ($cut->slug ?: $cut->translation('name', 'en'))) ?: 'cut';
            $slug = $base;

            for ($n = 2; static::query()->where('slug', $slug)->whereKeyNot($cut->getKey() ?? 0)->exists(); $n++) {
                $slug = $base.'-'.$n;
            }

            $cut->slug = $slug;
        });
    }

    /**
     * @return BelongsToMany<Species, $this>
     */
    public function species(): BelongsToMany
    {
        return $this->belongsToMany(Species::class, 'cut_species')->withPivot(['available_as_product', 'sort_order'])->withTimestamps();
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'cut_id');
    }
}
