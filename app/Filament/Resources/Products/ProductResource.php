<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\ProductCategories\ProductCategoryResource;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Support\HasTranslatableRecordTitle;
use App\Filament\Support\Translatable;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SiteSetting;
use App\Support\FrontendData;
use App\Support\ImageConversions;
use App\Support\ImageUpload;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use UnitEnum;

class ProductResource extends Resource
{
    use HasTranslatableRecordTitle;

    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = [
        'name' => 'Nama',
        'description' => 'Deskripsi kartu',
        'image_alt' => 'Alt foto',
        'intro' => 'Pengantar halaman detail',
        'content' => 'Isi halaman detail',
        'seo_title' => 'Judul SEO',
        'seo_description' => 'Meta description',
    ];

    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Konten Beranda';

    protected static ?string $modelLabel = 'produk';

    protected static ?string $pluralModelLabel = 'Produk';

    protected static ?string $navigationLabel = 'Produk';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Tabs::make('product')->persistTabInQueryString()->columnSpanFull()->tabs([
                Tab::make('Kartu produk')->icon(Heroicon::OutlinedRectangleStack)->schema([
                    Select::make('category_id')
                        ->label('Kategori')
                        ->relationship('category', 'slug')
                        ->getOptionLabelFromRecordUsing(fn (ProductCategory $record): string => (string) $record->translate('name', 'en'))
                        ->searchable()
                        ->preload()
                        ->placeholder('Tanpa kategori')
                        ->createOptionForm(ProductCategoryResource::fields())
                        ->createOptionModalHeading('Kategori baru')
                        ->helperText('Mis. Air laut, Air payau, Air tawar. Bisa dibuat langsung dari sini (ikon +), atau kelola di menu Kategori Produk.'),
                    Translatable::fields(fn (string $locale): TextInput => TextInput::make("name.{$locale}")
                        ->label(Translatable::label('Nama produk', $locale))
                        ->required(Translatable::isRequired($locale))
                        ->maxLength(80)
                        ->helperText('Juga jadi pilihan "Produk yang diminati" di form penawaran.')),
                    Translatable::fields(fn (string $locale): Textarea => Textarea::make("description.{$locale}")
                        ->label(Translatable::label('Deskripsi singkat', $locale))
                        ->rows(3)
                        ->maxLength(400)),
                    ImageUpload::limitDimensions(SpatieMediaLibraryFileUpload::make('image'), '1600 x 900 px')
                        ->label('Foto utama')
                        ->collection('image')
                        ->image()
                        ->acceptedFileTypes(SiteSetting::IMAGE_MIMES)
                        ->maxSize(5120)
                        ->helperText('Ukuran ideal 1600 x 900 px. JPG, PNG, atau WebP, maks. 5 MB dan sisi terpanjang maks. 4000 px.'),
                    Translatable::fields(fn (string $locale): TextInput => TextInput::make("image_alt.{$locale}")
                        ->label(Translatable::label('Teks alternatif foto (alt)', $locale))
                        ->maxLength(160)),
                    Toggle::make('is_active')->label('Tampilkan di situs')->default(true),
                ]),
                Tab::make('Halaman detail')->icon(Heroicon::OutlinedDocumentText)->schema([
                    ToggleButtons::make('detail_status')
                        ->label('Halaman detail')
                        ->options(Product::STATUSES)
                        ->colors([Product::DRAFT => 'gray', Product::PUBLISHED => 'success'])
                        ->default(Product::DRAFT)
                        ->inline()
                        ->grouped()
                        ->helperText('Terbit: kartu produk di beranda mendapat tautan "Lihat detail" ke halaman ini.'),
                    TextInput::make('slug')
                        ->label('Alamat halaman (slug)')
                        ->maxLength(120)
                        ->alphaDash()
                        ->unique(ignoreRecord: true)
                        ->placeholder('otomatis dari nama (EN)')
                        ->helperText('Hasilnya /products/<slug> dan /id/produk/<slug>.'),
                    Translatable::fields(fn (string $locale): Textarea => Textarea::make("intro.{$locale}")
                        ->label(Translatable::label('Pengantar', $locale))
                        ->rows(3)
                        ->maxLength(600)
                        ->helperText($locale === 'en' ? 'Kalimat pembuka di bawah judul. Kosong = deskripsi singkat dipakai.' : null)),
                    Text::make('Spesifikasi: isi hanya yang benar-benar diketahui (mis. ukuran, bentuk olahan, kemasan). Baris yang kosong diabaikan.')->color('gray'),
                    Repeater::make('specs')
                        ->label('Spesifikasi')
                        ->defaultItems(0)
                        ->addActionLabel('Tambah spesifikasi')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['label']['en'] ?? null)
                        ->schema([
                            Translatable::pair(fn (string $locale): TextInput => TextInput::make("label.{$locale}")
                                ->label(Translatable::label('Nama spesifikasi', $locale))
                                ->maxLength(60)),
                            Translatable::pair(fn (string $locale): TextInput => TextInput::make("value.{$locale}")
                                ->label(Translatable::label('Nilai', $locale))
                                ->maxLength(160)),
                        ]),
                    Translatable::fields(fn (string $locale): RichEditor => RichEditor::make("content.{$locale}")
                        ->label(Translatable::label('Isi halaman', $locale))
                        ->columnSpanFull()),
                    ImageUpload::limitDimensions(SpatieMediaLibraryFileUpload::make('gallery'), '1600 x 1000 px')
                        ->label('Galeri foto')
                        ->collection('gallery')
                        ->multiple()
                        ->reorderable()
                        ->image()
                        ->acceptedFileTypes(SiteSetting::IMAGE_MIMES)
                        ->maxSize(5120)
                        ->maxFiles(12)
                        ->helperText('Maks. 12 foto, 5 MB tiap foto.'),
                ]),
                Tab::make('SEO')->icon(Heroicon::OutlinedMagnifyingGlass)->schema([
                    Translatable::fields(fn (string $locale): TextInput => TextInput::make("seo_title.{$locale}")
                        ->label(Translatable::label('Judul SEO', $locale))
                        ->maxLength(120)
                        ->helperText($locale === 'en' ? 'Kosong = "<nama produk> | <nama perusahaan>".' : null)),
                    Translatable::fields(fn (string $locale): Textarea => Textarea::make("seo_description.{$locale}")
                        ->label(Translatable::label('Meta description', $locale))
                        ->rows(3)
                        ->maxLength(320)
                        ->helperText($locale === 'en' ? 'Kosong = pengantar halaman dipakai.' : null)),
                ]),
            ]),
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
                Translatable::column('name', 'Nama'),
                TextColumn::make('category.name')->label('Kategori')->badge()->color('info')
                    ->state(fn (Product $record): ?string => $record->category?->translation('name', 'en'))
                    ->placeholder('Tanpa kategori'),
                TextColumn::make('detail_status')->label('Halaman detail')->badge()
                    ->formatStateUsing(fn (string $state): string => $state === Product::PUBLISHED ? 'Terbit' : 'Draf')
                    ->color(fn (string $state): string => $state === Product::PUBLISHED ? 'success' : 'gray'),
                ...Translatable::statusColumns(self::LABELS),
                ToggleColumn::make('is_active')->label('Tampil'),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->options(fn (): array => ProductCategory::query()->ordered()->get()->mapWithKeys(fn (ProductCategory $c): array => [$c->id => (string) $c->translation('name', 'en')])->all())
                    ->placeholder('Semua kategori'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([
                BulkAction::make('category')
                    ->label('Ubah kategori')
                    ->icon(Heroicon::OutlinedSquares2x2)
                    ->schema([
                        Select::make('category_id')
                            ->label('Kategori baru')
                            ->options(fn (): array => ProductCategory::query()->ordered()->get()->mapWithKeys(fn (ProductCategory $c): array => [$c->id => (string) $c->translation('name', 'en')])->all())
                            ->placeholder('Tanpa kategori'),
                    ])
                    ->action(fn (Collection $records, array $data) => $records->each->update(['category_id' => $data['category_id'] ?? null]))
                    ->deselectRecordsAfterCompletion(),
                DeleteBulkAction::make(),
            ])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
