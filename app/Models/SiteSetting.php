<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\RegistersWebpConversions;
use App\Models\Contracts\HasTranslatableFields;
use App\Support\ImageConversions;
use App\Support\Locales;
use App\Support\MediaPresenter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Singleton model: the site always has exactly one settings row.
 *
 * @property int $id
 * @property string $company_name
 * @property string|null $legal_name
 * @property array<string, string|null>|null $company_description
 * @property array<string, string|null>|null $footer_tagline
 * @property string|null $whatsapp_number
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $instagram_url
 * @property string|null $linkedin_url
 * @property array<string, string|null>|null $cta_label
 * @property array<string, array<string, string|null>>|null $ui_texts
 * @property list<string>|null $contact_recipients
 * @property array<string, string|null>|null $seo_title
 * @property array<string, string|null>|null $seo_description
 * @property array<string, array<string, string>>|null $translation_status
 * @property list<array{en?: string|null, id?: string|null}>|null $translation_glossary
 * @property array<string, string|null>|null $colors
 * @property int $hero_slide_duration
 */
#[Fillable([
    'company_name',
    'legal_name',
    'company_description',
    'footer_tagline',
    'whatsapp_number',
    'email',
    'phone',
    'address',
    'instagram_url',
    'linkedin_url',
    'cta_label',
    'ui_texts',
    'contact_recipients',
    'seo_title',
    'seo_description',
    'translation_status',
    'translation_glossary',
    'colors',
    'hero_slide_duration',
])]
class SiteSetting extends Model implements HasMedia, HasTranslatableFields
{
    use FlushesFrontendCache, HasTranslations, InteractsWithMedia, RegistersWebpConversions;

    public const TRANSLATABLE = [
        'company_description',
        'footer_tagline',
        'cta_label',
        'seo_title',
        'seo_description',
    ];

    public const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public const LOGO_MIMES = ['image/png', 'image/webp', 'image/jpeg'];

    public const FAVICON_MIMES = ['image/png', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon'];

    /**
     * Brand colours (hex) editable in the admin: css variable => [admin label, where it is used, default].
     * The defaults are the values in public/css/style.css (keep both in sync); a colour that differs
     * from its default overrides the variable at runtime (see cssVariables()).
     */
    public const COLORS = [
        'deep-ocean' => ['Biru gelap (utama)', 'Latar bagian gelap, navbar setelah di-scroll, menu mobile, dasar overlay foto.', '#071A21'],
        'secondary-ocean' => ['Biru laut', 'Latar bagian kontak dan penutup, foto pimpinan tanpa foto.', '#123A43'],
        'near-black' => ['Hitam pekat', 'Latar footer dan teks di tombol terang.', '#080D0F'],
        'warm-off-white' => ['Krem', 'Latar bagian terang dan teks di atas latar gelap.', '#F5F4EF'],
        'ink' => ['Teks utama', 'Teks di latar terang.', '#10181B'],
        'accent-coral' => ['Aksen hangat', 'Angka tahap, garis menu aktif, penanda kecil.', '#C97B4A'],
        'aqua' => ['Aksen dingin', 'Tombol utama saat disorot, garis perjalanan, indikator fokus.', '#7CC4E4'],
    ];

    public const HEX_PATTERN = '/^#[0-9A-Fa-f]{6}$/';

    /**
     * Button and screen reader labels editable in the admin, per language
     * (ui_texts: {"en": {key: text}, "id": {key: text}}). The value here is the English
     * source string; its translation in lang/<locale>.json is the default for an empty field.
     */
    public const UI_TEXT_DEFAULTS = [
        'hero_primary_cta' => 'Follow the journey',
        'hero_secondary_cta' => 'Request a quote',
        'scroll' => 'Scroll',
        'footer_explore' => 'Explore',
        'footer_company' => 'Company',
        'footer_contact' => 'Contact',
        'footer_copyright' => 'All rights reserved.',
        'form_name' => 'Full name',
        'form_company' => 'Company',
        'form_email' => 'Email',
        'form_country' => 'Country',
        'form_phone' => 'Phone',
        'form_product' => 'Product of interest',
        'form_product_select' => 'Select a product',
        'form_product_other' => 'Other / multiple',
        'form_volume' => 'Estimated volume',
        'form_volume_placeholder' => 'e.g. 5 tons / month',
        'form_message' => 'Message',
        'form_optional' => 'optional',
        'form_submit' => 'Send inquiry',
        'form_sending' => 'Sending...',
        'form_whatsapp' => 'or chat on WhatsApp',
        'contact_success' => 'Thank you! Your inquiry has been received and our team will get back to you soon.',
        'contact_error' => 'Your inquiry could not be sent. Please try again or contact us on WhatsApp.',
        'contact_email_subject' => 'New inquiry from :name',
        'news_read_more' => 'Read more',
        'news_back' => 'Back to news',
        'news_latest' => 'Latest news',
        'news_empty' => 'No news has been published yet.',
        'product_learn_more' => 'View details',
        'product_specs' => 'Specifications',
        'product_gallery' => 'Gallery',
        'product_other' => 'Other products',
        'product_all' => 'All products',
        'product_back' => 'Back to products',
        'product_categories' => 'Product categories',
        'skip_link' => 'Skip to content',
        'menu_open' => 'Open menu',
        'menu_close' => 'Close menu',
        'language' => 'Language',
        'nav_primary' => 'Primary',
    ];

    /**
     * How the UI texts are grouped on the admin's "Teks & Label" tab: group => [key => admin label].
     * The contact_* keys live on the "Kontak" tab instead.
     */
    public const UI_TEXT_GROUPS = [
        'Hero dan tombol' => [
            'hero_primary_cta' => 'Tombol utama di hero',
            'hero_secondary_cta' => 'Tautan "minta penawaran" (hero dan produk)',
            'scroll' => 'Petunjuk gulir di hero',
        ],
        'Footer' => [
            'footer_explore' => 'Judul kolom Jelajahi',
            'footer_company' => 'Judul kolom Perusahaan',
            'footer_contact' => 'Judul kolom Kontak',
            'footer_copyright' => 'Teks hak cipta (setelah "© tahun nama resmi")',
        ],
        'Formulir penawaran' => [
            'form_name' => 'Label Nama',
            'form_company' => 'Label Perusahaan',
            'form_email' => 'Label Email',
            'form_country' => 'Label Negara',
            'form_phone' => 'Label Telepon',
            'form_product' => 'Label Produk yang diminati',
            'form_product_select' => 'Pilihan awal produk',
            'form_product_other' => 'Pilihan "Lainnya"',
            'form_volume' => 'Label Perkiraan volume',
            'form_volume_placeholder' => 'Contoh isian volume',
            'form_message' => 'Label Pesan',
            'form_optional' => 'Kata "opsional"',
            'form_submit' => 'Tombol kirim',
            'form_sending' => 'Teks saat mengirim',
            'form_whatsapp' => 'Tautan WhatsApp di bawah form',
        ],
        'Berita' => [
            'news_read_more' => 'Tautan "Baca selengkapnya"',
            'news_back' => 'Tautan kembali ke berita',
            'news_latest' => 'Judul berita terbaru',
            'news_empty' => 'Teks saat belum ada berita',
        ],
        'Produk' => [
            'product_learn_more' => 'Tautan ke halaman detail produk',
            'product_specs' => 'Judul spesifikasi',
            'product_gallery' => 'Judul galeri',
            'product_other' => 'Judul produk lainnya',
            'product_all' => 'Tautan semua produk',
            'product_back' => 'Tautan kembali ke produk',
            'product_categories' => 'Nama navigasi filter kategori (pembaca layar)',
        ],
        'Aksesibilitas (dibaca pembaca layar)' => [
            'skip_link' => 'Tautan "lewati ke konten"',
            'menu_open' => 'Tombol buka menu (mobile)',
            'menu_close' => 'Tombol tutup menu (mobile)',
            'language' => 'Nama grup tombol bahasa',
            'nav_primary' => 'Nama navigasi utama',
        ],
    ];

    protected function casts(): array
    {
        return [
            'ui_texts' => 'array',
            'contact_recipients' => 'array',
            'translation_glossary' => 'json:unicode',
            'colors' => 'array',
            'hero_slide_duration' => 'integer',
        ];
    }

    public static function current(): self
    {
        $settings = static::query()->oldest('id')->first();

        if (! $settings) {
            static::query()->create([]);
            $settings = static::query()->oldest('id')->firstOrFail();
        }

        return $settings;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile()->acceptsMimeTypes(self::LOGO_MIMES);
        $this->addMediaCollection('favicon')->singleFile()->acceptsMimeTypes(self::FAVICON_MIMES);
        $this->addMediaCollection('og_image')->singleFile()->acceptsMimeTypes(self::IMAGE_MIMES);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Favicon sizes (see MediaPresenter::favicon); an ICO or SVG upload is used as it is.
        if (ImageConversions::enabled() && in_array($media?->mime_type, ['image/png', 'image/webp'], true)) {
            foreach (MediaPresenter::FAVICON_SIZES as $size) {
                $this->addMediaConversion("favicon_{$size}")
                    ->performOnCollections('favicon')
                    ->nonQueued()
                    ->format('png')
                    ->fit(Fit::Contain, $size, $size);
            }
        }
    }

    /**
     * Brand colours as uppercase "#RRGGBB"; a missing or invalid value uses the default.
     *
     * @return array<string, string>
     */
    public function brandColors(): array
    {
        $colors = [];

        foreach (self::COLORS as $key => [, , $default]) {
            $value = trim((string) ($this->colors[$key] ?? ''));
            $colors[$key] = preg_match(self::HEX_PATTERN, $value) ? strtoupper($value) : $default;
        }

        return $colors;
    }

    /**
     * CSS custom properties for the colours that differ from the stylesheet, including the
     * derived values (rgb triplets, soft text and hairlines). Empty when nothing was changed.
     */
    public function cssVariables(): string
    {
        $colors = $this->brandColors();
        $changed = array_filter($colors, fn (string $hex, string $key): bool => $hex !== self::COLORS[$key][2], ARRAY_FILTER_USE_BOTH);

        if ($changed === []) {
            return '';
        }

        $rgb = fn (string $hex): string => implode(', ', sscanf($hex, '#%02x%02x%02x'));
        $css = '';

        foreach ($changed as $key => $hex) {
            $css .= "--{$key}:{$hex};--{$key}-rgb:{$rgb($hex)};";
        }

        if (isset($changed['warm-off-white'])) {
            $css .= "--paper-soft:rgba({$rgb($colors['warm-off-white'])},0.72);--hairline-dark:rgba({$rgb($colors['warm-off-white'])},0.16);";
        }

        if (isset($changed['ink'])) {
            $css .= "--ink-soft:rgba({$rgb($colors['ink'])},0.66);--hairline-light:rgba({$rgb($colors['ink'])},0.12);";
        }

        return $css;
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        $logo = $this->getFirstMedia('logo');
        $ogImage = $this->getFirstMedia('og_image');

        return [
            'companyName' => $this->company_name,
            // Registered name for the copyright and JSON-LD; falls back to the brand name.
            'legalName' => $this->legal_name ?: $this->company_name,
            'companyDescription' => $this->translate('company_description'),
            'footerTagline' => (string) ($this->translate('footer_tagline') ?? ''),
            'texts' => $this->uiTexts(Locales::current()),
            'colors' => $this->brandColors(),
            'cssVariables' => $this->cssVariables(),
            'heroSlideDuration' => max(3, (int) $this->hero_slide_duration),
            'logo' => $logo ? ['url' => $logo->getUrl(), 'width' => (int) $logo->getCustomProperty('width'), 'height' => (int) $logo->getCustomProperty('height')] : null,
            'favicon' => MediaPresenter::favicon($this->getFirstMedia('favicon')),
            'whatsappUrl' => self::whatsappUrl($this->whatsapp_number),
            'whatsappNumber' => $this->whatsapp_number,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'instagramUrl' => $this->instagram_url,
            'linkedinUrl' => $this->linkedin_url,
            'sameAs' => array_values(array_filter([$this->instagram_url, $this->linkedin_url])),
            'ctaLabel' => (string) $this->translate('cta_label'),
            'seo' => [
                'title' => (string) $this->translate('seo_title'),
                'description' => $this->translate('seo_description') ?? $this->translate('company_description'),
                // Original file: social networks do not all accept WebP/AVIF.
                'image' => $ogImage ? MediaPresenter::original($ogImage) : null,
            ],
        ];
    }

    /**
     * UI texts in one language: the admin text, else the default from lang/<locale>.json.
     *
     * @return array<string, string>
     */
    public function uiTexts(?string $locale = null): array
    {
        $locale ??= Locales::default();
        $texts = [];

        foreach (array_keys(self::UI_TEXT_DEFAULTS) as $key) {
            $value = trim((string) ($this->ui_texts[$locale][$key] ?? ''));
            $texts[$key] = $value !== '' ? $value : self::defaultUiText($key, $locale);
        }

        return $texts;
    }

    /**
     * Glossary for drafting translations: rows with an English term and its fixed
     * Indonesian translation (empty = keep the English term).
     *
     * @return list<array{en: string, id: string|null}>
     */
    public function glossary(): array
    {
        $rows = [];

        foreach ((array) $this->translation_glossary as $row) {
            $en = trim((string) ($row['en'] ?? ''));

            if ($en !== '') {
                $id = trim((string) ($row['id'] ?? ''));
                $rows[] = ['en' => $en, 'id' => $id !== '' ? $id : null];
            }
        }

        return $rows;
    }

    /**
     * Where the inquiry form notifications go: the addresses set in the admin, else the
     * public contact email as a stop-gap, else nobody.
     *
     * @return list<string>
     */
    public function contactRecipients(): array
    {
        $emails = array_values(array_filter(
            array_map(fn ($email): string => trim((string) $email), (array) $this->contact_recipients),
            fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
        ));

        if ($emails === [] && filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return [(string) $this->email];
        }

        return $emails;
    }

    /**
     * True while no recipient list is set and notifications fall back to the public email.
     */
    public function usesFallbackRecipient(): bool
    {
        return array_filter(array_map(fn ($email): string => trim((string) $email), (array) $this->contact_recipients)) === [];
    }

    public static function defaultUiText(string $key, string $locale): string
    {
        return (string) __(self::UI_TEXT_DEFAULTS[$key], [], $locale);
    }

    /**
     * wa.me link from a local ("0812...") or international ("+62 812...") number.
     */
    public static function whatsappUrl(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number) ?: '';

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }

        return $digits !== '' ? 'https://wa.me/'.$digits : null;
    }
}
