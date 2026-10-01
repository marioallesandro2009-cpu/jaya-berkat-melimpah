<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Filament\Resources\Posts\Schemas\PostForm;
use App\Filament\Support\Translatable;
use App\Models\Post;
use App\Support\Locales;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                Translatable::column('title', 'Judul')->weight('medium'),
                Translatable::column('excerpt', 'Ringkasan')->limit(60)->color('gray'),
                ...Translatable::statusColumns(PostForm::LABELS),
                ...array_map(fn (string $locale): TextColumn => TextColumn::make("seo_score_{$locale}")
                    ->label('SEO '.strtoupper($locale))
                    ->state(fn (Post $record): int => $record->seoScore($locale))
                    ->badge()
                    ->suffix('/100')
                    ->color(fn (int $state): string => $state >= 80 ? 'success' : ($state >= 50 ? 'warning' : 'danger'))
                    ->tooltip('Skor SEO (buka artikel untuk detail & saran)'), Locales::all()),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): string => $state === Post::PUBLISHED ? 'Terbit' : 'Draf')
                    ->badge()
                    ->color(fn (string $state): string => $state === Post::PUBLISHED ? 'success' : 'gray'),
                TextColumn::make('published_at')
                    ->label('Tanggal terbit')
                    ->dateTime('d M Y')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Aktif'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
