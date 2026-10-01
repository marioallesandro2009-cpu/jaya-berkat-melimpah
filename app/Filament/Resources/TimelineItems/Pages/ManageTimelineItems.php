<?php

namespace App\Filament\Resources\TimelineItems\Pages;

use App\Filament\Resources\TimelineItems\TimelineItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTimelineItems extends ManageRecords
{
    protected static string $resource = TimelineItemResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
