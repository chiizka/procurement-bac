<?php

namespace App\Models;

use App\Enums\LineType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrItem extends Model
{
    protected $fillable = [
        'purchase_request_id', 'sort_order', 'line_type', 'stock_no',
        'unit', 'description', 'quantity', 'unit_cost', 'amount', 'source_item_id',
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

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function sourceItem(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_item_id');
    }
}