<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\HasTranslatableFields;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A short text block, grouped by where it is shown: quality rows, quality checkpoints
 * (the small "Intake inspection / Final packing" line), sustainability chapters and the
 * company values.
 *
 * @property int $id
 * @property string $group
 * @property array<string, string|null>|null $title
 * @property array<string, string|null>|null $body
 * @property int $sort_order
 * @property bool $is_active
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['group', 'title', 'body', 'sort_order', 'is_active', 'translation_status'])]
class Feature extends Model implements HasTranslatableFields
{
    use FlushesFrontendCache, HasTranslations, Sortable;

    public const TRANSLATABLE = ['title', 'body'];

    public const QUALITY = 'quality';

    public const CHECKPOINT = 'checkpoint';

    public const SUSTAINABILITY = 'sustainability';

    public const VALUE = 'value';

    public const PRODUCT_FORM = 'product_form';

    public const GROUPS = [
        self::QUALITY => 'Mutu (baris teks)',
        self::CHECKPOINT => 'Mutu (titik pemeriksaan)',
        self::SUSTAINABILITY => 'Keberlanjutan',
        self::VALUE => 'Nilai perusahaan',
        self::PRODUCT_FORM => 'Bentuk produk (halaman Semua Produk)',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeGroup(Builder $query, string $group): void
    {
        $query->where('group', $group);
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        return ['id' => $this->id, 'group' => $this->group, 'title' => (string) $this->translate('title'), 'body' => $this->translate('body')];
    }
}
