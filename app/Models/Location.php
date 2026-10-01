<?php

namespace App\Models;

use App\Models\Concerns\FlushesFrontendCache;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\RegistersWebpConversions;
use App\Models\Concerns\Sortable;
use App\Models\Contracts\HasTranslatableFields;
use App\Support\Links;
use App\Support\MediaPresenter;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * An office, processing site or port, listed in the "Locations" block (hidden while there are
 * none). The map link opens the address in the visitor's maps app.
 *
 * @property int $id
 * @property string $name
 * @property array<string, string|null>|null $kind
 * @property string|null $address
 * @property string|null $maps_url
 * @property int $sort_order
 * @property bool $is_active
 * @property array<string, array<string, string>>|null $translation_status
 */
#[Fillable(['name', 'kind', 'address', 'maps_url', 'sort_order', 'is_active', 'translation_status'])]
class Location extends Model implements HasMedia, HasTranslatableFields
{
    use FlushesFrontendCache, HasTranslations, InteractsWithMedia, RegistersWebpConversions, Sortable;

    public const TRANSLATABLE = ['kind'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile()->acceptsMimeTypes(SiteSetting::IMAGE_MIMES);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addWebpConversion('lg', 1000, 'photo');
        $this->addWebpConversion('sm', 520, 'photo');
    }

    /**
     * @return array<string, mixed>
     */
    public function toFrontend(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'kind' => $this->translate('kind'),
            'address' => $this->address,
            'mapsUrl' => Links::safe($this->maps_url),
            'photo' => MediaPresenter::image($this->getFirstMedia('photo'), 'lg', 'sm', 1000, 520, $this->name),
        ];
    }
}
