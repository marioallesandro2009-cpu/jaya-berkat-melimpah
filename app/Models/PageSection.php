<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\RegistersWebpConversions;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\HasTranslatableFields;
use App\Support\Html;
use App\Support\Links;
use App\Support\MediaPresenter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A block of page copy (eyebrow, headline, paragraphs, button, photo). Blocks made by the site
 * are identified by a fixed key (see KEYS) that the templates ask for; blocks added in the admin
 * are "custom" (is_custom) and rendered by the generic block template with the chosen layout.
 *
 * Order: the blocks listed in FLOW_* (plus every custom block) are rendered in sort_order, so the
 * admin can reorder, hide or add them. The other keys (hero, contact, page header, closing) keep
 * their fixed place.
 *
 * @property int $id
 * @property string $key
 * @property string $page
 * @property string|null $layout
 * @property string|null $background
 * @property bool $is_custom
 * @property array<string, string|null>|null $eyebrow
 * @property array<string, string|null>|null $title
 * @property array<string, string|null>|null $body
 * @property array<string, string|null>|null $body_extra
 * @property array<string, string|null>|null $cta_label
 * @property string|null $cta_url
 * @property array<string, string|null>|null $image_alt
 * @property bool $is_active
 * @property int $sort_order
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['key', 'page', 'layout', 'background', 'is_custom', 'eyebrow', 'title', 'body', 'body_extra', 'cta_label', 'cta_url', 'image_alt', 'is_active', 'sort_order', 'translation_status'])]
class PageSection extends Model implements HasMedia, HasTranslatableFields
{
    use FlushesFrontendCache, HasTranslations, InteractsWithMedia, RegistersWebpConversions, Sortable;

    public const TRANSLATABLE = ['eyebrow', 'title', 'body', 'body_extra', 'cta_label', 'image_alt'];

    public const HOME = 'home';

    public const COMPANY = 'company';

    /**
     * Section key => admin label.
     */
    public const KEYS = [
        'hero' => 'Beranda: Hero (judul utama, tetap di atas)',
        'statement' => 'Beranda: Pernyataan + angka',
        'origin' => 'Beranda: Asal (foto penuh)',
        'quality' => 'Beranda: Mutu',
        'products' => 'Beranda: Produk (pengantar)',
        'chain' => 'Beranda: Perjalanan (pengantar)',
        'sustainability' => 'Beranda: Keberlanjutan',
        'markets' => 'Beranda: Pasar',
        'partners' => 'Beranda: Partner (judul; tampil jika ada logo)',
        'locations' => 'Beranda: Lokasi (judul; tampil jika ada lokasi)',
        'faq' => 'Beranda: FAQ (judul; tampil jika ada pertanyaan)',
        'news' => 'Beranda & halaman Berita: pengantar',
        'contact' => 'Beranda: Kontak / form (tetap di bawah)',
        'company_header' => 'Perusahaan: Header halaman (tetap di atas)',
        'company_overview' => 'Perusahaan: Siapa kami',
        'company_history' => 'Perusahaan: Sejarah (pengantar)',
        'company_vision' => 'Perusahaan: Visi',
        'company_mission' => 'Perusahaan: Misi (ikut blok Visi)',
        'company_values' => 'Perusahaan: Nilai (pengantar)',
        'company_leadership' => 'Perusahaan: Kepemimpinan (pengantar)',
        'company_operations' => 'Perusahaan: Cara kerja',
        'company_facilities' => 'Perusahaan: Lokasi operasi (foto penuh)',
        'company_certifications' => 'Perusahaan: Sertifikasi (pengantar)',
        'company_closing' => 'Perusahaan: Penutup (tetap di bawah)',
    ];

    /**
     * Blocks that can be reordered, hidden or mixed with custom blocks, per page.
     *
     * @var array<string, list<string>>
     */
    public const FLOW = [
        self::HOME => ['statement', 'origin', 'quality', 'products', 'chain', 'sustainability', 'markets', 'partners', 'locations', 'faq', 'news'],
        self::COMPANY => ['company_overview', 'company_history', 'company_vision', 'company_values', 'company_leadership', 'company_operations', 'company_facilities', 'company_certifications'],
    ];

    public const PAGES = [self::HOME => 'Beranda', self::COMPANY => 'Perusahaan'];

    /** Layouts of a custom block. */
    public const LAYOUTS = [
        'image_right' => 'Teks di kiri, foto di kanan',
        'image_left' => 'Foto di kiri, teks di kanan',
        'full_bleed' => 'Foto penuh dengan teks di atasnya',
        'text_only' => 'Pernyataan besar tanpa foto',
    ];

    public const BACKGROUNDS = ['white' => 'Putih', 'light' => 'Krem', 'dark' => 'Biru gelap', 'ocean' => 'Biru laut'];

    /**
     * Anchors on the home page a menu item can point to: anchor id => admin label.
     * The fixed blocks first, then the custom blocks added in the admin.
     *
     * @return array<string, string>
     */
    public static function anchorOptions(): array
    {
        $options = [
            'story' => 'Cerita dan angka skala',
            'business' => 'Asal (foto penuh)',
            'quality' => 'Mutu',
            'products' => 'Produk',
            'journey' => 'Perjalanan (rantai nilai)',
            'sustainability' => 'Keberlanjutan',
            'markets' => 'Pasar',
            'partners' => 'Partner',
            'locations' => 'Lokasi',
            'faq' => 'FAQ',
            'news' => 'Berita (hanya jika ada artikel)',
            'contact' => 'Kontak / form penawaran',
        ];

        foreach (static::query()->where('is_custom', true)->where('page', self::HOME)->orderBy('sort_order')->get() as $block) {
            $options[$block->key] = 'Blok: '.($block->translation('title', 'en') ?? $block->key);
        }

        return $options;
    }

    /**
     * Anchor id of a block on its page.
     */
    public static function anchorFor(string $key): string
    {
        return ['statement' => 'story', 'origin' => 'business', 'chain' => 'journey', 'company_leadership' => 'leadership', 'company_certifications' => 'certifications'][$key] ?? $key;
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_custom' => 'boolean', 'sort_order' => 'integer'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile()->acceptsMimeTypes(SiteSetting::IMAGE_MIMES);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addWebpConversion('lg', 1920, 'image');
        $this->addWebpConversion('sm', 800, 'image');
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        $title = (string) $this->translate('title');

        return [
            'key' => $this->key,
            'anchor' => self::anchorFor($this->key),
            'page' => $this->page,
            'custom' => $this->is_custom,
            'layout' => $this->layout ?: 'image_right',
            'background' => array_key_exists((string) $this->background, self::BACKGROUNDS) ? $this->background : 'white',
            'eyebrow' => $this->translate('eyebrow'),
            'title' => $title,
            'body' => $this->translate('body'),
            'bodyHtml' => Html::paragraphs($this->translate('body')),
            'extraHtml' => Html::paragraphs($this->translate('body_extra')),
            'cta' => $this->translate('cta_label'),
            'ctaUrl' => Links::safe($this->cta_url),
            'image' => MediaPresenter::image($this->getFirstMedia('image'), 'lg', 'sm', 1920, 800, $this->translate('image_alt') ?? $title),
        ];
    }
}
