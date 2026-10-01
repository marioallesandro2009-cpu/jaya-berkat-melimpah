<?php

namespace App\Filament\Resources\MenuItems;

use App\Filament\Resources\MenuItems\Pages\ManageMenuItems;
use App\Filament\Support\HasTranslatableRecordTitle;
use App\Filament\Support\Translatable;
use App\Models\MenuItem;
use App\Models\PageSection;
use App\Support\FrontendData;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Navbar and footer links. Order is set by dragging rows (per menu).
 */
class MenuItemResource extends Resource
{
    use HasTranslatableRecordTitle;

    public const LABELS = ['label' => 'Nama menu'];

    /** Pages (and page anchors) a menu item can open. */
    public const PAGE_TARGETS = [
        'home' => 'Beranda',
        'company' => 'Perusahaan',
        'products' => 'Semua produk',
        'company#leadership' => 'Perusahaan: Kepemimpinan',
        'company#certifications' => 'Perusahaan: Sertifikasi',
        'news' => 'Berita (tampil otomatis hanya jika ada artikel terbit)',
    ];

    protected static ?string $model = MenuItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static string|UnitEnum|null $navigationGroup = 'Tampilan';

    protected static ?string $modelLabel = 'menu';

    protected static ?string $pluralModelLabel = 'Menu';

    protected static ?string $navigationLabel = 'Menu';

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Select::make('location')->label('Letak menu')->options(MenuItem::LOCATIONS)->required()->native(false)->default(MenuItem::HEADER),
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("label.{$locale}")
                ->label(Translatable::label('Nama menu', $locale))
                ->required(Translatable::isRequired($locale))
                ->maxLength(40)
                ->helperText($locale === 'en' ? 'Untuk tombol di navbar yang dikosongkan, dipakai "Label tombol CTA" dari Pengaturan Situs.' : null)),
            Select::make('type')->label('Tujuan menu')->options(MenuItem::TYPES)->required()->native(false)->live()->default(MenuItem::SECTION),
            Select::make('target')->label('Bagian beranda')->options(fn (): array => PageSection::anchorOptions())->required()->native(false)
                ->visible(fn (Get $get): bool => $get('type') === MenuItem::SECTION)->dehydratedWhenHidden(false),
            Select::make('target')->label('Halaman')->options(self::PAGE_TARGETS)->required()->native(false)
                ->visible(fn (Get $get): bool => $get('type') === MenuItem::PAGE)->dehydratedWhenHidden(false),
            TextInput::make('target')->label('Alamat (URL)')->rules(['url:http,https'])->required()->maxLength(255)->placeholder('https://')
                ->visible(fn (Get $get): bool => $get('type') === MenuItem::URL)->dehydratedWhenHidden(false),
            Toggle::make('new_tab')->label('Buka di tab baru')->visible(fn (Get $get): bool => $get('type') === MenuItem::URL),
            Toggle::make('is_button')->label('Tampilkan sebagai tombol (hanya navbar)')->helperText('Dipakai untuk tombol "Minta Penawaran".'),
            Toggle::make('is_active')->label('Tampilkan')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->afterReordering(fn () => FrontendData::flush())
            ->paginated(false)
            ->defaultGroup('location')
            ->filters([SelectFilter::make('location')->label('Letak')->options(MenuItem::LOCATIONS)])
            ->columns([
                TextColumn::make('location')->label('Letak')->badge()->formatStateUsing(fn (string $state): string => MenuItem::LOCATIONS[$state] ?? $state),
                Translatable::column('label', 'Nama menu')->placeholder('(label tombol CTA)'),
                TextColumn::make('type')->label('Tujuan')->formatStateUsing(fn (string $state): string => MenuItem::TYPES[$state] ?? $state),
                TextColumn::make('target')->label('Target')->limit(40),
                ...Translatable::statusColumns(self::LABELS),
                ToggleColumn::make('is_active')->label('Tampil'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageMenuItems::route('/')];
    }
}
