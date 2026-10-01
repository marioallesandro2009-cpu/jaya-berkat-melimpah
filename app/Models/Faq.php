<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\HasTranslatableFields;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A question and answer in the FAQ block (hidden while there are none). Also emitted as
 * FAQPage structured data for search engines.
 *
 * @property int $id
 * @property array<string, string|null>|null $question
 * @property array<string, string|null>|null $answer
 * @property int $sort_order
 * @property bool $is_active
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['question', 'answer', 'sort_order', 'is_active', 'translation_status'])]
class Faq extends Model implements HasTranslatableFields
{
    use FlushesFrontendCache, HasTranslations, Sortable;

    public const TRANSLATABLE = ['question', 'answer'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        return ['id' => $this->id, 'question' => (string) $this->translate('question'), 'answer' => (string) $this->translate('answer')];
    }
}
