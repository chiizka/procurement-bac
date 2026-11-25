<?php

namespace App\Http\Controllers;

use App\Enums\DocStatus;
use App\Enums\Role;
use App\Models\AbstractOfQuotation;
use App\Models\TwgReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AbstractOfQuotationController extends Controller
{
    public function create(Request $request, TwgReport $twgReport)
    {
        $this->authorizeWrite($request);
        abort_unless($twgReport->status === DocStatus::Created, 403, 'Create the TWG report before starting an Abstract.');
        if ($twgReport->abstractOfQuotation) {
            return redirect()->route('abstracts.show', $twgReport->abstractOfQuotation);
        }
        $twgReport->load(['rfq.purchaseRequest', 'rfq.items', 'suppliers.quotes']);
        return view('abstracts.create', [
            'twg' => $twgReport, 'abstract' => null,
            'lastSignatories' => AbstractOfQuotation::latest('id')->value('signatories'),
            'lastHope' => AbstractOfQuotation::latest('id')->value('hope_name'),
        ]);
    }

    public function store(Request $request, TwgReport $twgReport)
    {
        $this->authorizeWrite($request);
        $abstract = DB::transaction(function () use ($request, $twgReport) {
            $twg = TwgReport::lockForUpdate()->findOrFail($twgReport->id);
            abort_unless($twg->status === DocStatus::Created, 403, 'Create the TWG report before starting an Abstract.');
            if ($existing = $twg->abstractOfQuotation) return $existing;
            $data = $this->validated($request, $twg);
            $data['status'] = $request->input('action') === 'created' ? DocStatus::Created : DocStatus::Draft;
            $data['created_by'] = $request->user()->id;
            return $twg->abstractOfQuotation()->create($data);
        });
        return redirect()->route('abstracts.show', $abstract)->with('status', 'Abstract saved.');
    }

    public function show(AbstractOfQuotation $abstract)
    {
        return view('abstracts.show', $this->context($abstract));
    }

    public function printPreview(AbstractOfQuotation $abstract)
    {
        return view('abstracts.print', $this->context($abstract));
    }

    public function edit(Request $request, AbstractOfQuotation $abstract)
    {
        $this->authorizeWrite($request);
        return view('abstracts.edit', $this->context($abstract) + ['lastSignatories' => null, 'lastHope' => null]);
    }

    public function update(Request $request, AbstractOfQuotation $abstract)
    {
        $this->authorizeWrite($request);
        DB::transaction(function () use ($request, $abstract) {
            $locked = AbstractOfQuotation::lockForUpdate()->findOrFail($abstract->id);
            $data = $this->validated($request, $locked->twgReport, $locked);
            if ($locked->status === DocStatus::Created) unset($data['abstract_no']);
            $data['status'] = $locked->status === DocStatus::Created || $request->input('action') === 'created'
                ? DocStatus::Created : DocStatus::Draft;
            $data['updated_by'] = $request->user()->id;
            $locked->update($data);
        });
        return redirect()->route('abstracts.show', $abstract)->with('status', 'Abstract updated.');
    }

    public function destroy(Request $request, AbstractOfQuotation $abstract)
    {
        $this->authorizeWrite($request);
        $pr = $abstract->twgReport->rfq->purchase_request_id;
        DB::transaction(function () use ($abstract) {
            $locked = AbstractOfQuotation::lockForUpdate()->findOrFail($abstract->id);
            abort_unless($locked->status === DocStatus::Draft, 403, 'Only drafts can be deleted.');
            $locked->delete();
        });
        return redirect()->route('purchase-requests.show', $pr)->with('status', 'Abstract draft deleted.');
    }

    private function context(AbstractOfQuotation $abstract): array
    {
        $abstract->load(['twgReport.rfq.purchaseRequest', 'twgReport.rfq.items', 'twgReport.suppliers.quotes']);
        return ['abstract' => $abstract, 'twg' => $abstract->twgReport];
    }

    private function authorizeWrite(Request $request): void
    {
        abort_unless($request->user()?->hasRole(Role::Bac, Role::Admin), 403);
    }

    private function validated(Request $request, TwgReport $twg, ?AbstractOfQuotation $abstract = null): array
    {
        $creating = $request->input('action') === 'created' || $abstract?->status === DocStatus::Created;
        $supplierIds = $twg->suppliers()->pluck('id')->map(fn ($id) => (string) $id)->all();
        $data = $request->validate([
            'action' => ['required', Rule::in(['draft', 'created'])],
            'abstract_no' => $abstract?->status === DocStatus::Created ? ['exclude'] : ['required', 'string', 'max:255', Rule::unique('abstracts', 'abstract_no')->ignore($abstract?->id)],
            'abstract_date' => ['required', 'date'],
            'project_name' => ['required', 'string'],
            'project_location' => [$creating ? 'required' : 'nullable', 'string', 'max:255'],
            'implementing_office' => [$creating ? 'required' : 'nullable', 'string', 'max:255'],
            'bid_deadline_at' => ['nullable', 'date'],
            'evaluations' => ['nullable', 'array:'.implode(',', $supplierIds)],
            'evaluations.*.received_at' => ['nullable', 'date'],
            'evaluations.*.eligibility' => ['nullable', Rule::in(['pass', 'fail'])],
            'evaluations.*.technical' => ['nullable', Rule::in(['pass', 'fail'])],
            'evaluations.*.financial' => ['nullable', Rule::in(['pass', 'fail'])],
            'evaluations.*.bid_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99', 'decimal:0,2'],
            'evaluations.*.rank' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'evaluations.*.remarks' => ['nullable', 'string', 'max:2000'],
            'recommendation' => [$creating ? 'required' : 'nullable', Rule::in(['award', 'failure'])],
            'award_description' => ['exclude_unless:recommendation,award', 'nullable', Rule::requiredIf($creating && $request->input('recommendation') === 'award'), 'string'],
            'award_amount' => ['exclude_unless:recommendation,award', 'nullable', Rule::requiredIf($creating && $request->input('recommendation') === 'award'), 'numeric', 'min:0', 'max:999999999999.99', 'decimal:0,2'],
            'failure_reason' => ['exclude_unless:recommendation,failure', 'nullable', Rule::requiredIf($creating && $request->input('recommendation') === 'failure'), Rule::in(['ineligible', 'noncompliant'])],
            'signatories' => ['nullable', 'array', 'max:20'],
            'signatories.*.name' => ['nullable', 'string', 'max:255'],
            'signatories.*.position' => ['nullable', 'string', 'max:255'],
            'signatories.*.role' => ['required', Rule::in(['member', 'vice_chairperson', 'chairperson'])],
            'hope_name' => ['nullable', 'string', 'max:255'],
        ]);
        unset($data['action']);
        // Prefill from TWG, then keep Abstract-specific bid amounts without changing source quotes.
        $data['procurement_mode'] = $twg->procurement_mode;
        $data['abc'] = $twg->abc;
        $submitted = $data['evaluations'] ?? [];
        $twg->loadMissing(['rfq.items', 'suppliers.quotes']);
        $data['evaluations'] = $twg->suppliers->mapWithKeys(function ($supplier) use ($submitted, $abstract, $twg) {
            $row = collect($submitted[$supplier->id] ?? [])->only([
                'received_at', 'eligibility', 'technical', 'financial', 'rank', 'remarks', 'bid_amount',
            ])->all();
            if (! array_key_exists('bid_amount', $row)) {
                $previous = ($abstract?->evaluations ?? [])[$supplier->id] ?? [];
                $row['bid_amount'] = array_key_exists('bid_amount', $previous)
                    ? $previous['bid_amount'] : $supplier->grandTotal($twg->rfq->items);
            }
            if ($row['bid_amount'] !== null) $row['bid_amount'] = number_format((float) $row['bid_amount'], 2, '.', '');
            return [$supplier->id => $row];
        })->all();
        $data['signatories'] = collect($data['signatories'] ?? [])->map(fn ($row) => collect($row)->only(['name', 'position', 'role'])->all())->values()->all();
        if (($data['recommendation'] ?? null) !== 'award') {
            $data['award_description'] = null;
            $data['award_amount'] = null;
        }
        if (($data['recommendation'] ?? null) !== 'failure') $data['failure_reason'] = null;
        return $data;
    }
}
