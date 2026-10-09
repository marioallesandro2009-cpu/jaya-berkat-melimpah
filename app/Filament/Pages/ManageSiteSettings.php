<?php

namespace App\Filament\Pages;

use App\Filament\Support\Translatable;
use App\Models\SiteSetting;
use App\Support\ColorContrast;
use App\Support\FrontendData;
use App\Support\ImageConversions;
use App\Support\ImageUpload;
use App\Support\Locales;
use App\Support\Translation\Translator;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

/**
 * @property-read Schema $form
 */
class ManageSiteSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Pengaturan Situs';

    protected static ?string $title = 'Pengaturan Situs';

    protected static ?string $slug = 'settings';

    protected static ?int $navigationSort = 1;

    /** Admin labels of the translatable settings (for the "Versi ID" notice). */
    public const LABELS = [
        'company_description' => 'Deskripsi perusahaan',
        'footer_tagline' => 'Kalimat di footer',
        'cta_label' => 'Label tombol CTA',
        'seo_title' => 'Judul halaman (SEO)',
        'seo_description' => 'Meta description',
    ];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $record = $this->getRecord();

        // UI texts: only the admin's own texts; the defaults show as placeholders.
        $texts = [];

        foreach (Locales::all() as $locale) {
            $texts[$locale] = array_intersect_key((array) ($record->ui_texts[$locale] ?? []), SiteSetting::UI_TEXT_DEFAULTS);
        }

        $this->form->fill([
            ...$record->attributesToArray(),
            'ui_texts' => $texts,
            'contact_recipients' => implode("\n", (array) $record->contact_recipients),
            'colors' => $record->brandColors(),
        ]);
    }

    public function getRecord(): SiteSetting
    {
        return SiteSetting::current();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->model($this->getRecord())
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('settings')
                ->persistTabInQueryString()
                ->tabs([
                    Tab::make('Umum')
                        ->icon(Heroicon::OutlinedBuildingOffice)
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('company_name')
                                    ->label('Nama merek')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Dipakai di navbar, judul, llms.txt, dan nama panel admin.'),
                                TextInput::make('legal_name')
                                    ->label('Nama resmi (badan hukum)')
                                    ->maxLength(255)
                                    ->placeholder('PT ...')
                                    ->helperText('Dipakai di hak cipta footer dan data terstruktur Google (legalName). Jika kosong, dipakai nama merek.'),
                                Translatable::fields(fn (string $locale): Textarea => Textarea::make("company_description.{$locale}")
                                    ->label(Translatable::label('Deskripsi perusahaan', $locale))
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->helperText('Satu-dua kalimat tentang perusahaan. Dipakai di llms.txt dan data terstruktur Google (jika kosong, dipakai meta description).')),
                                Translatable::fields(fn (string $locale): TextInput => TextInput::make("footer_tagline.{$locale}")
                                    ->label(Translatable::label('Kalimat di footer', $locale))
                                    ->maxLength(160)),
                                Select::make('hero_mode')
                                    ->label('Tampilan hero beranda')
                                    ->options(SiteSetting::HERO_MODES)
                                    ->default('3d')
                                    ->required()
                                    ->native(false)
                                    ->selectablePlaceholder(false)
                                    ->helperText('Animasi 3D: laut, kapal, dan matahari bergerak (dimuat setelah halaman tampil; di perangkat yang menolaknya otomatis memakai gambar). Gambar: foto atau slide dari menu Slide Hero.'),
                                TextInput::make('hero_slide_duration')
                                    ->label('Pergantian slide hero')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(3)
                                    ->maxValue(30)
                                    ->suffix('detik')
                                    ->required()
                                    ->helperText('Dipakai jika ada dua atau lebih Slide Hero aktif.'),
                                Textarea::make('address')
                                    ->label('Alamat')
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->columnSpanFull()
                                    ->helperText('Tampil di footer, llms.txt, dan data terstruktur Google. Pisahkan baris dengan Enter.'),
                                TextInput::make('whatsapp_number')
                                    ->label('Nomor WhatsApp')
                                    ->tel()
                                    ->maxLength(32)
                                    ->placeholder('+62 812 3456 7890')
                                    ->helperText('Dipakai untuk semua tombol WhatsApp (tombol "Tanya ketersediaan" di armada, CTA layanan, footer, menu mobile). Kosongkan: tombol armada/CTA diarahkan ke form kontak.'),
                                TextInput::make('whatsapp_message')
                                    ->label('Pesan awal tombol WhatsApp')
                                    ->maxLength(300)
                                    ->placeholder('Halo, saya ingin bertanya tentang produk seafood Anda.')
                                    ->helperText('Teks yang sudah terisi saat pengunjung menekan tombol WhatsApp melayang. Kosongkan untuk salam bawaan (mengikuti bahasa halaman). Tombolnya hanya tampil jika Nomor WhatsApp diisi.'),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->maxLength(255),
                                TextInput::make('phone')
                                    ->label('Telepon')
                                    ->tel()
                                    ->maxLength(32)
                                    ->placeholder('+62 21 1234 5678'),
                                TextInput::make('instagram_url')
                                    ->label('URL Instagram')
                                    ->url()
                                    ->rules(['url:http,https'])
                                    ->maxLength(255),
                                TextInput::make('linkedin_url')
                                    ->label('URL LinkedIn')
                                    ->url()
                                    ->rules(['url:http,https'])
                                    ->maxLength(255),
                                Translatable::fields(fn (string $locale): TextInput => TextInput::make("cta_label.{$locale}")
                                    ->label(Translatable::label('Label tombol CTA', $locale))
                                    ->required(Translatable::isRequired($locale))
                                    ->maxLength(60)),
                            ]),
                            Grid::make(2)->schema([
                                self::logoUpload('logo', 'Logo')
                                    ->helperText('PNG/WebP transparan, tinggi ideal minimal 120 px. Tampil di navbar (latar gelap) dan footer. Logo yang bagus untuk latar gelap = versi putih/terang.'),
                                ImageUpload::limitDimensions(SpatieMediaLibraryFileUpload::make('favicon'), '512 x 512 px')
                                    ->label('Favicon')
                                    ->collection('favicon')
                                    ->acceptedFileTypes(SiteSetting::FAVICON_MIMES)
                                    ->maxSize(1024)
                                    ->helperText('PNG/WebP persegi 512 x 512 px, atau ICO. Maks. 1 MB. PNG dan ICO juga memperbarui favicon.ico (16/32/48 px).'),
                            ]),
                        ]),
                    Tab::make('Kontak')
                        ->icon(Heroicon::OutlinedEnvelope)
                        ->schema([
                            View::make('filament.contact-recipients-notice')
                                ->viewData(fn (Get $get): array => [
                                    'empty' => blank(trim((string) $get('contact_recipients'))),
                                    'fallback' => $get('email'),
                                ])
                                ->columnSpanFull(),
                            Textarea::make('contact_recipients')
                                ->label('Penerima notifikasi leads')
                                ->rows(4)
                                ->placeholder("nama@example.com\nmarketing@perusahaan.co.id")
                                ->live(debounce: 600)
                                ->helperText('Satu alamat email per baris (maks. 10). Setiap pesan dari form Kontak dikirim ke semua alamat ini; bisa Gmail pribadi dan bisa diganti kapan saja. Balas email langsung ke pengunjung karena Reply-To sudah berisi email mereka. Pengirim (From) diatur di server, bukan di sini.')
                                ->rules([fn (): Closure => self::validateRecipients()]),
                            Text::make('Nomor WhatsApp (tombol WhatsApp di section Kontak) diatur di tab Umum. Kosongkan nomornya untuk menyembunyikan tombol.')
                                ->color('gray'),
                            self::uiText('contact_email_subject', 'Subjek email notifikasi', 'Ikut bahasa pengunjung. ":name" diganti nama pengirim.', 160),
                            self::uiText('contact_success', 'Pesan sukses form', 'Tampil setelah form terkirim.', 240),
                            self::uiText('contact_error', 'Pesan gagal form', 'Tampil jika pengiriman ditolak (mis. terlalu sering mengirim).', 240),
                        ]),
                    Tab::make('Teks & Label')
                        ->icon(Heroicon::OutlinedLanguage)
                        ->schema([
                            Text::make('Kosongkan kolom untuk memakai teks bawaan (terlihat samar di dalam kolom). Kolom ID yang kosong memakai teks bawaan Bahasa Indonesia. Nama menu navbar/footer diatur di menu "Menu".')
                                ->color('gray'),
                            ...collect(SiteSetting::UI_TEXT_GROUPS)->map(fn (array $keys, string $group): Section => Section::make($group)
                                ->collapsible()
                                ->collapsed()
                                ->schema(collect($keys)->map(fn (string $label, string $key): Grid => self::uiText($key, $label))->values()->all()))->values()->all(),
                        ]),
                    Tab::make('Warna')
                        ->icon(Heroicon::OutlinedSwatch)
                        ->schema([
                            Text::make('Ketik kode hex 6 digit (misalnya #071A21) atau pilih lewat kotak warna. Perubahan langsung berlaku di website setelah disimpan. Sistem memeriksa kontras teks dan memberi peringatan jika ada teks yang sulit dibaca (di bawah standar WCAG AA 4,5:1).')
                                ->color('gray'),
                            Grid::make(3)->schema(collect(SiteSetting::COLORS)->map(fn (array $color, string $key): ColorPicker => self::color($key, $color))->values()->all()),
                            Actions::make([
                                Action::make('resetColors')
                                    ->label('Kembalikan ke warna bawaan')
                                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                                    ->color('gray')
                                    ->action(function (): void {
                                        $this->data['colors'] = array_map(fn (array $color): string => $color[2], SiteSetting::COLORS);
                                    }),
                            ]),
                        ]),
                    Tab::make('SEO')
                        ->icon(Heroicon::OutlinedMagnifyingGlass)
                        ->schema([
                            Translatable::fields(fn (string $locale): TextInput => TextInput::make("seo_title.{$locale}")
                                ->label(Translatable::label('Judul halaman (title)', $locale))
                                ->required(Translatable::isRequired($locale))
                                ->maxLength(120)
                                ->helperText('Tampil di tab browser dan hasil pencarian. Ideal 50–60 karakter.')),
                            Translatable::fields(fn (string $locale): Textarea => Textarea::make("seo_description.{$locale}")
                                ->label(Translatable::label('Meta description', $locale))
                                ->rows(3)
                                ->maxLength(320)
                                ->helperText('Ringkasan untuk hasil pencarian, ideal 140–160 karakter. Jika kosong, dipakai deskripsi perusahaan.')),
                            ImageUpload::limitDimensions(SpatieMediaLibraryFileUpload::make('og_image'), '1200 x 630 px')
                                ->label('Gambar Open Graph (share)')
                                ->collection('og_image')
                                ->image()
                                ->acceptedFileTypes(SiteSetting::IMAGE_MIMES)
                                ->maxSize(5120)
                                ->helperText('Tampil saat link dibagikan di WhatsApp/LinkedIn. Ukuran ideal 1200 x 630 px, JPG atau PNG, maks. 5 MB dan sisi terpanjang maks. 4000 px.'),
                        ]),
                    Tab::make('Terjemahan')
                        ->icon(Heroicon::OutlinedLanguage)
                        ->schema([
                            Text::make(Translator::enabled()
                                ? 'Terjemahan otomatis aktif ('.config('services.translation.driver').'): tombol "Buat draf dari EN" di tiap kolom, dan aksi "Buat draf terjemahan (ID)" untuk banyak data sekaligus di daftar Produk, Spesies, Kategori, Potongan, Metode, dan FAQ. Draf tidak tampil di situs sampai ditandai "Sudah dicek".'
                                : 'Terjemahan otomatis BELUM aktif. Isi TRANSLATION_DRIVER (deepl, google, atau anthropic) dan TRANSLATION_API_KEY di file .env, lalu muat ulang; tombol "Buat draf dari EN" di tiap kolom akan langsung bekerja. Sementara itu terjemahan tetap bisa diketik manual.')
                                ->color('gray'),
                            Repeater::make('translation_glossary')
                                ->label('Glosarium')
                                ->helperText('Dipakai saat membuat draf. Istilah tanpa terjemahan tetap ditulis seperti aslinya (misalnya Cold Storage).')
                                ->table([
                                    TableColumn::make('Istilah (EN)')->markAsRequired(),
                                    TableColumn::make('Terjemahan tetap (ID)'),
                                ])
                                ->schema([
                                    TextInput::make('en')
                                        ->label('Istilah (EN)')
                                        ->required()
                                        ->maxLength(80),
                                    TextInput::make('id')
                                        ->label('Terjemahan tetap (ID)')
                                        ->placeholder('Kosong = tidak diterjemahkan')
                                        ->maxLength(80),
                                ])
                                ->defaultItems(0)
                                ->addActionLabel('Tambah istilah')
                                ->reorderable(false),
                        ]),
                ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        $conversionsEnabled = ImageConversions::enabled();

        return $schema->components([
            Text::make(ImageConversions::statusLabel())
                ->badge()
                ->color($conversionsEnabled ? 'success' : 'warning')
                ->icon($conversionsEnabled ? Heroicon::CheckCircle : Heroicon::ExclamationTriangle),
            // Empty or unchecked Indonesian texts show the English version on /id.
            Text::make(fn (): string => ($summary = Translatable::summary($this->getRecord(), 'id', self::LABELS))
                ? 'Versi ID (tampil versi EN sampai diisi dan dicek). '.$summary
                : 'Versi ID lengkap dan sudah dicek')
                ->badge()
                ->color(fn (): string => Translatable::status($this->getRecord(), 'id') === Translatable::COMPLETE ? 'success' : 'warning')
                ->icon(Heroicon::OutlinedLanguage),
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Simpan')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $record = $this->getRecord();

        $this->form->model($record);

        $state = $this->form->getState();
        $state['contact_recipients'] = self::parseRecipients((string) ($state['contact_recipients'] ?? ''));

        $record->update($state);

        FrontendData::flush();

        Notification::make()
            ->success()
            ->title('Pengaturan disimpan')
            ->send();

        // Colours are saved anyway; the admin decides whether to adjust them.
        $failures = ColorContrast::failures($record->brandColors());

        if ($failures !== []) {
            Notification::make()
                ->warning()
                ->title('Kontras warna kurang')
                ->body(new HtmlString(implode('<br>', array_map(
                    fn (array $failure): string => '• '.e($failure['label']).': '.number_format($failure['ratio'], 2, ',', '').':1 (minimal 4,5:1)',
                    $failures,
                ))))
                ->persistent()
                ->send();
        }

    }

    /**
     * @param  array{0: string, 1: string, 2: string}  $color  label, where it is used, default
     */
    private static function color(string $key, array $color): ColorPicker
    {
        return ColorPicker::make('colors.'.$key)
            ->label($color[0])
            ->hex()
            ->required()
            ->regex(SiteSetting::HEX_PATTERN)
            ->validationMessages(['regex' => 'Gunakan kode hex 6 digit, misalnya #071A21.'])
            ->placeholder($color[2])
            ->helperText($color[1].' Bawaan: '.$color[2]);
    }

    /**
     * One UI text in every language, side by side; empty = the default text.
     */
    private static function uiText(string $key, string $label, ?string $help = null, int $max = 120): Grid
    {
        return Translatable::fields(fn (string $locale): TextInput => TextInput::make("ui_texts.{$locale}.{$key}")
            ->label(Translatable::label($label, $locale))
            ->placeholder(SiteSetting::defaultUiText($key, $locale))
            ->helperText($locale === Locales::default() ? $help : null)
            ->maxLength($max));
    }

    /**
     * One email per line, at most 10, every line a valid address.
     */
    private static function validateRecipients(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $lines = self::parseRecipients((string) $value);

            if (count($lines) > 10) {
                $fail('Maksimal 10 alamat email.');

                return;
            }

            foreach ($lines as $line) {
                if (! filter_var($line, FILTER_VALIDATE_EMAIL)) {
                    $fail('"'.$line.'" bukan alamat email yang valid.');

                    return;
                }
            }
        };
    }

    /**
     * @return list<string>
     */
    private static function parseRecipients(string $text): array
    {
        return array_values(array_unique(array_filter(array_map('trim', preg_split('/[\r\n,;]+/', $text) ?: []))));
    }

    private static function logoUpload(string $collection, string $label): SpatieMediaLibraryFileUpload
    {
        return ImageUpload::limitDimensions(SpatieMediaLibraryFileUpload::make($collection), 'tinggi 72-140 px')
            ->label($label)
            ->collection($collection)
            ->acceptedFileTypes(SiteSetting::LOGO_MIMES)
            ->maxSize(5120);
    }
}
