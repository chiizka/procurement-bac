<?php

namespace App\Models;

use App\Enums\DocStatus;
use App\Enums\LineType;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'abstract_id', 'po_no', 'po_date', 'twg_supplier_id',
        'supplier_name', 'supplier_address', 'supplier_tin', 'supplier_contact_no',
        'procurement_mode', 'place_of_delivery', 'delivery_term', 'date_of_delivery', 'payment_term',
        'warranty_security', 'deductions', 'total_amount', 'deductions_total', 'amount_due',
        'approver_name', 'approver_position', 'accountant_name',
        'status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'po_date' => 'date',
            'deductions' => 'array',
            'total_amount' => 'decimal:2',
            'deductions_total' => 'decimal:2',
            'amount_due' => 'decimal:2',
            'status' => DocStatus::class,
        ];
    }

    public function abstractOfQuotation(): BelongsTo
    {
        return $this->belongsTo(AbstractOfQuotation::class, 'abstract_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(TwgSupplier::class, 'twg_supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Procurement edits. BAC and Admin only see a PO once it is Created. */
    public function isViewableBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->status === DocStatus::Created || $user->hasRole(Role::Procurement, Role::Admin);
    }

    /**
     * The PO lines for one supplier: the RFQ lines priced with that supplier's TWG quotes.
     * Items the supplier did not quote are left out. Lot headers and notes are kept.
     *
     * @param  Collection<int, RfqItem>  $rfqItems
     * @return array{lines: list<array<string, mixed>>, total: float}
     */
    public static function buildLines(TwgSupplier $supplier, Collection $rfqItems): array
    {
        $prices = $supplier->quotes->keyBy('rfq_item_id');
        $lines = [];
        $total = 0.0;

        foreach ($rfqItems as $item) {
            if ($item->line_type === LineType::Item) {
                $quote = $prices->get($item->id);
                if (! $quote) {
                    continue;
                }
                $unitCost = (float) $quote->unit_price;
                $amount = round((float) $item->quantity * $unitCost, 2);
                $total += $amount;
                $lines[] = [
                    'rfq_item_id' => $item->id, 'line_type' => LineType::Item->value,
                    'stock_no' => $item->stock_no, 'unit' => $item->unit, 'description' => $item->description,
                    'quantity' => $item->quantity, 'unit_cost' => number_format($unitCost, 2, '.', ''),
                    'amount' => number_format($amount, 2, '.', ''),
                ];
            } else {
                $lines[] = [
                    'rfq_item_id' => $item->id, 'line_type' => $item->line_type->value,
                    'stock_no' => null, 'unit' => null, 'description' => $item->description,
                    'quantity' => null, 'unit_cost' => null, 'amount' => null,
                ];
            }
        }

        return ['lines' => $lines, 'total' => round($total, 2)];
    }
}
