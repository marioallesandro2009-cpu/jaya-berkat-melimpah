<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        if ($record instanceof Product && $record->is_sample) {
            $record->forceFill(['is_sample' => false])->saveQuietly();
        }
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
