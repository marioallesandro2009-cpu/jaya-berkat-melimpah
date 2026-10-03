<?php

namespace App\Filament\Resources\ProcessingMethods\Pages;

use App\Filament\Resources\ProcessingMethods\ProcessingMethodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageProcessingMethods extends ManageRecords
{
    protected static string $resource = ProcessingMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
