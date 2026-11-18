<?php

namespace App\Models;

use App\Enums\DocStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rfq extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_request_id', 'rfq_no', 'lot_label', 'rfq_date', 'deadline_at',
        'purpose', 'bac_chairperson', 'contract_duration', 'delivery_area',
        'total_amount', 'status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'rfq_date' => 'date',
            'deadline_at' => 'datetime',
            'total_amount' => 'decimal:2',
            'status' => DocStatus::class,
        ];
    }

    public function purchaseRequest(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RfqItem::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
