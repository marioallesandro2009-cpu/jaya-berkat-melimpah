<?php

namespace App\Filament\Resources\TrustLogos;

use App\Filament\Resources\TrustLogos\Pages\ManageTrustLogos;
use App\Filament\Support\Translatable;
use App\Models\SiteSetting;
use App\Models\TrustLogo;
use App\Support\FrontendData;
use App\Support\ImageConversions;
use App\Support\ImageUpload;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class TrustLogoResource extends Resource
{
    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = [];

    protected static ?string $model = TrustLogo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Konten Beranda';

    protected static ?string $modelLabel = 'logo partner';

    protected static ?string $pluralModelLabel = 'Logo Partner';

    protected static ?string $navigationLabel = 'Logo Partner';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('name')->label('Nama partner')->required()->maxLength(120),
            TextInput::make('url')->label('Situs partner (opsional)')->url()->rules(['url:http,https'])->maxLength(255),
            ImageUpload::limitDimensions(SpatieMediaLibraryFileUpload::make('logo'), 'lebar 400 px, latar transparan')
                ->label('Foto')
                ->collection('logo')
                ->image()
                ->acceptedFileTypes(SiteSetting::LOGO_MIMES)
                ->maxSize(5120)
                ->helperText('Ukuran ideal lebar 400 px, latar transparan. JPG, PNG, atau WebP, maks. 5 MB dan sisi terpanjang maks. 4000 px.'),
            Toggle::make('is_active')->label('Tampilkan di situs')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->afterReordering(fn () => FrontendData::flush())
            ->paginated(false)
            ->columns([
                SpatieMediaLibraryImageColumn::make('logo')->label('Foto')->collection('logo')->imageHeight(56)->when(ImageConversions::enabled(), fn ($c) => $c->conversion('logo_webp')),
                TextColumn::make('name')->label('Nama')->searchable()->weight('medium'),
                ToggleColumn::make('is_active')->label('Tampil'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageTrustLogos::route('/')];
    }
}
