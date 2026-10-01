<?php

namespace App\Filament\Resources\Locations;

use App\Filament\Resources\Locations\Pages\ManageLocations;
use App\Filament\Support\Translatable;
use App\Models\Location;
use App\Models\SiteSetting;
use App\Support\FrontendData;
use App\Support\ImageConversions;
use App\Support\ImageUpload;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
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

class LocationResource extends Resource
{
    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = ['kind' => 'Jenis'];

    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Halaman Perusahaan';

    protected static ?string $modelLabel = 'lokasi';

    protected static ?string $pluralModelLabel = 'Lokasi';

    protected static ?string $navigationLabel = 'Lokasi';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            TextInput::make('name')->label('Nama lokasi')->required()->maxLength(120),
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("kind.{$locale}")
                ->label(Translatable::label('Jenis lokasi', $locale))

                ->maxLength(60)
                ->helperText('Mis. "Kantor pusat", "Fasilitas pengolahan", "Pelabuhan".')),
            Textarea::make('address')->label('Alamat')->rows(3)->maxLength(400),
            TextInput::make('maps_url')->label('Tautan peta')->url()->rules(['url:http,https'])->maxLength(500)->helperText('Tempel tautan dari Google Maps (Bagikan > Salin link).'),
            ImageUpload::limitDimensions(SpatieMediaLibraryFileUpload::make('photo'), '1600 x 1000 px')
                ->label('Foto')
                ->collection('photo')
                ->image()
                ->acceptedFileTypes(SiteSetting::IMAGE_MIMES)
                ->maxSize(5120)
                ->helperText('Ukuran ideal 1600 x 1000 px. JPG, PNG, atau WebP, maks. 5 MB dan sisi terpanjang maks. 4000 px.'),
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
                SpatieMediaLibraryImageColumn::make('photo')->label('Foto')->collection('photo')->imageHeight(56)->when(ImageConversions::enabled(), fn ($c) => $c->conversion('sm')),
                TextColumn::make('name')->label('Nama')->searchable()->weight('medium'),
                Translatable::column('kind', 'Jenis'),
                ...Translatable::statusColumns(self::LABELS),
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
        return ['index' => ManageLocations::route('/')];
    }
}
