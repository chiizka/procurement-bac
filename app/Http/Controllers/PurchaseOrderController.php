<?php

namespace App\Http\Controllers;

use App\Enums\DocStatus;
use App\Enums\Role;
use App\Models\AbstractOfQuotation;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\TwgSupplier;
use App\Support\PoOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    public function create(Request $request, AbstractOfQuotation $abstract)
    {
        $this->authorizeWrite($request);
        $this->ensureAwarded($abstract);
        if ($abstract->purchaseOrder) {
            return redirect()->route('purchase-orders.show', $abstract->purchaseOrder);
        }

        return view('purchase-orders.create', ['po' => null] + $this->formContext($abstract));
    }

    public function store(Request $request, AbstractOfQuotation $abstract)
    {
        $this->authorizeWrite($request);
        $po = DB::transaction(function () use ($request, $abstract) {
            $locked = AbstractOfQuotation::lockForUpdate()->findOrFail($abstract->id);
            $this->ensureAwarded($locked);
            if ($existing = $locked->purchaseOrder) {
                return $existing;
            }
            [$data, $built, $supplier] = $this->validated($request, $locked);
            $data['status'] = $request->input('action') === 'created' ? DocStatus::Created : DocStatus::Draft;
            $data['created_by'] = $request->user()->id;
            $po = $locked->purchaseOrder()->create($data);
            $po->items()->createMany($this->itemRows($built['lines']));

            return $po;
        });

        return redirect()->route('purchase-orders.show', $po)->with('status', 'Purchase Order saved.');
    }

    public function show(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorizeView($request, $purchaseOrder);

        return view('purchase-orders.show', ['po' => $purchaseOrder->load('items', 'abstractOfQuotation.twgReport.rfq')]);
    }

    public function printPreview(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorizeView($request, $purchaseOrder);

        return view('purchase-orders.print', ['po' => $purchaseOrder->load('items', 'abstractOfQuotation.twgReport.rfq')]);
    }

    public function edit(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorizeWrite($request);
        $purchaseOrder->load('items');

        return view('purchase-orders.edit', ['po' => $purchaseOrder] + $this->formContext($purchaseOrder->abstractOfQuotation));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorizeWrite($request);
        DB::transaction(function () use ($request, $purchaseOrder) {
            $locked = PurchaseOrder::lockForUpdate()->findOrFail($purchaseOrder->id);
            [$data, $built] = $this->validated($request, $locked->abstractOfQuotation, $locked);
            if ($locked->status === DocStatus::Created) {
                unset($data['po_no']);
            }
            $data['status'] = $locked->status === DocStatus::Created || $request->input('action') === 'created'
                ? DocStatus::Created : DocStatus::Draft;
            $data['updated_by'] = $request->user()->id;
            $locked->update($data);
            $locked->items()->delete();
            $locked->items()->createMany($this->itemRows($built['lines']));
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)->with('status', 'Purchase Order updated.');
    }

    public function destroy(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorizeWrite($request);
        $pr = $purchaseOrder->abstractOfQuotation->twgReport->rfq->purchase_request_id;
        DB::transaction(function () use ($purchaseOrder) {
            $locked = PurchaseOrder::lockForUpdate()->findOrFail($purchaseOrder->id);
            abort_unless($locked->status === DocStatus::Draft, 403, 'Only drafts can be deleted.');
            $locked->delete();
        });

        return redirect()->route('purchase-requests.show', $pr)->with('status', 'Purchase Order draft deleted.');
    }

    /** Only procurement writes POs. BAC and Admin are read-only. */
    private function authorizeWrite(Request $request): void
    {
        abort_unless($request->user()?->hasRole(Role::Procurement), 403);
    }

    /** Drafts are private to procurement (and Admin). Everyone else sees a PO once it is Created. */
    private function authorizeView(Request $request, PurchaseOrder $po): void
    {
        abort_unless($po->isViewableBy($request->user()), 403);
    }

    private function ensureAwarded(AbstractOfQuotation $abstract): void
    {
        abort_unless(
            $abstract->status === DocStatus::Created && $abstract->recommendation === 'award',
            403,
            'The Abstract must be Created with a recommendation to award before a Purchase Order can be made.'
        );
    }

    /** Everything the form needs: supplier choices, their priced lines, and master-list details. */
    private function formContext(AbstractOfQuotation $abstract): array
    {
        $abstract->load(['twgReport.rfq.purchaseRequest', 'twgReport.rfq.items', 'twgReport.suppliers.quotes']);
        $twg = $abstract->twgReport;
        $rfqItems = $twg->rfq->items;

        $master = Supplier::all()->keyBy(fn (Supplier $s) => mb_strtolower(trim($s->name)));
        $suppliers = [];
        foreach ($twg->suppliers as $supplier) {
            $match = $master->get(mb_strtolower(trim($supplier->name)));
            $built = PurchaseOrder::buildLines($supplier, $rfqItems);
            $suppliers[$supplier->id] = [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'quoted' => $supplier->quotes->isNotEmpty(),
                'address' => $match?->address,
                'tin' => $match?->tin,
                'contact_no' => $match?->contact_no,
                'lines' => $built['lines'],
                'total' => $built['total'],
            ];
        }

        // Default the awarded supplier to rank 1 in the Abstract, else the first supplier that quoted.
        $default = $twg->suppliers->first(fn ($s) => (int) (($abstract->evaluations ?? [])[$s->id]['rank'] ?? 0) === 1)
            ?? $twg->suppliers->first(fn ($s) => $s->quotes->isNotEmpty());

        return [
            'abstract' => $abstract,
            'twg' => $twg,
            'suppliers' => $suppliers,
            'defaultSupplierId' => $default?->id,
        ];
    }

    /** @return array{0: array<string, mixed>, 1: array{lines: list<array<string, mixed>>, total: float}, 2: TwgSupplier} */
    private function validated(Request $request, AbstractOfQuotation $abstract, ?PurchaseOrder $po = null): array
    {
        $creating = $request->input('action') === 'created' || $po?->status === DocStatus::Created;
        $abstract->loadMissing(['twgReport.rfq.items', 'twgReport.suppliers.quotes']);
        $twg = $abstract->twgReport;
        $needed = $creating ? 'required' : 'nullable';

        $data = $request->validate([
            'action' => ['required', Rule::in(['draft', 'created'])],
            'po_no' => $po?->status === DocStatus::Created
                ? ['exclude']
                : ['required', 'string', 'max:50', Rule::unique('purchase_orders', 'po_no')->ignore($po?->id)],
            'po_date' => ['required', 'date'],
            'twg_supplier_id' => ['required', Rule::in($twg->suppliers->pluck('id')->all())],
            'supplier_name' => ['required', 'string', 'max:255'],
            'supplier_address' => [$needed, 'string', 'max:255'],
            'supplier_tin' => ['nullable', 'string', 'max:50'],
            'supplier_contact_no' => ['nullable', 'string', 'max:100'],
            'procurement_mode' => ['required', 'string', 'max:255'],
            'place_of_delivery' => [$needed, Rule::in(PoOptions::places())],
            'delivery_term' => [$needed, Rule::in(PoOptions::deliveryTerms())],
            'date_of_delivery' => [$needed, 'string', 'max:100'],
            'payment_term' => [$needed, Rule::in(PoOptions::paymentTerms())],
            'warranty_security' => ['nullable', 'string', 'max:255'],
            'deductions' => ['nullable', 'array', 'max:10'],
            'deductions.*.label' => ['nullable', 'string', 'max:100'],
            'deductions.*.rate' => ['nullable', 'string', 'max:20'],
            'deductions.*.amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99', 'decimal:0,2'],
            'approver_name' => ['nullable', 'string', 'max:255'],
            'approver_position' => ['nullable', 'string', 'max:255'],
            'accountant_name' => ['nullable', 'string', 'max:255'],
        ]);
        unset($data['action']);

        /** @var TwgSupplier $supplier */
        $supplier = $twg->suppliers->firstWhere('id', (int) $data['twg_supplier_id']);
        $built = PurchaseOrder::buildLines($supplier, $twg->rfq->items);
        if ($creating && $built['total'] <= 0) {
            throw ValidationException::withMessages([
                'twg_supplier_id' => 'This supplier has no quoted items in the TWG report, so there is nothing to order.',
            ]);
        }

        // Deductions are typed by hand. Rows with no amount are dropped.
        $deductions = collect($data['deductions'] ?? [])
            ->filter(fn ($row) => filled($row['amount'] ?? null))
            ->map(fn ($row) => [
                'label' => trim((string) ($row['label'] ?? '')) ?: 'less tax:',
                'rate' => trim((string) ($row['rate'] ?? '')),
                'amount' => number_format((float) $row['amount'], 2, '.', ''),
            ])->values()->all();
        $deductionsTotal = round(array_sum(array_map(fn ($row) => (float) $row['amount'], $deductions)), 2);
        if ($deductionsTotal > $built['total']) {
            throw ValidationException::withMessages([
                'deductions' => 'The deductions are more than the total amount of the order.',
            ]);
        }

        $data['deductions'] = $deductions;
        $data['total_amount'] = $built['total'];
        $data['deductions_total'] = $deductionsTotal;
        $data['amount_due'] = round($built['total'] - $deductionsTotal, 2);

        return [$data, $built, $supplier];
    }

    /** @param list<array<string, mixed>> $lines */
    private function itemRows(array $lines): array
    {
        return array_map(fn ($line, $i) => $line + ['sort_order' => $i + 1], $lines, array_keys($lines));
    }
}
