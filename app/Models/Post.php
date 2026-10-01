<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\RegistersWebpConversions;
use App\Models\Contracts\HasTranslatableFields;
use App\Support\Html;
use App\Support\Locales;
use App\Support\MediaPresenter;
use App\Support\SeoAnalyzer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A news post: title, excerpt, rich-text content, SEO texts and a cover photo,
 * per language (see App\Models\Concerns\HasTranslations). "slug" is the internal
 * key; the URL uses "slugs" (one per language) — same convention as Service.
 *
 * The home page teases the latest posts; /news lists them all.
 * teases the latest posts (see FrontendData::build()'s "recentPosts", capped
 * at MAX_RECENT — same pattern as the other capped lists); only a post's own
 * detail page (detailPath()) is a real route.
 *
 * @property int $id
 * @property array<string, string|null>|null $title
 * @property string $slug
 * @property array<string, string|null>|null $slugs
 * @property array<string, string|null>|null $excerpt
 * @property array<string, string|null>|null $content
 * @property array<string, string|null>|null $seo_title
 * @property array<string, string|null>|null $seo_description
 * @property array<string, string|null>|null $focus_keyword
 * @property string $status
 * @property Carbon|null $published_at
 * @property bool $is_active
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['title', 'slug', 'slugs', 'excerpt', 'content', 'seo_title', 'seo_description', 'focus_keyword', 'status', 'published_at', 'is_active', 'translation_status'])]
class Post extends Model implements HasMedia, HasTranslatableFields
{
    use FlushesFrontendCache, HasTranslations, InteractsWithMedia, RegistersWebpConversions;

    public const TRANSLATABLE = ['title', 'excerpt', 'content', 'seo_title', 'seo_description', 'focus_keyword'];

    public const DRAFT = 'draft';

    public const PUBLISHED = 'published';

    public const STATUSES = [self::DRAFT => 'Draf', self::PUBLISHED => 'Terbit'];

    /** How many posts the home page #blog section teases. */
    public const MAX_RECENT = 3;

    protected function casts(): array
    {
        return [
            'slugs' => 'array',
            'published_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $post): void {
            $post->slug = $post->slug ?: Str::slug((string) $post->translate('title', Locales::default()));

            $slugs = (array) $post->slugs;

            foreach (Locales::all() as $locale) {
                if (blank($slugs[$locale] ?? null)) {
                    $slugs[$locale] = Str::slug((string) ($post->translation('title', $locale) ?? $post->translate('title', Locales::default()))) ?: $post->slug;
                }
            }

            $post->slugs = $slugs;
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile()->acceptsMimeTypes(SiteSetting::IMAGE_MIMES);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addWebpConversion('lg', 1600, 'cover');
        $this->addWebpConversion('sm', 800, 'cover');
    }

    public function slugFor(?string $locale = null): string
    {
        $locale ??= Locales::current();

        return (string) ($this->slugs[$locale] ?? $this->slugs[Locales::default()] ?? $this->slug);
    }

    /**
     * On-page SEO score (0-100) for one language, same analysis as the admin panel.
     */
    public function seoScore(?string $locale = null): int
    {
        $locale ??= Locales::default();

        return SeoAnalyzer::analyze([
            'title' => $this->translation('title', $locale),
            'slug' => $this->slugs[$locale] ?? null,
            'excerpt' => $this->translation('excerpt', $locale),
            'content' => $this->translation('content', $locale),
            'seo_title' => $this->translation('seo_title', $locale),
            'seo_description' => $this->translation('seo_description', $locale),
            'keyword' => $this->translation('focus_keyword', $locale),
            'has_cover' => $this->getFirstMedia('cover') !== null,
        ])['score'];
    }

    public function isPublished(): bool
    {
        return $this->status === self::PUBLISHED && ($this->published_at === null || $this->published_at->isPast());
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::PUBLISHED)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderByDesc('published_at')->orderByDesc('id');
    }

    /**
     * Path of the detail page in a language: /news/{slug}, /id/berita/{slug}.
     * The index page is /news.
     */
    public function detailPath(?string $locale = null): string
    {
        $locale ??= Locales::current();
        $segment = (string) config("site.locales.{$locale}.segments.news", 'news');

        return Locales::path($locale, "/{$segment}/".$this->slugFor($locale));
    }

    /**
     * Cover photo (16:9).
     *
     * @return array<string, mixed>|null
     */
    public function coverImage(): ?array
    {
        return MediaPresenter::image($this->getFirstMedia('cover'), 'lg', 'sm', 1600, 900, (string) $this->translate('title'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->translate('title'),
            'excerpt' => $this->translate('excerpt'),
            'image' => $this->coverImage(),
            // Two strings, not a live Carbon instance: this array is cached
            // (FrontendData::build(), Cache::rememberForever) and a Carbon object
            // surviving that round trip depends on unserialize() finding the exact
            // same class already loaded, which isn't guaranteed across requests —
            // it previously surfaced as "incomplete object" errors on the cached
            // read. translatedFormat() is resolved here, not in the view, so it
            // still uses the correct per-locale month names (toFrontend() only
            // ever runs while FrontendData::build() has already set the locale).
            'publishedAt' => $this->published_at?->toAtomString(),
            // 'd F Y': PHP date()-style tokens, which is what translatedFormat()
            // actually expects (each character translated independently) — not
            // ICU/Moment-style multi-letter tokens like 'MMMM'/'Y', which get
            // expanded character-by-character into a run of ISO tokens long
            // enough to repeat the month name several times over.
            'publishedAtDisplay' => $this->published_at?->translatedFormat('d F Y'),
            'url' => $this->isPublished() ? $this->detailPath() : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDetail(): array
    {
        return [
            ...$this->toFrontend(),
            'content' => Html::rich($this->translate('content')),
            'seoTitle' => $this->translate('seo_title'),
            'seoDescription' => $this->translate('seo_description'),
            'updatedAt' => $this->updated_at?->toAtomString(),
        ];
    }
}
