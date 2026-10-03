<?php

namespace App\Filament\Resources\Cuts;

use App\Filament\Resources\Cuts\Pages\ManageCuts;
use App\Filament\Support\HasTranslatableRecordTitle;
use App\Filament\Support\Translatable;
use App\Models\Cut;
use App\Support\FrontendData;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CutResource extends Resource
{
    use HasTranslatableRecordTitle;

    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = ['name' => 'Nama', 'description' => 'Deskripsi'];

    protected static ?string $model = Cut::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScissors;

    protected static string|UnitEnum|null $navigationGroup = 'Katalog Produk';

    protected static ?string $modelLabel = 'potongan';

    protected static ?string $pluralModelLabel = 'Potongan / Bagian Ikan';

    protected static ?string $navigationLabel = 'Potongan Ikan';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("name.{$locale}")
                ->label(Translatable::label('Nama potongan', $locale))
                ->required(Translatable::isRequired($locale))
                ->maxLength(80)
                ->helperText($locale === 'en' ? 'Contoh: Loin, Saku, Akami, Fillet. Potongan mana yang tersedia untuk tiap ikan diatur di menu Spesies Ikan.' : null)),
            TextInput::make('slug')->label('Slug')->maxLength(100)->alphaDash()->unique(ignoreRecord: true)->placeholder('otomatis dari nama (EN)'),
            TextInput::make('body_region')->label('Bagian tubuh')->maxLength(120)->helperText('Contoh: Back (loin), Belly, Whole fish.'),
            Translatable::fields(fn (string $locale): Textarea => Textarea::make("description.{$locale}")
                ->label(Translatable::label('Deskripsi', $locale))
                ->rows(2)
                ->maxLength(300)),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount('products'))
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->afterReordering(fn () => FrontendData::flush())
            ->paginated(false)
            ->columns([
                Translatable::column('name', 'Nama')->weight('medium'),
                TextColumn::make('body_region')->label('Bagian tubuh')->color('gray'),
                TextColumn::make('products_count')->label('Produk')->badge()->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
                ...Translatable::statusColumns(self::LABELS),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->modalDescription('Produk yang memakai potongan ini tidak ikut terhapus, hanya menjadi tanpa potongan.'),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCuts::route('/')];
    }
}
