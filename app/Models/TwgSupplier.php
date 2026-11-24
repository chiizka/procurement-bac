<?php

namespace App\Models;

use App\Enums\LineType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TwgSupplier extends Model
{
    protected $fillable = ['twg_report_id', 'sort_order', 'name', 'remarks'];

    public function report(): BelongsTo
    {
        return $this->belongsTo(TwgReport::class, 'twg_report_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(TwgQuote::class);
    }

    /**
     * Grand total of this supplier over the RFQ's item lines, or null when the
     * supplier quoted nothing (the form prints "-").
     *
     * @param  Collection<int, RfqItem>  $rfqItems
     */
    public function grandTotal(Collection $rfqItems): ?float
    {
        if ($this->quotes->isEmpty()) {
            return null;
        }

        $prices = $this->quotes->keyBy('rfq_item_id');
        $total = 0.0;

        foreach ($rfqItems as $item) {
            if ($item->line_type !== LineType::Item) {
                continue;
            }

            $quote = $prices->get($item->id);
            if ($quote) {
                $total += round((float) $item->quantity * (float) $quote->unit_price, 2);
            }
        }

        return round($total, 2);
    }
}
