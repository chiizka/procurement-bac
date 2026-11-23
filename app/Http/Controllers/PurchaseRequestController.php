<?php

namespace App\Http\Controllers;

use App\Enums\DocStatus;
use App\Enums\LineType;
use App\Enums\Role;
use App\Http\Requests\StorePurchaseRequestRequest;
use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PurchaseRequestController extends Controller
{
    private const HEADER_FIELDS = [
        'pr_date', 'entity_name', 'fund_cluster', 'office_section',
        'responsibility_center_code', 'purpose', 'end_user',
        'requested_by', 'approved_by',
    ];

    public function index()
    {
        $purchaseRequests = PurchaseRequest::withCount([
                'items',
                'rfqs',
                'rfqs as rfqs_created_count' => fn ($q) => $q->where('status', 'created'),
            ])
            ->whereNull('group_id') // grouped PRs are shown inside their group PR
            ->latest('pr_date')->latest('id')->paginate(15);

        $purchaseRequests->getCollection()
            ->where('is_group', true)
            ->load('items.sourceItem.purchaseRequest:id,pr_no');

        return view('purchase-requests.index', compact('purchaseRequests'));
    }

    public function create()
    {
        $this->authorizeWrite();

        return view('purchase-requests.create');
    }

    public function store(StorePurchaseRequestRequest $request)
    {
        $pr = DB::transaction(function () use ($request) {
            $pr = PurchaseRequest::create(array_merge(
                $request->safe()->only(self::HEADER_FIELDS),
                [
                    'pr_no' => $request->input('pr_no'),
                    'status' => $request->input('action') === 'created'
                        ? DocStatus::Created : DocStatus::Draft,
                    'created_by' => $request->user()->id,
                ]
            ));

            $this->syncItems($pr, $request->input('items', []));

            return $pr;
        });

        return redirect()->route('purchase-requests.show', $pr)
            ->with('status', $pr->status === DocStatus::Created ? 'PR created.' : 'PR saved as draft.');
    }

    public function show(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->load([
            'items',
            'group',    // the group PR this PR belongs to (if any)
            'members',  // the PRs inside this PR (if it is a group PR)
            'rfqs' => fn ($q) => $q->withCount([
                'items as item_count' => fn ($i) => $i->where('line_type', 'item'),
            ]),
        ]);

        return view('purchase-requests.show', ['pr' => $purchaseRequest]);
    }

    public function printPreview(PurchaseRequest $purchaseRequest)
    {
        return view('purchase-requests.printPR', ['pr' => $purchaseRequest->load('items')]);
    }

    public function edit(PurchaseRequest $purchaseRequest)
    {
        $this->authorizeWrite();
        $this->ensureEditable($purchaseRequest);
        $purchaseRequest->load('items');

        return view('purchase-requests.edit', ['pr' => $purchaseRequest]);
    }

    public function update(StorePurchaseRequestRequest $request, PurchaseRequest $purchaseRequest)
    {
        $this->ensureEditable($purchaseRequest);

        DB::transaction(function () use ($request, $purchaseRequest) {
            $locked = $purchaseRequest->status === DocStatus::Created;

            $data = $request->safe()->only(self::HEADER_FIELDS);
            $data['updated_by'] = $request->user()->id;
            $data['status'] = ($locked || $request->input('action') === 'created')
                ? DocStatus::Created : DocStatus::Draft;

            // The PR number is locked once the PR is Created.
            if (! $locked) {
                $data['pr_no'] = $request->input('pr_no');
            }

            $purchaseRequest->update($data);
            $this->syncItems($purchaseRequest, $request->input('items', []));
        });

        return redirect()->route('purchase-requests.show', $purchaseRequest)
            ->with('status', 'PR updated.');
    }

    public function destroy(PurchaseRequest $purchaseRequest)
    {
        $this->authorizeWrite();
        abort_unless($purchaseRequest->status === DocStatus::Draft, 403, 'Only drafts can be deleted.');

        $purchaseRequest->delete(); // soft delete

        return redirect()->route('purchase-requests.index')->with('status', 'Draft deleted.');
    }

    private function authorizeWrite(): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        abort_unless($user?->hasRole(Role::Bac, Role::Admin), 403);
    }

    /**
     * A PR that was grouped is locked (its items were copied into the group PR),
     * and a group PR can't be edited once it has RFQs, because syncItems()
     * deletes and recreates every item, which would break the RFQ links.
     */
    private function ensureEditable(PurchaseRequest $pr): void
    {
        abort_if($pr->group_id !== null, 403, 'This PR is in a group PR and is locked.');
        abort_if($pr->is_group && $pr->rfqs()->exists(), 403, 'This group PR already has RFQs and can no longer be edited.');
    }

    private function syncItems(PurchaseRequest $pr, array $rows): void
    {
        $pr->items()->delete();
        $total = 0;

        foreach (array_values($rows) as $i => $row) {
            $isItem = $row['line_type'] === LineType::Item->value;
            $qty = $isItem ? ($row['quantity'] ?? null) : null;
            $cost = $isItem ? ($row['unit_cost'] ?? null) : null;
            $amount = ($qty !== null && $cost !== null) ? round($qty * $cost, 2) : null;
            $total += $amount ?? 0;

            $pr->items()->create([
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

        $pr->update(['total_amount' => $total]);
    }
}