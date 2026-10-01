<?php

namespace App\Filament\Support;

use App\Models\Contracts\HasTranslatableFields;
use App\Support\Locales;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

/**
 * For resources whose $recordTitleAttribute is a text per language: the title
 * (breadcrumbs, page headings, notifications) is the English text.
 */
trait HasTranslatableRecordTitle
{
    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        if ($record instanceof HasTranslatableFields && static::$recordTitleAttribute !== null) {
            return $record->translate(static::$recordTitleAttribute, Locales::default()) ?? static::getModelLabel();
        }

        return parent::getRecordTitle($record);
    }
}
