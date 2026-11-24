<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwgQuote extends Model
{
    protected $fillable = ['twg_supplier_id', 'rfq_item_id', 'unit_price'];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(TwgSupplier::class, 'twg_supplier_id');
    }

    public function rfqItem(): BelongsTo
    {
        return $this->belongsTo(RfqItem::class);
    }
}
