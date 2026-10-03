<?php

namespace App\Filament\Support;

use App\Models\Contracts\HasTranslatableFields;
use App\Support\Locales;
use App\Support\Translation\TranslationFailed;
use App\Support\Translation\Translator;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * "Buat draf terjemahan" for many records at once (table bulk action): every text that has an English
 * version but no Indonesian one is translated by the configured service (App\Support\Translation\Translator)
 * and saved as a DRAFT. Drafts are not shown on the site until someone marks them "Sudah dicek".
 *
 * Never overwritten: Indonesian texts that already exist. Skipped: fields a model lists in
 * AUTO_TRANSLATE_SKIP (product names stay English) and texts that contain markup (rich text is
 * translated one field at a time with the field's own button, where the result can be read first).
 * Shared hosting has short request limits, so a run stops after about 20 seconds and says how many
 * records are left: select them again and repeat.
 */
final class AutoTranslate
{
    /** Seconds one run may use before it stops and reports what is left. */
    private const BUDGET = 20;

    public static function bulkAction(): BulkAction
    {
        return BulkAction::make('draftMissing')
            ->label('Buat draf terjemahan (ID)')
            ->icon(Heroicon::OutlinedLanguage)
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Buat draf terjemahan Indonesia?')
            ->modalDescription('Teks EN yang versi Indonesianya masih kosong diterjemahkan otomatis menjadi draf. Teks yang sudah ada tidak diubah, dan draf tidak tampil di situs sampai Anda menandainya "Sudah dicek". Teks berformat (isi artikel/halaman) dilewati.')
            ->modalSubmitActionLabel('Buat draf')
            ->action(function (Collection $records): void {
                if (! Translator::enabled()) {
                    Notification::make()->warning()->title('Terjemahan otomatis belum aktif')
                        ->body('Isi TRANSLATION_DRIVER (deepl, google, atau anthropic) dan TRANSLATION_API_KEY di file .env, lalu muat ulang.')
                        ->persistent()->send();

                    return;
                }

                $result = self::run($records, Locales::default() === 'id' ? 'en' : 'id');

                Notification::make()
                    ->{$result['failed'] === null && $result['left'] === 0 ? 'success' : 'warning'}()
                    ->title($result['texts'].' teks diterjemahkan (draf) di '.$result['records'].' data')
                    ->body(implode(' ', array_filter([
                        $result['skipped'] > 0 ? $result['skipped'].' teks berformat dilewati (terjemahkan lewat tombol di kolomnya).' : null,
                        $result['left'] > 0 ? 'Waktu habis: '.$result['left'].' data belum diproses, pilih lalu jalankan lagi.' : null,
                        $result['failed'] !== null ? 'Berhenti karena: '.$result['failed'] : null,
                        'Periksa lalu tandai "Sudah dicek" agar tampil di situs.',
                    ])))
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    /**
     * @param  Collection<int, Model>  $records
     * @return array{texts: int, records: int, skipped: int, left: int, failed: string|null}
     */
    public static function run(Collection $records, string $to, ?float $deadline = null): array
    {
        $deadline ??= microtime(true) + self::BUDGET;
        $from = Locales::default();
        $result = ['texts' => 0, 'records' => 0, 'skipped' => 0, 'left' => 0, 'failed' => null];

        foreach ($records->values() as $index => $record) {
            if (! $record instanceof HasTranslatableFields) {
                continue;
            }

            if (microtime(true) >= $deadline) {
                $result['left'] = $records->count() - $index;

                break;
            }

            $skips = defined($record::class.'::AUTO_TRANSLATE_SKIP') ? (array) constant($record::class.'::AUTO_TRANSLATE_SKIP') : [];
            $done = 0;

            foreach ((array) constant($record::class.'::TRANSLATABLE') as $field) {
                $text = $record->translation($field, $from);

                if ($text === null || $record->translation($field, $to) !== null || in_array($field, $skips, true)) {
                    continue;
                }

                if ($text !== strip_tags($text)) {
                    $result['skipped']++;

                    continue;
                }

                try {
                    $draft = Translator::draft($text, $from, $to);
                } catch (TranslationFailed $exception) {
                    $result['failed'] = $exception->getMessage();
                    $result['left'] = $records->count() - $index;
                    break 2;
                }

                $values = (array) $record->getAttribute($field);
                $values[$to] = $draft;
                $status = (array) $record->getAttribute('translation_status');
                $status[$to][$field] = HasTranslatableFields::STATE_DRAFT;
                $record->setAttribute($field, $values);
                $record->setAttribute('translation_status', $status);
                $done++;
            }

            if ($done > 0) {
                $record->save();
                $result['texts'] += $done;
                $result['records']++;
            }
        }

        return $result;
    }
}
