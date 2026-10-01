<?php

namespace App\Filament\Resources\Features;

use App\Filament\Resources\Features\Pages\ManageFeatures;
use App\Filament\Support\HasTranslatableRecordTitle;
use App\Filament\Support\Translatable;
use App\Models\Feature;
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
use UnitEnum;

class FeatureResource extends Resource
{
    use HasTranslatableRecordTitle;

    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = ['title' => 'Judul', 'body' => 'Isi'];

    protected static ?string $model = Feature::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Konten Beranda';

    protected static ?string $modelLabel = 'blok teks';

    protected static ?string $pluralModelLabel = 'Blok Teks (Mutu, Keberlanjutan, Nilai)';

    protected static ?string $navigationLabel = 'Blok Teks';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Select::make('group')->label('Dipakai di')->options(Feature::GROUPS)->required()->native(false),
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("title.{$locale}")
                ->label(Translatable::label('Judul', $locale))
                ->required(Translatable::isRequired($locale))
                ->maxLength(80)),
            Translatable::fields(fn (string $locale): Textarea => Textarea::make("body.{$locale}")
                ->label(Translatable::label('Isi', $locale))

                ->rows(4)
                ->maxLength(500)
                ->helperText('Boleh kosong untuk "titik pemeriksaan".')),
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
            ->filters([SelectFilter::make('group')->label('Dipakai di')->options(Feature::GROUPS)])
            ->columns([
                TextColumn::make('group')->label('Dipakai di')->badge()->formatStateUsing(fn (string $state): string => Feature::GROUPS[$state] ?? $state),
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
        return ['index' => ManageFeatures::route('/')];
    }
}
