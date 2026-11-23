<?php

namespace App\Http\Controllers;

use App\Enums\DocStatus;
use App\Enums\LineType;
use App\Enums\Role;
use App\Models\PurchaseRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Group PR = a new PR whose lots are made of existing solo PRs.
 *   - one lot_header row per lot, then the items of every PR in that lot
 *   - member PRs get group_id set and are locked
 *   - RFQs are then made from the group PR with the existing RFQ screens
 */
class GroupPurchaseRequestController extends Controller
{
    public function create()
    {
        $this->authorizeWrite();

        // Only solo, Created PRs with no RFQ of their own can be grouped.
        $available = PurchaseRequest::query()
            ->where('status', DocStatus::Created->value)
            ->where('is_group', false)
            ->whereNull('group_id')
            ->whereDoesntHave('rfqs')
            ->orderBy('pr_date')->orderBy('id')
            ->get(['id', 'pr_no', 'pr_date', 'office_section', 'purpose', 'total_amount'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'pr_no' => $p->pr_no,
                'date' => $p->pr_date->format('M d, Y'),
                'office' => $p->office_section,
                'purpose' => $p->purpose,
                'total' => (float) $p->total_amount,
            ])->values()->all();

        return view('purchase-requests.group-create', compact('available'));
    }

    public function store(Request $request)
    {
        $this->authorizeWrite();

        $data = $request->validate([
            'pr_no' => ['required', 'string', 'max:255', Rule::unique('purchase_requests', 'pr_no')],
            'pr_date' => ['required', 'date'],
            'entity_name' => ['required', 'string', 'max:255'],
            'fund_cluster' => ['nullable', 'string', 'max:255'],
            'office_section' => ['required', 'string', 'max:255'],
            'responsibility_center_code' => ['nullable', 'string', 'max:255'],
            'purpose' => ['required', 'string'],
            'end_user' => ['nullable', 'string', 'max:255'],
            'lots' => ['required', 'array', 'min:1'],
            'lots.*.name' => ['required', 'string', 'max:255'],
            'lots.*.pr_ids' => ['required', 'array', 'min:1'],
            'lots.*.pr_ids.*' => ['integer'],
        ], [
            'lots.required' => 'Add at least one lot.',
            'lots.*.name.required' => 'Every lot needs a name.',
            'lots.*.pr_ids.required' => 'Every lot needs at least one PR.',
        ]);

        $allIds = collect($data['lots'])->pluck('pr_ids')->flatten()->map(fn ($v) => (int) $v);

        if ($allIds->count() !== $allIds->unique()->count()) {
            throw ValidationException::withMessages(['lots' => 'A PR can only be in one lot.']);
        }

        $group = DB::transaction(function () use ($data, $allIds, $request) {
            // Lock the member rows so two people can't group the same PR at once.
            $sources = PurchaseRequest::query()
                ->whereIn('id', $allIds)
                ->lockForUpdate()
                ->with('items')
                ->get()
                ->keyBy('id');

            foreach ($allIds as $id) {
                $pr = $sources->get($id);

                if (! $pr
                    || $pr->status !== DocStatus::Created
                    || $pr->is_group
                    || $pr->group_id !== null
                    || $pr->rfqs()->exists()) {
                    throw ValidationException::withMessages([
                        'lots' => 'PR '.($pr?->pr_no ?? "#{$id}").' can no longer be grouped. Reload the page and try again.',
                    ]);
                }
            }

            $group = PurchaseRequest::create([
                'pr_no' => $data['pr_no'],
                'is_group' => true,
                'pr_date' => $data['pr_date'],
                'entity_name' => $data['entity_name'],
                'fund_cluster' => $data['fund_cluster'] ?? null,
                'office_section' => $data['office_section'],
                'responsibility_center_code' => $data['responsibility_center_code'] ?? null,
                'purpose' => $data['purpose'],
                'end_user' => $data['end_user'] ?? null,
                'status' => DocStatus::Created,
                'created_by' => $request->user()->id,
            ]);

            $sort = 0;
            $total = 0;

            foreach ($data['lots'] as $lot) {
                $group->items()->create([
                    'sort_order' => ++$sort,
                    'line_type' => LineType::LotHeader->value,
                    'description' => $lot['name'],
                ]);

                foreach ($lot['pr_ids'] as $id) {
                    foreach ($sources[(int) $id]->items as $item) {
                        // a member PR's own lot headers are dropped so lots don't nest
                        if ($item->line_type === LineType::LotHeader) {
                            continue;
                        }

                        $group->items()->create([
                            'source_item_id' => $item->id,
                            'sort_order' => ++$sort,
                            'line_type' => $item->line_type->value,
                            'stock_no' => $item->stock_no,
                            'unit' => $item->unit,
                            'description' => $item->description,
                            'quantity' => $item->quantity,
                            'unit_cost' => $item->unit_cost,
                            'amount' => $item->amount,
                        ]);

                        $total += $item->amount ?? 0;
                    }
                }
            }

            $group->update(['total_amount' => $total]);

            PurchaseRequest::whereIn('id', $allIds)->update(['group_id' => $group->id]);

            return $group;
        });

        return redirect()->route('purchase-requests.show', $group)
            ->with('status', 'Group PR created. Make the RFQs from here, one per lot.');
    }

    /** Ungroup: allowed only while the group PR has never had an RFQ. */
    public function destroy(PurchaseRequest $purchaseRequest)
    {
        $this->authorizeWrite();

        abort_unless($purchaseRequest->is_group, 404);
        abort_if(
            $purchaseRequest->rfqs()->withTrashed()->exists(),
            403,
            'This group PR already has RFQs, so it can no longer be ungrouped.'
        );

        DB::transaction(function () use ($purchaseRequest) {
            PurchaseRequest::where('group_id', $purchaseRequest->id)->update(['group_id' => null]);

            // hard delete: the group PR is only a derived copy, and this frees its PR No.
            $purchaseRequest->forceDelete(); // items cascade
        });

        return redirect()->route('purchase-requests.index')
            ->with('status', 'Group PR removed. Its PRs are solo again.');
    }

    private function authorizeWrite(): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        abort_unless($user?->hasRole(Role::Bac, Role::Admin), 403);
    }
}
