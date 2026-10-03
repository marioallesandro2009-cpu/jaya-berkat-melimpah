<?php

namespace App\Filament\Support;

use App\Models\Contracts\HasTranslatableFields;
use App\Support\Locales;
use App\Support\Translation\TranslationFailed;
use App\Support\Translation\Translator;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

/**
 * Admin helpers for texts stored per language (App\Models\Concerns\HasTranslations).
 *
 * Forms show one field per language side by side (EN | ID) so the versions can be
 * compared. A field named "<field>.<locale>" also gets:
 * - a "Buat draf dari .." button (it explains how to switch the service on while none is configured);
 * - under a translation (ID): "Draf - perlu dicek" | "Sudah dicek". Only checked translations
 *   are shown on the site; the others show the English text.
 */
final class Translatable
{
    /** Table badge per language. */
    public const COMPLETE = 'Lengkap';

    public const NEEDS_REVIEW = 'Perlu dicek';

    public const MISSING = 'Belum diisi';

    /**
     * @param  Closure(string $locale): Field  $make  builds the field for one language
     */
    public static function fields(Closure $make): Grid
    {
        $columns = [];

        foreach (Locales::all() as $locale) {
            $field = $make($locale);
            $base = self::baseName($field->getName(), $locale);
            $columns[] = $base === null ? $field : self::withReview($field, $base, $locale);
        }

        return Grid::make(count(Locales::all()))
            ->schema($columns)
            ->columnSpanFull();
    }

    /**
     * One field per language side by side, without the per-field review buttons
     * (block contents: the block has one "Versi ID sudah dicek" switch instead).
     *
     * @param  Closure(string $locale): Field  $make
     */
    public static function pair(Closure $make): Grid
    {
        return Grid::make(count(Locales::all()))
            ->schema(array_map($make, Locales::all()))
            ->columnSpanFull();
    }

    /**
     * "Judul" -> "Judul (EN)".
     */
    public static function label(string $label, string $locale): string
    {
        return $label.' ('.strtoupper($locale).')';
    }

    /**
     * Only the default language (English) is required; an empty or unchecked
     * Indonesian version shows the English text on the site.
     */
    public static function isRequired(string $locale): bool
    {
        return $locale === Locales::default();
    }

    /**
     * Table column with the English text, searchable inside the JSON value.
     */
    public static function column(string $field, string $label): TextColumn
    {
        return TextColumn::make($field)
            ->label($label)
            ->state(fn (HasTranslatableFields $record): ?string => $record->translation($field, Locales::default()))
            ->searchable(query: fn (Builder $query, string $search): Builder => $query->where($field, 'like', '%'.$search.'%'));
    }

    /**
     * One badge per other language: "Lengkap", "Perlu dicek" (drafts) or
     * "Belum diisi" (empty fields), with the fields as tooltip.
     *
     * @param  array<string, string>  $labels  field => admin label
     * @return list<TextColumn>
     */
    public static function statusColumns(array $labels): array
    {
        $columns = [];

        foreach (array_diff(Locales::all(), [Locales::default()]) as $locale) {
            $columns[] = TextColumn::make('translation_'.$locale)
                ->label('Versi '.strtoupper($locale))
                ->state(fn (HasTranslatableFields $record): string => self::status($record, $locale))
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    self::COMPLETE => 'success',
                    self::NEEDS_REVIEW => 'warning',
                    default => 'danger',
                })
                ->tooltip(fn (HasTranslatableFields $record): ?string => self::summary($record, $locale, $labels));
        }

        return $columns;
    }

    public static function status(HasTranslatableFields $record, string $locale): string
    {
        return match (true) {
            $record->missingTranslations($locale) !== [] => self::MISSING,
            $record->draftTranslations($locale) !== [] => self::NEEDS_REVIEW,
            default => self::COMPLETE,
        };
    }

    /**
     * "Kosong: Judul. Draf: Deskripsi singkat" for the fields that are not done, or null.
     *
     * @param  array<string, string>  $labels
     */
    public static function summary(HasTranslatableFields $record, string $locale, array $labels): ?string
    {
        $name = fn (string $field): string => $labels[$field] ?? $field;
        $parts = [];

        if ($missing = $record->missingTranslations($locale)) {
            $parts[] = 'Kosong: '.implode(', ', array_map($name, $missing));
        }

        if ($drafts = $record->draftTranslations($locale)) {
            $parts[] = 'Draf perlu dicek: '.implode(', ', array_map($name, $drafts));
        }

        return $parts === [] ? null : implode('. ', $parts);
    }

    /**
     * "title.id" -> "title"; null when the name does not end with the locale
     * (e.g. ui_texts.id.key, which has no review step).
     */
    private static function baseName(string $name, string $locale): ?string
    {
        $suffix = '.'.$locale;

        return str_ends_with($name, $suffix) ? substr($name, 0, -strlen($suffix)) : null;
    }

    private static function withReview(Field $field, string $base, string $locale): Field|Group
    {
        $default = Locales::default();
        $source = $locale === $default ? (array_values(array_diff(Locales::all(), [$default]))[0] ?? $default) : $default;

        $field->hintAction(self::draftAction($base, $source, $locale));

        if ($locale === $default) {
            return $field;
        }

        $statusPath = "translation_status.{$locale}.{$base}";

        return Group::make([
            $field->live(onBlur: true),
            // Hidden while the translation is empty ("Kosong"); a new text starts as a draft.
            ToggleButtons::make($statusPath)
                ->hiddenLabel()
                ->options([
                    HasTranslatableFields::STATE_DRAFT => 'Draf - perlu dicek',
                    HasTranslatableFields::STATE_REVIEWED => 'Sudah dicek',
                ])
                ->colors([
                    HasTranslatableFields::STATE_DRAFT => 'warning',
                    HasTranslatableFields::STATE_REVIEWED => 'success',
                ])
                ->inline()
                ->grouped()
                ->formatStateUsing(fn (mixed $state): string => $state === HasTranslatableFields::STATE_REVIEWED ? $state : HasTranslatableFields::STATE_DRAFT)
                ->helperText('Versi '.strtoupper($locale).' tampil di situs hanya jika "Sudah dicek"; selain itu tampil versi '.strtoupper($default).'.')
                ->hidden(fn (Get $get): bool => blank($get("{$base}.{$locale}"))),
        ]);
    }

    private static function draftAction(string $base, string $from, string $to): Action
    {
        return Action::make('draftTranslation')
            ->label('Buat draf dari '.strtoupper($from))
            ->icon(Heroicon::OutlinedLanguage)
            ->action(function (Get $get, Set $set) use ($base, $from, $to): void {
                if (! Translator::enabled()) {
                    Notification::make()->warning()->title('Terjemahan otomatis belum aktif')
                        ->body('Isi TRANSLATION_DRIVER (deepl, google, atau anthropic) dan TRANSLATION_API_KEY di file .env, lalu muat ulang. Sementara itu terjemahan tetap bisa diketik manual.')
                        ->persistent()->send();

                    return;
                }

                $text = trim((string) $get("{$base}.{$from}"));

                if ($text === '') {
                    Notification::make()->warning()->title('Isi versi '.strtoupper($from).' terlebih dahulu.')->send();

                    return;
                }

                try {
                    $set("{$base}.{$to}", Translator::draft($text, $from, $to));
                } catch (TranslationFailed $exception) {
                    Notification::make()->danger()->title('Draf gagal dibuat')->body($exception->getMessage())->send();

                    return;
                }

                if ($to !== Locales::default()) {
                    $set("translation_status.{$to}.{$base}", HasTranslatableFields::STATE_DRAFT);
                }

                Notification::make()
                    ->success()
                    ->title('Draf terjemahan dibuat')
                    ->body('Periksa dan koreksi teksnya, tandai "Sudah dicek", lalu simpan. Draf belum tampil di situs.')
                    ->send();
            });
    }
}
