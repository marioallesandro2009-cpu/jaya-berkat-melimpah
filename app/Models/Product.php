<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\RegistersWebpConversions;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\HasTranslatableFields;
use App\Support\Html;
use App\Support\Links;
use App\Support\Locales;
use App\Support\MediaPresenter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A seafood product: a card in the home page catalogue and, once its detail page is published,
 * a page of its own (/products/{slug}, /id/produk/{slug}) with intro, specifications, rich-text
 * story and a gallery. The name also fills the "Product of interest" choice of the inquiry form.
 *
 * @property int $id
 * @property string|null $slug
 * @property string $detail_status
 * @property array<string, string|null>|null $name
 * @property array<string, string|null>|null $description
 * @property array<string, string|null>|null $image_alt
 * @property array<string, string|null>|null $intro
 * @property array<string, string|null>|null $content
 * @property list<array{label?: array<string, string|null>, value?: array<string, string|null>}>|null $specs
 * @property array<string, string|null>|null $seo_title
 * @property array<string, string|null>|null $seo_description
 * @property int $sort_order
 * @property bool $is_active
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['slug', 'detail_status', 'name', 'description', 'image_alt', 'intro', 'content', 'specs', 'seo_title', 'seo_description', 'sort_order', 'is_active', 'translation_status'])]
class Product extends Model implements HasMedia, HasTranslatableFields
{
    use FlushesFrontendCache, HasTranslations, InteractsWithMedia, RegistersWebpConversions, Sortable;

    public const TRANSLATABLE = ['name', 'description', 'image_alt', 'intro', 'content', 'seo_title', 'seo_description'];

    public const DRAFT = 'draft';

    public const PUBLISHED = 'published';

    public const STATUSES = [self::DRAFT => 'Draf (tanpa halaman detail)', self::PUBLISHED => 'Terbit (halaman detail aktif)'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer', 'specs' => 'array'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $product): void {
            $product->slug = Str::slug((string) ($product->slug ?: $product->translation('name', Locales::default()))) ?: null;
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile()->acceptsMimeTypes(SiteSetting::IMAGE_MIMES);
        $this->addMediaCollection('gallery')->acceptsMimeTypes(SiteSetting::IMAGE_MIMES);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addWebpConversion('lg', 1200, ['image', 'gallery']);
        $this->addWebpConversion('sm', 600, ['image', 'gallery']);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeWithDetailPage(Builder $query): void
    {
        $query->where('detail_status', self::PUBLISHED)->whereNotNull('slug');
    }

    public function hasDetailPage(): bool
    {
        return $this->detail_status === self::PUBLISHED && filled($this->slug);
    }

    /**
     * Path of the detail page in a language: /products/{slug}, /id/produk/{slug}.
     */
    public function detailPath(?string $locale = null): string
    {
        return Links::page('products', $locale).'/'.$this->slug;
    }

    /**
     * Specification rows in the current language (empty rows skipped).
     *
     * @return list<array{label: string, value: string}>
     */
    public function specRows(?string $locale = null): array
    {
        $locale ??= Locales::current();
        $default = Locales::default();
        $rows = [];

        foreach ((array) $this->specs as $row) {
            $label = trim((string) ($row['label'][$locale] ?? '') ?: (string) ($row['label'][$default] ?? ''));
            $value = trim((string) ($row['value'][$locale] ?? '') ?: (string) ($row['value'][$default] ?? ''));

            if ($label !== '' && $value !== '') {
                $rows[] = ['label' => $label, 'value' => $value];
            }
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        return [
            'id' => $this->id,
            'name' => (string) $this->translate('name'),
            // The form always submits the English name: a stable value for the team's inbox.
            'value' => (string) $this->translation('name', 'en'),
            'description' => $this->translate('description'),
            'image' => MediaPresenter::image($this->getFirstMedia('image'), 'lg', 'sm', 1200, 600, $this->translate('image_alt') ?? $this->translate('name')),
            'url' => $this->hasDetailPage() ? $this->detailPath() : null,
        ];
    }

    /**
     * Everything the detail page shows.
     *
     * @return array<string, mixed>
     */
    public function toDetail(): array
    {
        $name = (string) $this->translate('name');

        return [
            ...$this->toFrontend(),
            'intro' => $this->translate('intro') ?? $this->translate('description'),
            'content' => Html::rich($this->translate('content')),
            'specs' => $this->specRows(),
            'gallery' => $this->getMedia('gallery')->map(fn (Media $media): ?array => MediaPresenter::image($media, 'lg', 'sm', 1200, 600, $name))->filter()->values()->all(),
            'seoTitle' => $this->translate('seo_title'),
            'seoDescription' => $this->translate('seo_description'),
            'updatedAt' => $this->updated_at?->toAtomString(),
        ];
    }
}
