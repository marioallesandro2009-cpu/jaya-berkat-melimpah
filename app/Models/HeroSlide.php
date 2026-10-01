<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\RegistersWebpConversions;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\HasTranslatableFields;
use App\Support\MediaPresenter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * An extra hero photo. With two or more active slides the hero cross-fades between them
 * (speed: Pengaturan Situs); with none the hero shows the photo of the "hero" block.
 *
 * @property int $id
 * @property array<string, string|null>|null $label
 * @property array<string, string|null>|null $image_alt
 * @property int $sort_order
 * @property bool $is_active
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['label', 'image_alt', 'sort_order', 'is_active', 'translation_status'])]
class HeroSlide extends Model implements HasMedia, HasTranslatableFields
{
    use FlushesFrontendCache, HasTranslations, InteractsWithMedia, RegistersWebpConversions, Sortable;

    public const TRANSLATABLE = ['label', 'image_alt'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
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
     * Slides without a photo are skipped on the site.
     *
     * @return array<string, mixed>|null
     */
    public function toFrontend(): ?array
    {
        $image = MediaPresenter::image($this->getFirstMedia('image'), 'lg', 'sm', 1920, 800, $this->translate('image_alt') ?? $this->translate('label'));

        return $image ? ['id' => $this->id, 'label' => $this->translate('label'), 'image' => $image] : null;
    }
}
