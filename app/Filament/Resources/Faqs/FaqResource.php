<?php

namespace App\Filament\Resources\Faqs;

use App\Filament\Resources\Faqs\Pages\ManageFaqs;
use App\Filament\Support\AutoTranslate;
use App\Filament\Support\HasTranslatableRecordTitle;
use App\Filament\Support\Translatable;
use App\Models\Faq;
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
use UnitEnum;

class FaqResource extends Resource
{
    use HasTranslatableRecordTitle;

    /** Admin labels of the translatable fields (also used by the table). */
    public const LABELS = ['question' => 'Pertanyaan', 'answer' => 'Jawaban'];

    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Konten Beranda';

    protected static ?string $modelLabel = 'pertanyaan';

    protected static ?string $pluralModelLabel = 'FAQ';

    protected static ?string $navigationLabel = 'FAQ';

    protected static ?string $recordTitleAttribute = 'question';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Translatable::fields(fn (string $locale): TextInput => TextInput::make("question.{$locale}")
                ->label(Translatable::label('Pertanyaan', $locale))
                ->required(Translatable::isRequired($locale))
                ->maxLength(200)),
            Translatable::fields(fn (string $locale): Textarea => Textarea::make("answer.{$locale}")
                ->label(Translatable::label('Jawaban', $locale))

                ->rows(5)
                ->maxLength(1500)),
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
                Translatable::column('question', 'Pertanyaan'),
                ...Translatable::statusColumns(self::LABELS),
                ToggleColumn::make('is_active')->label('Tampil'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([AutoTranslate::bulkAction(),
                    DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageFaqs::route('/')];
    }
}
