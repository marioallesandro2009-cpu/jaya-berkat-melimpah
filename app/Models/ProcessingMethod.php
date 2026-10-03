<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\HasTranslatableFields;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * A processing method a product can carry (Fresh, Frozen, Super Frozen, Skinless, Skin-on,
 * Boneless, Trimmed, CO Treated). type groups the choices that exclude each other:
 * freshness (Fresh / Frozen / Super Frozen), skin, bone, trim, treatment.
 *
 * @property int $id
 * @property string $slug
 * @property array<string, string|null>|null $name
 * @property string $type
 * @property array<string, string|null>|null $description
 * @property string|null $temperature
 * @property bool $is_active
 * @property int $sort_order
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['slug', 'name', 'type', 'description', 'temperature', 'is_active', 'sort_order', 'translation_status'])]
class ProcessingMethod extends Model implements HasTranslatableFields
{
    use FlushesFrontendCache, HasTranslations, Sortable;

    public const TRANSLATABLE = ['name', 'description'];

    public const TYPES = [
        'freshness' => 'Kesegaran / pembekuan',
        'skin' => 'Kulit',
        'bone' => 'Tulang',
        'trim' => 'Perapian',
        'treatment' => 'Perlakuan',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $method): void {
            $base = Str::slug((string) ($method->slug ?: $method->translation('name', 'en'))) ?: 'method';
            $slug = $base;

            for ($n = 2; static::query()->where('slug', $slug)->whereKeyNot($method->getKey() ?? 0)->exists(); $n++) {
                $slug = $base.'-'.$n;
            }

            $method->slug = $slug;
        });
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'processing_method_product');
    }
}
