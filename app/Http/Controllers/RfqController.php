<?php

namespace App\Http\Controllers;

use App\Enums\DocStatus;
use App\Enums\LineType;
use App\Enums\Role;
use App\Http\Requests\StoreRfqRequest;
use App\Models\PurchaseRequest;
use App\Models\Rfq;
use App\Models\RfqItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RfqController extends Controller
{
    private const HEADER_FIELDS = [
        'rfq_no', 'lot_label', 'rfq_date', 'deadline_at', 'purpose',
        'bac_chairperson', 'contract_duration', 'delivery_area',
    ];

    // Routes (see routes/web.php):
    //   GET  purchase-requests/{pr}/rfqs/create   create
    //   POST purchase-requests/{pr}/rfqs          store
    //   GET  rfqs/{rfq}  /edit  PUT/PATCH  DELETE
    //   GET  rfqs/{rfq}/print                     printPreview

    public function create(PurchaseRequest $purchaseRequest)
    {
        $this->authorizeWrite();
        $this->ensureCreatedPr($purchaseRequest);
        $purchaseRequest->load('items');

        return view('rfqs.create', [
            'pr' => $purchaseRequest,
            'rfq' => null,
            'usage' => $this->usage($purchaseRequest),
        ]);
    }

    public function store(StoreRfqRequest $request, PurchaseRequest $purchaseRequest)
    {
        $this->authorizeWrite();
        $this->ensureCreatedPr($purchaseRequest);
        $this->ensureCreatedPr($purchaseRequest);

        $rfq = DB::transaction(function () use ($request, $purchaseRequest) {
            $rfq = $purchaseRequest->rfqs()->create(array_merge(
                $request->safe()->only(self::HEADER_FIELDS),
                [
                    'status' => $request->input('action') === 'created'
                        ? DocStatus::Created : DocStatus::Draft,
                    'created_by' => $request->user()->id,
                ]
            ));

            $this->syncItems($rfq, $request->input('items', []));

            return $rfq;
        });

        return redirect()->route('rfqs.show', $rfq)
            ->with('status', $rfq->status === DocStatus::Created ? 'RFQ created.' : 'RFQ saved as draft.');
    }

    public function show(Rfq $rfq)
    {
        $rfq->load(['items', 'purchaseRequest', 'twgReport']);

        return view('rfqs.show', compact('rfq'));
    }

    public function printPreview(Rfq $rfq)
    {
        return view('rfqs.printRFQ', ['rfq' => $rfq->load(['items', 'purchaseRequest'])]);
    }

    public function edit(Rfq $rfq)
    {
        $this->authorizeWrite();
        abort_if($rfq->twgReport()->exists(), 403, 'This RFQ already has a TWG report and can no longer be edited.');
        $rfq->load('items');
        $pr = $rfq->purchaseRequest->load('items');

        return view('rfqs.edit', [
            'rfq' => $rfq,
            'pr' => $pr,
            'usage' => $this->usage($pr, $rfq),
        ]);
    }

    public function update(StoreRfqRequest $request, Rfq $rfq)
    {
        $this->authorizeWrite();
        abort_if($rfq->twgReport()->exists(), 403, '...');
        abort_if($rfq->twgReport()->exists(), 403, 'This RFQ already has a TWG report and can no longer be edited.');

        DB::transaction(function () use ($request, $rfq) {
            $locked = $rfq->status === DocStatus::Created;

            $data = $request->safe()->only(self::HEADER_FIELDS);
            $data['updated_by'] = $request->user()->id;
            $data['status'] = ($locked || $request->input('action') === 'created')
                ? DocStatus::Created : DocStatus::Draft;

            // The RFQ number is locked once the RFQ is Created.
            if ($locked) {
                unset($data['rfq_no']);
            }

            $rfq->update($data);
            $this->syncItems($rfq, $request->input('items', []));
        });

        return redirect()->route('rfqs.show', $rfq)->with('status', 'RFQ updated.');
    }

    public function destroy(Rfq $rfq)
    {
        $this->authorizeWrite();
        abort_unless($rfq->status === DocStatus::Draft, 403, 'Only drafts can be deleted.');

        $pr = $rfq->purchaseRequest;
        $rfq->delete(); // soft delete

        return redirect()->route('purchase-requests.show', $pr)->with('status', 'RFQ draft deleted.');
    }

    private function authorizeWrite(): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        abort_unless($user?->hasRole(Role::Bac, Role::Admin), 403);
    }

    private function ensureCreatedPr(PurchaseRequest $pr): void
    {
        abort_unless($pr->status === DocStatus::Created, 403, 'RFQs can only be made from a Created PR.');
        abort_if($pr->group_id !== null, 403, 'This PR is in a group. Make the RFQ from the group PR.');
    }

    /**
     * For the picker: which RFQ numbers already contain each PR item.
     * Returns [pr_item_id => ['601-2026', ...]]. Soft-deleted RFQs are ignored.
     */
    private function usage(PurchaseRequest $pr, ?Rfq $except = null): array
    {
        return RfqItem::query()
            ->whereIn('pr_item_id', $pr->items->pluck('id'))
            ->when($except, fn ($q) => $q->where('rfq_id', '!=', $except->id))
            ->whereHas('rfq')
            ->with('rfq:id,rfq_no')
            ->get()
            ->groupBy('pr_item_id')
            ->map(fn ($g) => $g->pluck('rfq.rfq_no')->unique()->values()->all())
            ->all();
    }

    private function syncItems(Rfq $rfq, array $rows): void
    {
        $rfq->items()->delete();
        $total = 0;

        foreach (array_values($rows) as $i => $row) {
            $isItem = $row['line_type'] === LineType::Item->value;
            $qty = $isItem ? ($row['quantity'] ?? null) : null;
            $cost = $isItem ? ($row['unit_cost'] ?? null) : null;
            $amount = ($qty !== null && $cost !== null) ? round($qty * $cost, 2) : null;
            $total += $amount ?? 0;

            $rfq->items()->create([
                'pr_item_id' => $row['pr_item_id'] ?? null,
                'sort_order' => $i + 1,
                'line_type' => $row['line_type'],
                'stock_no' => $row['stock_no'] ?? null,
                'unit' => $row['unit'] ?? null,
                'description' => $row['description'],
                'quantity' => $qty,
                'unit_cost' => $cost,
                'amount' => $amount,
            ]);
        }

        $rfq->update(['total_amount' => $total]);
    }
}
