<?php

namespace App\Filament\Resources\ContactMessages\Pages;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property ContactMessage $record
 */
class ViewContactMessage extends ViewRecord
{
    protected static string $resource = ContactMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mark_read')
                ->label('Tandai dibaca')
                ->icon(Heroicon::OutlinedCheck)
                ->color('gray')
                ->visible(fn (): bool => $this->record->status === ContactMessage::NEW)
                ->action(function (): void {
                    $this->record->markRead();
                    $this->refreshFormData(['status']);
                }),
            Action::make('reply')
                ->label('Balas via email')
                ->icon(Heroicon::OutlinedEnvelope)
                ->url(fn (): string => 'mailto:'.$this->record->email.'?subject='.rawurlencode('Re: '.$this->record->name))
                ->color('primary'),
            DeleteAction::make(),
        ];
    }
}
