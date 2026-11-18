<?php

namespace App\Models;

use App\Enums\DocStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\RfqItem;

class PurchaseRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'pr_no', 'pr_date', 'entity_name', 'fund_cluster', 'office_section',
        'responsibility_center_code', 'purpose', 'end_user', 'total_amount',
        'requested_by', 'approved_by', 'status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'pr_date' => 'date',
            'total_amount' => 'decimal:2',
            'status' => DocStatus::class,
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PrItem::class)->orderBy('sort_order');
    }

    public function rfqs(): HasMany
    {
        return $this->hasMany(Rfq::class)->orderBy('rfq_date')->orderBy('id');
    }

    /** How many PR item lines are on at least one (non-deleted) RFQ. */
    public function coverage(): array
    {
        $items = $this->items()->where('line_type', 'item');

        $total = (clone $items)->count();
        $covered = (clone $items)->whereIn(
            'id',
            RfqItem::query()->whereHas('rfq')->whereNotNull('pr_item_id')->select('pr_item_id')
        )->count();

        return ['total' => $total, 'covered' => $covered];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}