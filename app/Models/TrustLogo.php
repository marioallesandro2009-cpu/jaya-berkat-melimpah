<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\RegistersWebpConversions;
use App\Models\Concerns\Sortable;
use App\Support\Links;
use App\Support\MediaPresenter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A partner, client or certification body logo shown in the "Partners" block (hidden while
 * there are no logos). Only add logos you have permission to show.
 *
 * @property int $id
 * @property string $name
 * @property string|null $url
 * @property int $sort_order
 * @property bool $is_active
 */
#[Fillable(['name', 'url', 'sort_order', 'is_active'])]
class TrustLogo extends Model implements HasMedia
{
    use FlushesFrontendCache, InteractsWithMedia, RegistersWebpConversions, Sortable;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile()->acceptsMimeTypes(SiteSetting::LOGO_MIMES);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addWebpConversion('logo_webp', 480, 'logo');
    }

    /**
     * Logos without a file are skipped on the site.
     *
     * @return array<string, mixed>|null
     */
    public function toFrontend(): ?array
    {
        $media = $this->getFirstMedia('logo');

        if (! $media) {
            return null;
        }

        return ['id' => $this->id, 'name' => $this->name, 'url' => Links::safe($this->url), 'logo' => MediaPresenter::logo($media, 'logo_webp')];
    }
}
