<?php

namespace App\Filament\Resources\Species;

use App\Filament\Resources\Species\Pages\ManageSpecies;
use App\Filament\Support\HasTranslatableRecordTitle;
use App\Filament\Support\Sample;
use App\Filament\Support\Translatable;
use App\Models\Cut;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Species;
use App\Support\FrontendData;
use App\Support\ImageUpload;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SpeciesResource extends Resource
{
    use HasTranslatableRecordTitle;

    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = ['common_name' => 'Nama umum', 'short_description' => 'Deskripsi singkat', 'meta_title' => 'Judul SEO', 'meta_description' => 'Meta description'];

    protected static ?string $model = Species::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Katalog Produk';

    protected static ?string $modelLabel = 'spesies';

    protected static ?string $pluralModelLabel = 'Spesies Ikan';

    protected static ?string $navigationLabel = 'Spesies Ikan';

    protected static ?string $recordTitleAttribute = 'common_name';

    protected static ?int $navigationSort = 3;

    /**
     * @return array<int, string>
     */
    public static function categoryOptions(): array
    {
        return ProductCategory::query()->ordered()->get()->mapWithKeys(fn (ProductCategory $c): array => [$c->id => (string) $c->translation('name', 'en')])->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Sample::notice(),
            Section::make('Ikan')->columns(2)->schema([
                Translatable::fields(fn (string $locale): TextInput => TextInput::make("common_name.{$locale}")
                    ->label(Translatable::label('Nama umum', $locale))
                    ->required(Translatable::isRequired($locale))
                    ->maxLength(100)),
                Select::make('category_id')->label('Kategori')->options(fn (): array => self::categoryOptions())->searchable()->placeholder('Tanpa kategori'),
                TextInput::make('slug')->label('Slug')->maxLength(100)->alphaDash()->unique(ignoreRecord: true)->placeholder('otomatis dari nama (EN)'),
                TextInput::make('scientific_name')->label('Nama ilmiah')->maxLength(120)->helperText('Isi hanya nama ilmiah yang benar.'),
                TextInput::make('japanese_name')->label('Nama Jepang')->maxLength(120),
                TextInput::make('origin')->label('Wilayah / asal spesies')->maxLength(255),
            ]),
            Translatable::fields(fn (string $locale): Textarea => Textarea::make("short_description.{$locale}")
                ->label(Translatable::label('Deskripsi singkat', $locale))
                ->rows(3)
                ->maxLength(400)),
            Textarea::make('habitat')->label('Habitat')->rows(2)->maxLength(500),
            Section::make('Sashimi dan keberlanjutan')->columns(2)->schema([
                Toggle::make('is_sashimi_suitable')->label('Spesies ini umum dipakai untuk sashimi')->helperText('Tidak berarti perusahaan menjualnya: itu diatur per produk (grade) dan per potongan di bawah.'),
                Select::make('sashimi_grade')->label('Tingkat umum spesies')->options(Product::GRADES)->placeholder('-'),
                Textarea::make('sustainability_info')->label('Info keberlanjutan')->rows(3)->maxLength(1000)->columnSpanFull()->helperText('Kosongkan bila belum ada dokumen pendukung. Jangan menulis klaim yang tidak bisa dibuktikan.'),
            ]),
            Repeater::make('cutLinks')
                ->label('Potongan yang tersedia untuk spesies ini')
                ->relationship()
                ->orderColumn('sort_order')
                ->reorderable()
                ->collapsible()
                ->defaultItems(0)
                ->addActionLabel('Tambah potongan')
                ->itemLabel(fn (array $state): ?string => ($state['cut_id'] ?? null) ? (string) Cut::query()->whereKey($state['cut_id'])->first()?->translation('name', 'en') : null)
                ->columns(2)
                ->schema([
                    Select::make('cut_id')->label('Potongan')->options(fn (): array => Cut::query()->ordered()->get()->mapWithKeys(fn (Cut $c): array => [$c->id => (string) $c->translation('name', 'en')])->all())->required()->searchable()->distinct()->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                    Toggle::make('available_as_product')->label('Dijual sebagai produk')->helperText('Mati = bagian ikan ini ada, tetapi bukan produk yang dijual.'),
                ]),
            ImageUpload::limitDimensions(SpatieMediaLibraryFileUpload::make('image'), '1600 x 1000 px')
                ->label('Foto')
                ->collection('image')
                ->image()
                ->maxSize(5120)
                ->helperText('JPG, PNG, atau WebP, maks. 5 MB.'),
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("meta_title.{$locale}")
                ->label(Translatable::label('Judul SEO', $locale))
                ->maxLength(120)),
            Translatable::fields(fn (string $locale): Textarea => Textarea::make("meta_description.{$locale}")
                ->label(Translatable::label('Meta description', $locale))
                ->rows(2)
                ->maxLength(320)),
            Toggle::make('is_active')->label('Tampilkan di situs')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['category', 'media'])->withCount('products'))
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->afterReordering(fn () => FrontendData::flush())
            ->paginated(false)
            ->columns([
                Sample::column(),
                SpatieMediaLibraryImageColumn::make('image')->label('Foto')->collection('image')->imageHeight(44),
                Translatable::column('common_name', 'Nama umum')->weight('medium'),
                TextColumn::make('scientific_name')->label('Nama ilmiah')->fontFamily('serif')->color('gray'),
                TextColumn::make('category')->label('Kategori')->badge()->color('info')->state(fn (Species $record): ?string => $record->category?->translation('name', 'en')),
                TextColumn::make('products_count')->label('Produk')->badge()->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
                ...Translatable::statusColumns(self::LABELS),
                ToggleColumn::make('is_active')->label('Tampil'),
            ])
            ->filters([
                SelectFilter::make('category_id')->label('Kategori')->options(fn (): array => self::categoryOptions()),
            ])
            ->recordActions([
                EditAction::make()->after(fn (Model $record) => $record->getAttribute('is_sample') ? $record->forceFill(['is_sample' => false])->saveQuietly() : null),
                DeleteAction::make()->modalDescription('Produk yang memakai spesies ini tidak ikut terhapus, hanya menjadi tanpa spesies.'),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSpecies::route('/')];
    }
}
