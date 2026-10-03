<?php

namespace App\Filament\Resources\ProcessingMethods;

use App\Filament\Resources\ProcessingMethods\Pages\ManageProcessingMethods;
use App\Filament\Support\AutoTranslate;
use App\Filament\Support\HasTranslatableRecordTitle;
use App\Filament\Support\Translatable;
use App\Models\ProcessingMethod;
use App\Support\FrontendData;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProcessingMethodResource extends Resource
{
    use HasTranslatableRecordTitle;

    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = ['name' => 'Nama', 'description' => 'Deskripsi'];

    protected static ?string $model = ProcessingMethod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBeaker;

    protected static string|UnitEnum|null $navigationGroup = 'Katalog Produk';

    protected static ?string $modelLabel = 'metode pengolahan';

    protected static ?string $pluralModelLabel = 'Metode Pengolahan';

    protected static ?string $navigationLabel = 'Metode Pengolahan';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("name.{$locale}")
                ->label(Translatable::label('Nama metode', $locale))
                ->required(Translatable::isRequired($locale))
                ->maxLength(60)
                ->helperText($locale === 'en' ? 'Contoh: Fresh, Frozen, Super Frozen, Skinless, Boneless, Trimmed.' : null)),
            TextInput::make('slug')->label('Slug')->maxLength(100)->alphaDash()->unique(ignoreRecord: true)->placeholder('otomatis dari nama (EN)'),
            Select::make('type')->label('Jenis')->options(ProcessingMethod::TYPES)->required()->native(false)->helperText('Kesegaran/pembekuan, kulit, tulang, perapian, atau perlakuan khusus.'),
            TextInput::make('temperature')->label('Suhu')->maxLength(40)->helperText('Hanya untuk kesegaran/pembekuan, mis. -60°C.'),
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
                TextColumn::make('type')->label('Jenis')->badge()->color('gray')->formatStateUsing(fn (string $state): string => ProcessingMethod::TYPES[$state] ?? $state),
                TextColumn::make('temperature')->label('Suhu')->placeholder('-'),
                TextColumn::make('products_count')->label('Produk')->badge()->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
                ...Translatable::statusColumns(self::LABELS),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->filters([SelectFilter::make('type')->label('Jenis')->options(ProcessingMethod::TYPES)])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->modalDescription('Produk yang memakai metode ini tidak ikut terhapus, hanya kehilangan label metode tersebut.'),
            ])
            ->toolbarActions([BulkActionGroup::make([AutoTranslate::bulkAction(),
                DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageProcessingMethods::route('/')];
    }
}
