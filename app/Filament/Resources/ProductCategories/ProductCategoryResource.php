<?php

namespace App\Filament\Resources\ProductCategories;

use App\Filament\Resources\ProductCategories\Pages\ManageProductCategories;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\AutoTranslate;
use App\Filament\Support\HasTranslatableRecordTitle;
use App\Filament\Support\Translatable;
use App\Models\ProductCategory;
use App\Support\FrontendData;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

class ProductCategoryResource extends Resource
{
    use HasTranslatableRecordTitle;

    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = ['name' => 'Nama', 'description' => 'Deskripsi'];

    /** Quick colour choices; any hex colour is accepted. */
    public const PALETTE = ['#7CC4E4', '#2F9E8F', '#C97B4A', '#D9B26F', '#8A7FD6', '#E26D6D'];

    protected static ?string $model = ProductCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Katalog Produk';

    protected static ?string $modelLabel = 'kategori produk';

    protected static ?string $pluralModelLabel = 'Kategori Produk';

    protected static ?string $navigationLabel = 'Kategori Produk';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    /**
     * @return list<Component>
     */
    public static function fields(): array
    {
        return [
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("name.{$locale}")
                ->label(Translatable::label('Nama kategori', $locale))
                ->required(Translatable::isRequired($locale))
                ->maxLength(60)
                ->helperText($locale === 'en' ? 'Contoh: Marine, Brackish water, Freshwater.' : null)),
            TextInput::make('slug')
                ->label('Alamat filter (slug)')
                ->maxLength(80)
                ->alphaDash()
                ->unique(ignoreRecord: true)
                ->placeholder('otomatis dari nama (EN)')
                ->helperText('Dipakai di tautan filter: /products?category=<slug>.'),
            Translatable::fields(fn (string $locale): Textarea => Textarea::make("description.{$locale}")
                ->label(Translatable::label('Deskripsi singkat', $locale))
                ->rows(2)
                ->maxLength(240)
                ->helperText($locale === 'en' ? 'Opsional. Tampil di bawah filter kategori di halaman Semua Produk.' : null)),
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("meta_title.{$locale}")
                ->label(Translatable::label('Judul SEO', $locale))
                ->maxLength(120)),
            Translatable::fields(fn (string $locale): Textarea => Textarea::make("meta_description.{$locale}")
                ->label(Translatable::label('Meta description', $locale))
                ->rows(2)
                ->maxLength(320)),
            SpatieMediaLibraryFileUpload::make('image')->label('Foto kategori')->collection('image')->image()->maxSize(5120)->helperText('Opsional. JPG, PNG, atau WebP, maks. 5 MB.'),
            ColorPicker::make('color')
                ->label('Warna penanda')
                ->default(ProductCategory::DEFAULT_COLOR)
                ->helperText('Titik warna di filter kategori. Pilihan cepat: '.implode(', ', self::PALETTE).'.'),
            Toggle::make('is_active')->label('Tampilkan di situs')->default(true),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components(self::fields());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount('products'))
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->afterReordering(fn () => FrontendData::flush())
            ->paginated(false)
            ->emptyStateHeading('Belum ada kategori')
            ->emptyStateDescription('Tambah kategori seperti Air laut, Air payau, atau Air tawar untuk mengelompokkan produk.')
            ->emptyStateIcon(Heroicon::OutlinedSquares2x2)
            ->columns([
                ColorColumn::make('color')->label(''),
                Translatable::column('name', 'Nama')->weight('medium'),
                TextColumn::make('slug')->label('Slug')->color('gray')->copyable(),
                TextColumn::make('products_count')
                    ->label('Produk')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'info' : 'gray')
                    ->sortable()
                    ->url(fn (ProductCategory $record): string => ProductResource::getUrl('index', ['filters' => ['category_id' => ['value' => $record->getKey()]]]))
                    ->tooltip('Lihat produk di kategori ini'),
                ...Translatable::statusColumns(self::LABELS),
                ToggleColumn::make('is_active')->label('Tampil'),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Tampil di situs'),
                TernaryFilter::make('empty')->label('Kategori kosong')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->doesntHave('products'),
                        false: fn (Builder $query): Builder => $query->has('products'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(fn (ProductCategory $record): string => $record->products()->count() > 0
                        ? "Kategori ini dipakai {$record->products()->count()} produk. Produknya tidak ikut terhapus, hanya menjadi tanpa kategori."
                        : 'Kategori ini belum dipakai produk apa pun.'),
                Action::make('products')
                    ->label('Lihat produk')
                    ->icon(Heroicon::OutlinedShoppingBag)
                    ->color('gray')
                    ->url(fn (ProductCategory $record): string => ProductResource::getUrl('index', ['filters' => ['category_id' => ['value' => $record->getKey()]]])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('show')->label('Tampilkan')->icon(Heroicon::OutlinedEye)
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))->deselectRecordsAfterCompletion(),
                    BulkAction::make('hide')->label('Sembunyikan')->icon(Heroicon::OutlinedEyeSlash)
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false]))->deselectRecordsAfterCompletion(),
                    AutoTranslate::bulkAction(),
                    DeleteBulkAction::make()->modalDescription('Produk di kategori yang dihapus tidak ikut terhapus, hanya menjadi tanpa kategori.'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageProductCategories::route('/')];
    }
}
