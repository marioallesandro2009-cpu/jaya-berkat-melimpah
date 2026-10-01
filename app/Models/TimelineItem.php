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
 * A milestone on the company page ("2011 - Company founded"). year_label is text per
 * language so it can also be "Today" / "Hari ini".
 *
 * @property int $id
 * @property array<string, string|null>|null $year_label
 * @property array<string, string|null>|null $title
 * @property array<string, string|null>|null $body
 * @property bool $is_sample
 * @property int $sort_order
 * @property bool $is_active
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['year_label', 'title', 'body', 'is_sample', 'sort_order', 'is_active', 'translation_status'])]
class TimelineItem extends Model implements HasTranslatableFields
{
    use FlushesFrontendCache, HasSampleFlag, HasTranslations, Sortable;

    public const TRANSLATABLE = ['year_label', 'title', 'body'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        return ['id' => $this->id, 'year' => (string) $this->translate('year_label'), 'title' => (string) $this->translate('title'), 'body' => $this->translate('body')];
    }
}
