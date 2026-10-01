<?php

namespace App\Filament\Resources\PageSections;

use App\Filament\Resources\PageSections\Pages\ManagePageSections;
use App\Filament\Support\Translatable;
use App\Models\PageSection;
use App\Models\SiteSetting;
use App\Support\FrontendData;
use App\Support\ImageConversions;
use App\Support\ImageUpload;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * The page builder: the copy of every block of the home and company pages. Drag the rows to reorder
 * the blocks that are not fixed (hero, contact and page header/closing stay in place), switch
 * blocks off, or add your own blocks with a layout and background.
 */
class PageSectionResource extends Resource
{
    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = [
        'eyebrow' => 'Label kecil',
        'title' => 'Judul',
        'body' => 'Teks',
        'body_extra' => 'Teks tambahan',
        'cta_label' => 'Label tombol',
        'image_alt' => 'Alt foto',
    ];

    protected static ?string $model = PageSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Konten Beranda';

    protected static ?string $modelLabel = 'blok halaman';

    protected static ?string $pluralModelLabel = 'Blok Halaman';

    protected static ?string $navigationLabel = 'Blok Halaman';

    protected static ?int $navigationSort = 1;

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return $record instanceof PageSection ? self::blockName($record) : null;
    }

    public static function blockName(PageSection $record): string
    {
        return $record->is_custom
            ? 'Blok sendiri: '.($record->translation('title', 'en') ?? $record->key)
            : (PageSection::KEYS[$record->key] ?? $record->key);
    }

    /**
     * Fixed in place: it is not part of the reorderable flow of its page.
     */
    public static function isFixed(PageSection $record): bool
    {
        return ! $record->is_custom && ! in_array($record->key, PageSection::FLOW[$record->page] ?? [], true);
    }

    public static function form(Schema $schema): Schema
    {
        $custom = fn (?PageSection $record): bool => $record === null || $record->is_custom;

        return $schema->columns(1)->components([
            Text::make(fn (?PageSection $record): string => $record === null
                ? 'Blok baru ditampilkan di halaman yang dipilih, di urutan paling bawah dalam alurnya. Seret barisnya di daftar untuk memindahkannya.'
                : 'Blok: '.self::blockName($record).'. Kolom yang tidak dipakai blok ini diabaikan; kosongkan saja.')
                ->color('gray'),
            Select::make('page')->label('Halaman')->options(PageSection::PAGES)->required()->native(false)->default(PageSection::HOME)->visible($custom),
            Select::make('layout')->label('Tata letak')->options(PageSection::LAYOUTS)->required()->native(false)->default('image_right')->visible($custom),
            Select::make('background')->label('Warna latar')->options(PageSection::BACKGROUNDS)->required()->native(false)->default('white')->visible($custom)
                ->helperText('Tidak dipakai oleh tata letak "Foto penuh".'),
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("eyebrow.{$locale}")
                ->label(Translatable::label(PageSectionResource::LABELS['eyebrow'], $locale))
                ->maxLength(80)),
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("title.{$locale}")
                ->label(Translatable::label(PageSectionResource::LABELS['title'], $locale))
                ->required(Translatable::isRequired($locale))
                ->maxLength(200)),
            Translatable::fields(fn (string $locale): Textarea => Textarea::make("body.{$locale}")
                ->label(Translatable::label(PageSectionResource::LABELS['body'], $locale))
                ->rows(5)
                ->maxLength(1500)
                ->helperText('Baris kosong = paragraf baru.')),
            Translatable::fields(fn (string $locale): Textarea => Textarea::make("body_extra.{$locale}")
                ->label(Translatable::label(PageSectionResource::LABELS['body_extra'], $locale))
                ->rows(3)
                ->maxLength(1000)),
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("cta_label.{$locale}")
                ->label(Translatable::label(PageSectionResource::LABELS['cta_label'], $locale))
                ->maxLength(60)),
            TextInput::make('cta_url')
                ->label('Tautan tombol')
                ->maxLength(255)
                ->rules(['regex:~^(#|/(?!/)|https?://|mailto:|tel:)\S*$~i'])
                ->validationMessages(['regex' => 'Gunakan "#bagian", "/halaman", alamat http(s), mailto: atau tel:.'])
                ->helperText('Mis. "#contact" atau alamat lengkap. Kosong = tombol memakai tautan bawaan blok.'),
            ImageUpload::limitDimensions(SpatieMediaLibraryFileUpload::make('image'), '1920 x 1200 px')
                ->label('Foto')
                ->collection('image')
                ->image()
                ->acceptedFileTypes(SiteSetting::IMAGE_MIMES)
                ->maxSize(5120)
                ->helperText('Hanya dipakai blok yang punya foto. JPG, PNG, atau WebP, maks. 5 MB dan sisi terpanjang maks. 4000 px.'),
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("image_alt.{$locale}")
                ->label(Translatable::label(PageSectionResource::LABELS['image_alt'], $locale))
                ->maxLength(160)),
            Toggle::make('is_active')->label('Tampilkan blok ini')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->afterReordering(fn () => FrontendData::flush())
            ->paginated(false)
            ->filters([SelectFilter::make('page')->label('Halaman')->options(PageSection::PAGES)])
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')->label('Foto')->collection('image')->imageHeight(48)->when(ImageConversions::enabled(), fn ($c) => $c->conversion('sm')),
                TextColumn::make('page')->label('Halaman')->badge()->formatStateUsing(fn (string $state): string => PageSection::PAGES[$state] ?? $state),
                TextColumn::make('key')->label('Blok')->formatStateUsing(fn (string $state, PageSection $record): string => self::blockName($record))->weight('medium')->searchable()->description(fn (PageSection $record): ?string => self::isFixed($record) ? 'Posisi tetap' : null),
                Translatable::column('title', 'Judul')->limit(50),
                ...Translatable::statusColumns(self::LABELS),
                ToggleColumn::make('is_active')->label('Tampil'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn (PageSection $record): bool => $record->is_custom),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePageSections::route('/')];
    }
}
