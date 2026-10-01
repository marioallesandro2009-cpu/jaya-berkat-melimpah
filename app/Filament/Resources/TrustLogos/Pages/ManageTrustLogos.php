<?php

namespace App\Filament\Resources\TrustLogos\Pages;

use App\Filament\Resources\TrustLogos\TrustLogoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTrustLogos extends ManageRecords
{
    protected static string $resource = TrustLogoResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
