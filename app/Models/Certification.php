<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasSampleFlag;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\HasTranslatableFields;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A standard or certificate listed on the company page. Only add certificates the
 * company really holds.
 *
 * @property int $id
 * @property string $name
 * @property array<string, string|null>|null $status_label
 * @property bool $is_sample
 * @property int $sort_order
 * @property bool $is_active
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['name', 'status_label', 'is_sample', 'sort_order', 'is_active', 'translation_status'])]
class Certification extends Model implements HasTranslatableFields
{
    use FlushesFrontendCache, HasSampleFlag, HasTranslations, Sortable;

    public const TRANSLATABLE = ['status_label'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'status' => (string) $this->translate('status_label')];
    }
}
