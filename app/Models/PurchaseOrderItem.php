<?php

namespace App\Models;

use App\Enums\LineType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id', 'rfq_item_id', 'sort_order', 'line_type', 'stock_no',
        'unit', 'description', 'quantity', 'unit_cost', 'amount',
    ];

    protected function casts(): array
    {
        return [
            'line_type' => LineType::class,
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
