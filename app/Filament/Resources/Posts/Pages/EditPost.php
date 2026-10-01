<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use App\Support\Locales;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property Post $record
 */
class EditPost extends EditRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        $previews = array_map(fn (string $locale): Action => Action::make("preview_{$locale}")
            ->label('Halaman '.strtoupper($locale))
            ->url(fn (): string => $this->record->detailPath($locale))
            ->openUrlInNewTab(), Locales::all());

        return [
            ActionGroup::make($previews)
                ->label(fn (): string => $this->record->isPublished() ? 'Lihat halaman' : 'Pratinjau')
                ->icon(Heroicon::OutlinedEye)
                ->button()
                ->color('gray'),
            DeleteAction::make(),
        ];
    }
}
