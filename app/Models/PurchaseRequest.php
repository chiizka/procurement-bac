<?php

namespace App\Models;

use App\Enums\DocStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\RfqItem;
use App\Enums\LineType;
use Illuminate\Support\Collection;

class PurchaseRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'pr_no', 'pr_date', 'entity_name', 'fund_cluster', 'office_section',
        'responsibility_center_code', 'purpose', 'end_user', 'total_amount',
        'requested_by', 'approved_by', 'requested_by_position', 'certified_funds_available', 'certified_funds_available_position', 'approved_by_position', 'status', 'created_by', 'updated_by',
        'is_group', 'group_id',
    ];

    protected function casts(): array
    {
        return [
            'pr_date' => 'date',
            'total_amount' => 'decimal:2',
            'status' => DocStatus::class,
            'is_group' => 'boolean'
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

    public function group(): BelongsTo
    {
        return $this->belongsTo(self::class, 'group_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(self::class, 'group_id')->orderBy('pr_no');
    }

    /** Lots of a group PR: [['name' => ..., 'total' => ..., 'prs' => [id => pr_no]], ...] */
    public function lots(): Collection
    {
        $lots = collect();
        $current = null;

        foreach ($this->items as $item) {
            if ($item->line_type === LineType::LotHeader) {
                if ($current) {
                    $lots->push($current);
                }
                $current = ['name' => $item->description, 'total' => 0.0, 'prs' => [], 'item_ids' => [$item->id]];
                continue;
            }

            if (! $current) {
                continue;
            }

            $current['item_ids'][] = $item->id;
            $current['total'] += (float) ($item->amount ?? 0);

            $source = $item->sourceItem?->purchaseRequest;
            if ($source) {
                $current['prs'][$source->id] = $source->pr_no;
            }
        }

        if ($current) {
            $lots->push($current);
        }

        return $lots;
    }
}