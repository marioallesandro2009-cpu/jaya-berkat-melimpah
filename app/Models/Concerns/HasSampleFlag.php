<?php

namespace App\Models\Concerns;

/**
 * is_sample: a seeded DUMMY row, shown as "Contoh" in the admin until it is saved
 * there (App\Filament\Support\Sample clears it). Models list is_sample in Fillable.
 */
trait HasSampleFlag
{
    public function initializeHasSampleFlag(): void
    {
        $this->mergeCasts(['is_sample' => 'boolean']);
    }
}
