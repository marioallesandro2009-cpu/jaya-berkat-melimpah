<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row of the cut_species table: a cut a species is offered in, and whether that cut is really
 * sold as a product (available_as_product). The admin edits these rows as a list on the species.
 *
 * @property int $id
 * @property int $species_id
 * @property int $cut_id
 * @property bool $available_as_product
 * @property int $sort_order
 */
#[Fillable(['species_id', 'cut_id', 'available_as_product', 'sort_order'])]
class SpeciesCut extends Model
{
    protected $table = 'cut_species';

    protected function casts(): array
    {
        return ['available_as_product' => 'boolean', 'sort_order' => 'integer'];
    }

    /**
     * @return BelongsTo<Species, $this>
     */
    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class, 'species_id');
    }

    /**
     * @return BelongsTo<Cut, $this>
     */
    public function cut(): BelongsTo
    {
        return $this->belongsTo(Cut::class, 'cut_id');
    }
}
