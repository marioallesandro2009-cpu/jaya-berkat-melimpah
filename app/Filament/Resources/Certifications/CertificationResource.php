<?php

namespace App\Filament\Resources\Certifications;

use App\Filament\Resources\Certifications\Pages\ManageCertifications;
use App\Filament\Support\Sample;
use App\Filament\Support\Translatable;
use App\Models\Certification;
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

class CertificationResource extends Resource
{
    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = ['status_label' => 'Status'];

    protected static ?string $model = Certification::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckBadge;

    protected static string|UnitEnum|null $navigationGroup = 'Halaman Perusahaan';

    protected static ?string $modelLabel = 'sertifikasi';

    protected static ?string $pluralModelLabel = 'Sertifikasi';

    protected static ?string $navigationLabel = 'Sertifikasi';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Sample::notice(),
            TextInput::make('name')->label('Nama sertifikasi / standar')->required()->maxLength(120)->helperText('Hanya isi sertifikasi yang benar-benar dimiliki perusahaan.'),
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("status_label.{$locale}")
                ->label(Translatable::label('Status', $locale))
                ->required(Translatable::isRequired($locale))
                ->maxLength(40)
                ->helperText('Mis. "Certified" / "Bersertifikat".')),
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
                TextColumn::make('name')->label('Nama')->searchable()->weight('medium'),
                Translatable::column('status_label', 'Status'),
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
        return ['index' => ManageCertifications::route('/')];
    }
}
