<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\HasTranslatableFields;
use App\Support\Links;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A link in the navbar or in a footer column. The link is stored as type + target and turned
 * into a URL when the page is rendered (App\Support\Links::menu()), because "#contact" and
 * "/#contact" depend on the page being shown.
 *
 * @property int $id
 * @property string $location
 * @property array<string, string|null>|null $label
 * @property string $type
 * @property string $target
 * @property bool $new_tab
 * @property bool $is_button
 * @property int $sort_order
 * @property bool $is_active
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['location', 'label', 'type', 'target', 'new_tab', 'is_button', 'sort_order', 'is_active', 'translation_status'])]
class MenuItem extends Model implements HasTranslatableFields
{
    use FlushesFrontendCache, HasTranslations, Sortable;

    public const TRANSLATABLE = ['label'];

    public const HEADER = 'header';

    public const FOOTER_EXPLORE = 'footer_explore';

    public const FOOTER_COMPANY = 'footer_company';

    public const LOCATIONS = [
        self::HEADER => 'Navbar (atas)',
        self::FOOTER_EXPLORE => 'Footer: kolom Jelajahi',
        self::FOOTER_COMPANY => 'Footer: kolom Perusahaan',
    ];

    public const SECTION = 'section';

    public const PAGE = 'page';

    public const URL = 'url';

    public const TYPES = [
        self::SECTION => 'Bagian di beranda',
        self::PAGE => 'Halaman',
        self::URL => 'Alamat lain (URL)',
    ];

    /** Pages a menu item can point to (the key of App\Support\Links::page(), or "home"). */
    public const PAGES = [
        'home' => 'Beranda',
        'company' => 'Perusahaan',
        'news' => 'Berita (hanya tampil jika ada artikel terbit)',
    ];

    protected function casts(): array
    {
        return ['new_tab' => 'boolean', 'is_button' => 'boolean', 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeAt(Builder $query, string $location): void
    {
        $query->where('location', $location);
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        return [
            'id' => $this->id,
            'label' => (string) $this->translate('label'),
            'type' => $this->type,
            'target' => $this->type === self::URL ? (Links::safe($this->target) ?? '#') : $this->target,
            'newTab' => $this->new_tab,
            'button' => $this->is_button,
        ];
    }
}
