<?php

namespace App\Filament\Resources\TimelineItems;

use App\Filament\Resources\TimelineItems\Pages\ManageTimelineItems;
use App\Filament\Support\HasTranslatableRecordTitle;
use App\Filament\Support\Sample;
use App\Filament\Support\Translatable;
use App\Models\TimelineItem;
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
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class TimelineItemResource extends Resource
{
    use HasTranslatableRecordTitle;

    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = ['year_label' => 'Tahun', 'title' => 'Judul', 'body' => 'Keterangan'];

    protected static ?string $model = TimelineItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Halaman Perusahaan';

    protected static ?string $modelLabel = 'tonggak sejarah';

    protected static ?string $pluralModelLabel = 'Sejarah (Timeline)';

    protected static ?string $navigationLabel = 'Sejarah (Timeline)';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Sample::notice(),
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("year_label.{$locale}")
                ->label(Translatable::label('Tahun / penanda', $locale))
                ->required(Translatable::isRequired($locale))
                ->maxLength(30)
                ->helperText('Mis. "2011" atau "Today" / "Hari ini".')),
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("title.{$locale}")
                ->label(Translatable::label('Judul', $locale))
                ->required(Translatable::isRequired($locale))
                ->maxLength(100)),
            Translatable::fields(fn (string $locale): Textarea => Textarea::make("body.{$locale}")
                ->label(Translatable::label('Keterangan', $locale))

                ->rows(3)
                ->maxLength(400)),
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
                Translatable::column('year_label', 'Tahun'),
                Translatable::column('title', 'Judul'),
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
        return ['index' => ManageTimelineItems::route('/')];
    }
}
