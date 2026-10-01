<?php

namespace App\Filament\Resources\Stats;

use App\Filament\Resources\Stats\Pages\ManageStats;
use App\Filament\Support\HasTranslatableRecordTitle;
use App\Filament\Support\Sample;
use App\Filament\Support\Translatable;
use App\Models\Stat;
use App\Support\FrontendData;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class StatResource extends Resource
{
    use HasTranslatableRecordTitle;

    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = ['label' => 'Keterangan', 'text_value' => 'Teks pengganti'];

    protected static ?string $model = Stat::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Konten Beranda';

    protected static ?string $modelLabel = 'angka';

    protected static ?string $pluralModelLabel = 'Angka Skala';

    protected static ?string $navigationLabel = 'Angka Skala';

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Sample::notice(),
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("label.{$locale}")
                ->label(Translatable::label('Keterangan', $locale))
                ->required(Translatable::isRequired($locale))
                ->maxLength(60)),
            TextInput::make('value')->label('Angka')->numeric()->minValue(0)->helperText('Dihitung naik saat tampil di layar. Kosongkan jika hanya teks (mis. nama kota).'),
            TextInput::make('suffix')->label('Akhiran angka')->maxLength(12)->helperText('Mis. "+" atau "%".'),
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("text_value.{$locale}")
                ->label(Translatable::label('Teks pengganti angka', $locale))

                ->maxLength(40)
                ->helperText('Dipakai jika Angka kosong, mis. "Jakarta".')),
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
                Sample::column(),
                Translatable::column('label', 'Keterangan'),
                TextColumn::make('value')->label('Angka')->default('-'),
                ...Translatable::statusColumns(self::LABELS),
                ToggleColumn::make('is_active')->label('Tampil'),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(fn (Model $record) => $record->getAttribute('is_sample') ? $record->forceFill(['is_sample' => false])->saveQuietly() : null),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageStats::route('/')];
    }
}
