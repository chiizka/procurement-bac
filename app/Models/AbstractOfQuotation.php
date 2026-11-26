<?php

namespace App\Models;

use App\Enums\DocStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AbstractOfQuotation extends Model
{
    protected $table = 'abstracts';

    protected $fillable = [
        'twg_report_id', 'abstract_no', 'abstract_date', 'project_name',
        'project_location', 'implementing_office', 'procurement_mode', 'abc',
        'bid_deadline_at', 'evaluations', 'recommendation', 'award_description',
        'award_amount', 'failure_reason', 'signatories', 'hope_name',
        'status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'abstract_date' => 'date', 'bid_deadline_at' => 'datetime',
            'abc' => 'decimal:2', 'award_amount' => 'decimal:2',
            'evaluations' => 'array', 'signatories' => 'array', 'status' => DocStatus::class,
        ];
    }

    public function bidAmountFor(TwgSupplier $supplier): ?float
    {
        $evaluation = ($this->evaluations ?? [])[$supplier->id] ?? [];
        if (array_key_exists('bid_amount', $evaluation)) {
            return $evaluation['bid_amount'] === null ? null : (float) $evaluation['bid_amount'];
        }

        // Abstracts saved before editable amounts retain their original TWG totals.
        return $supplier->grandTotal($this->twgReport->rfq->items);
    }

    public function twgReport(): BelongsTo
    {
        return $this->belongsTo(TwgReport::class);
    }

    public function purchaseOrder(): HasOne
    {
        return $this->hasOne(PurchaseOrder::class, 'abstract_id');
    }
}
