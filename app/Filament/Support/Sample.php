<?php

namespace App\Filament\Support;

use Filament\Schemas\Components\Callout;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

/**
 * "Contoh" marker for seeded DUMMY rows (is_sample): a badge in the table and a
 * notice on the form. Saving the record from the admin clears it
 * (ClearsSampleFlag on the edit page).
 */
final class Sample
{
    public const LABEL = 'Contoh';

    public static function column(): TextColumn
    {
        return TextColumn::make('is_sample')
            ->label('')
            ->state(fn (Model $record): ?string => $record->getAttribute('is_sample') ? self::LABEL : null)
            ->badge()
            ->color('warning')
            ->tooltip('Data contoh (DUMMY) dari seeder, belum diubah di admin.');
    }

    public static function notice(): Callout
    {
        return Callout::make('Data contoh (DUMMY)')
            ->description('Isi ini dibuat seeder sebagai contoh dan belum dikonfirmasi klien. Penanda "Contoh" hilang setelah Anda menyimpan dari sini.')
            ->warning()
            ->visible(fn (?Model $record): bool => (bool) $record?->getAttribute('is_sample'))
            ->columnSpanFull();
    }
}
