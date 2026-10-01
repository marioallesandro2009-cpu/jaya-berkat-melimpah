<?php

namespace App\Filament\Resources\Leaders;

use App\Filament\Resources\Leaders\Pages\ManageLeaders;
use App\Filament\Support\Sample;
use App\Filament\Support\Translatable;
use App\Models\Leader;
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
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class LeaderResource extends Resource
{
    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = ['role' => 'Jabatan'];

    protected static ?string $model = Leader::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Halaman Perusahaan';

    protected static ?string $modelLabel = 'pimpinan';

    protected static ?string $pluralModelLabel = 'Kepemimpinan';

    protected static ?string $navigationLabel = 'Kepemimpinan';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Sample::notice(),
            TextInput::make('name')->label('Nama')->required()->maxLength(120),
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("role.{$locale}")
                ->label(Translatable::label('Jabatan', $locale))
                ->required(Translatable::isRequired($locale))
                ->maxLength(100)),
            ImageUpload::limitDimensions(SpatieMediaLibraryFileUpload::make('photo'), '900 x 1200 px')
                ->label('Foto')
                ->collection('photo')
                ->image()
                ->acceptedFileTypes(SiteSetting::IMAGE_MIMES)
                ->maxSize(5120)
                ->helperText('Ukuran ideal 900 x 1200 px. JPG, PNG, atau WebP, maks. 5 MB dan sisi terpanjang maks. 4000 px.'),
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
                Sample::column(),
                SpatieMediaLibraryImageColumn::make('photo')->label('Foto')->collection('photo')->imageHeight(56)->when(ImageConversions::enabled(), fn ($c) => $c->conversion('sm')),
                TextColumn::make('name')->label('Nama')->searchable()->weight('medium'),
                Translatable::column('role', 'Jabatan'),
                ...Translatable::statusColumns(self::LABELS),
                ToggleColumn::make('is_active')->label('Tampil'),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(fn (Model $record) => $record->getAttribute('is_sample') ? $record->forceFill(['is_sample' => false])->saveQuietly() : null),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageLeaders::route('/')];
    }
}
