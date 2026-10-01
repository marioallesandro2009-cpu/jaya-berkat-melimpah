<?php

namespace App\Filament\Resources\ChainSteps;

use App\Filament\Resources\ChainSteps\Pages\ManageChainSteps;
use App\Filament\Support\HasTranslatableRecordTitle;
use App\Filament\Support\Translatable;
use App\Models\ChainStep;
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
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class ChainStepResource extends Resource
{
    use HasTranslatableRecordTitle;

    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = ['title' => 'Judul', 'body' => 'Penjelasan'];

    protected static ?string $model = ChainStep::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|UnitEnum|null $navigationGroup = 'Konten Beranda';

    protected static ?string $modelLabel = 'tahap perjalanan';

    protected static ?string $pluralModelLabel = 'Perjalanan (rantai nilai)';

    protected static ?string $navigationLabel = 'Perjalanan (rantai nilai)';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("title.{$locale}")
                ->label(Translatable::label('Judul tahap', $locale))
                ->required(Translatable::isRequired($locale))
                ->maxLength(60)),
            Translatable::fields(fn (string $locale): Textarea => Textarea::make("body.{$locale}")
                ->label(Translatable::label('Penjelasan', $locale))

                ->rows(4)
                ->maxLength(500)),
            ImageUpload::limitDimensions(SpatieMediaLibraryFileUpload::make('image'), '1600 x 1000 px')
                ->label('Foto')
                ->collection('image')
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
                SpatieMediaLibraryImageColumn::make('image')->label('Foto')->collection('image')->imageHeight(56)->when(ImageConversions::enabled(), fn ($c) => $c->conversion('sm')),
                Translatable::column('title', 'Judul'),
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
        return ['index' => ManageChainSteps::route('/')];
    }
}
