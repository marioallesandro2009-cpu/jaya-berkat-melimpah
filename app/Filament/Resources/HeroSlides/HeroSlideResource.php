<?php

namespace App\Filament\Resources\HeroSlides;

use App\Filament\Resources\HeroSlides\Pages\ManageHeroSlides;
use App\Filament\Support\HasTranslatableRecordTitle;
use App\Filament\Support\Translatable;
use App\Models\HeroSlide;
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
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class HeroSlideResource extends Resource
{
    use HasTranslatableRecordTitle;

    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = ['label' => 'Nama', 'image_alt' => 'Alt foto'];

    protected static ?string $model = HeroSlide::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Konten Beranda';

    protected static ?string $modelLabel = 'slide hero';

    protected static ?string $pluralModelLabel = 'Slide Hero';

    protected static ?string $navigationLabel = 'Slide Hero';

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("label.{$locale}")
                ->label(Translatable::label('Nama slide (untuk admin)', $locale))
                ->required(Translatable::isRequired($locale))
                ->maxLength(60)
                ->helperText('Tidak tampil di situs.')),
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("image_alt.{$locale}")
                ->label(Translatable::label('Teks alternatif foto (alt)', $locale))

                ->maxLength(160)
                ->helperText('Deskripsi singkat isi foto untuk pembaca layar dan SEO.')),
            ImageUpload::limitDimensions(SpatieMediaLibraryFileUpload::make('image'), '1920 x 1200 px')
                ->label('Foto')
                ->collection('image')
                ->image()
                ->acceptedFileTypes(SiteSetting::IMAGE_MIMES)
                ->maxSize(5120)
                ->helperText('Ukuran ideal 1920 x 1200 px. JPG, PNG, atau WebP, maks. 5 MB dan sisi terpanjang maks. 4000 px.'),
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
                Translatable::column('label', 'Nama'),
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
        return ['index' => ManageHeroSlides::route('/')];
    }
}
