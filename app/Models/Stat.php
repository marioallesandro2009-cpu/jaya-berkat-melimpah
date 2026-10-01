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
 * A figure in the "scale" strip under the home page statement ("15+ years"). A number
 * counts up when it scrolls into view; a stat with only a text value ("Jakarta") does not.
 *
 * @property int $id
 * @property string|null $value
 * @property string|null $suffix
 * @property array<string, string|null>|null $text_value
 * @property array<string, string|null>|null $label
 * @property bool $is_sample
 * @property int $sort_order
 * @property bool $is_active
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['value', 'suffix', 'text_value', 'label', 'is_sample', 'sort_order', 'is_active', 'translation_status'])]
class Stat extends Model implements HasTranslatableFields
{
    use FlushesFrontendCache, HasSampleFlag, HasTranslations, Sortable;

    public const TRANSLATABLE = ['label', 'text_value'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        $value = $this->value !== null && $this->value !== '' ? (float) $this->value : null;

        return [
            'id' => $this->id,
            'value' => $value,
            'suffix' => $this->suffix,
            'textValue' => $this->translate('text_value'),
            'label' => (string) $this->translate('label'),
        ];
    }
}
