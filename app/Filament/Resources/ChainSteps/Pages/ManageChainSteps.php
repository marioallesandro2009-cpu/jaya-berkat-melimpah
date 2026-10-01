<?php

namespace App\Filament\Resources\ChainSteps\Pages;

use App\Filament\Resources\ChainSteps\ChainStepResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageChainSteps extends ManageRecords
{
    protected static string $resource = ChainStepResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
