<?php

namespace App\Filament\Resources\PageSections\Pages;

use App\Filament\Resources\PageSections\PageSectionResource;
use App\Models\PageSection;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Str;

class ManagePageSections extends ManageRecords
{
    protected static string $resource = PageSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah blok sendiri')
                ->mutateDataUsing(fn (array $data): array => [
                    ...$data,
                    'key' => 'custom-'.Str::lower(Str::random(6)),
                    'is_custom' => true,
                    'sort_order' => (int) PageSection::query()->max('sort_order') + 1,
                ]),
        ];
    }
}
