<?php

namespace App\Models\Concerns;

use App\Support\FrontendData;

trait FlushesFrontendCache
{
    public static function bootFlushesFrontendCache(): void
    {
        static::saved(fn () => FrontendData::flush());
        static::deleted(fn () => FrontendData::flush());
    }
}
