<?php

namespace App\Filament\Resources\ContactMessages\Tables;

use App\Models\ContactMessage;
use App\Models\Product;
use App\Support\Locales;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ContactMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            // No per-cell links (the View page would otherwise make every cell a Tab stop): one
            // "Buka" action per row keeps keyboard navigation short.
            ->recordUrl(null)
            ->recordClasses(fn (ContactMessage $record): ?string => $record->status === ContactMessage::NEW ? 'font-semibold' : null)
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->weight('medium'),
                TextColumn::make('email')->label('Email')->searchable()->copyable(),
                TextColumn::make('message')
                    ->label('Pesan')
                    ->formatStateUsing(fn (?string $state): string => Str::limit(preg_replace('/\s+/', ' ', (string) $state) ?? (string) $state, 70))
                    ->searchable()
                    ->wrap(),
                TextColumn::make('product_title')->label('Produk diminati')->default('-')->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ContactMessage::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => $state === ContactMessage::NEW ? 'warning' : 'gray'),
                TextColumn::make('email_failed')
                    ->label('Email')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Email gagal terkirim' : 'Terkirim')
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success'),
                TextColumn::make('locale')->label('Bahasa')->formatStateUsing(fn (string $state): string => strtoupper($state))->toggleable(),
                TextColumn::make('created_at')->label('Tanggal')->dateTime('d M Y H:i', 'Asia/Jakarta')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(ContactMessage::STATUSES),
                SelectFilter::make('locale')
                    ->label('Bahasa asal')
                    ->options(array_combine(Locales::all(), array_map('strtoupper', Locales::all()))),
                SelectFilter::make('product_id')
                    ->label('Produk diminati')
                    ->options(fn (): array => Product::query()->ordered()->get()->mapWithKeys(fn (Product $product): array => [$product->id => (string) $product->translate('name')])->all()),
                SelectFilter::make('email_failed')
                    ->label('Email')
                    ->options([1 => 'Email gagal terkirim', 0 => 'Terkirim']),
                Filter::make('created_at')
                    ->label('Rentang tanggal')
                    ->schema([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([ViewAction::make()->label('Buka')])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_read')
                        ->label('Tandai dibaca')
                        ->icon('heroicon-o-check')
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                if ($record instanceof ContactMessage) {
                                    $record->markRead();
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
