<?php

namespace App\Http\Controllers;

use App\Enums\DocStatus;
use App\Enums\LineType;
use App\Enums\Role;
use App\Models\Rfq;
use App\Models\TwgReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TwgReportController extends Controller
{
    // Routes (see routes/web.php):
    //   GET  rfqs/{rfq}/twg-reports/create   create
    //   POST rfqs/{rfq}/twg-reports          store
    //   GET  twg-reports/{twg_report}  /edit  PUT/PATCH  DELETE

    public function create(Rfq $rfq)
    {
        $this->authorizeWrite();
        $this->ensureCreatedRfq($rfq);

        if ($rfq->twgReport) {
            return redirect()->route('twg-reports.show', $rfq->twgReport)
                ->with('status', 'This RFQ already has a TWG report.');
        }

        $rfq->load(['items', 'purchaseRequest']);

        return view('twg-reports.create', [
            'rfq' => $rfq,
            'twg' => null,
            // the TWG members rarely change, so start from the last report's signatories
            'lastSignatories' => TwgReport::latest('id')->value('signatories'),
        ]);
    }

    public function store(Request $request, Rfq $rfq)
    {
        $this->authorizeWrite();
        $this->ensureCreatedRfq($rfq);

        if ($rfq->twgReport) {
            return redirect()->route('twg-reports.show', $rfq->twgReport)
                ->with('status', 'This RFQ already has a TWG report.');
        }

        $data = $this->clean($request->validate($this->rules($request, null)));
        $created = $request->input('action') === 'created';

        $twg = DB::transaction(function () use ($data, $rfq, $request, $created) {
            $twg = $rfq->twgReport()->create([
                'twg_no' => $data['twg_no'],
                'twg_date' => $data['twg_date'],
                'project_name' => $data['project_name'],
                'abc' => $data['abc'],
                'procurement_mode' => $data['procurement_mode'],
                'philgeps_ref' => $data['philgeps_ref'] ?? null,
                'philgeps_posted' => $data['philgeps_posted'] ?? null,
                'criteria' => $data['criteria'],
                'findings' => $data['findings'],
                'signatories' => $data['signatories'],
                'status' => $created ? DocStatus::Created : DocStatus::Draft,
                'created_by' => $request->user()->id,
            ]);

            $this->syncSuppliers($twg, $rfq, $data['suppliers'] ?? []);

            return $twg;
        });

        return redirect()->route('twg-reports.show', $twg)
            ->with('status', $created ? 'TWG report created.' : 'TWG report saved as draft.');
    }

    public function show(TwgReport $twgReport)
    {
        $twgReport->load(['rfq.items', 'rfq.purchaseRequest', 'suppliers.quotes']);

        return view('twg-reports.show', ['twg' => $twgReport]);
    }

    public function printPreview(TwgReport $twgReport)
    {
        return view('twg-reports.printTWG', [
            'twg' => $twgReport->load(['rfq.items', 'suppliers.quotes']),
        ]);
    }

    public function edit(TwgReport $twgReport)
    {
        $this->authorizeWrite();
        $twgReport->load(['rfq.items', 'rfq.purchaseRequest', 'suppliers.quotes']);

        return view('twg-reports.edit', [
            'twg' => $twgReport,
            'rfq' => $twgReport->rfq,
            'lastSignatories' => null,
        ]);
    }

    public function update(Request $request, TwgReport $twgReport)
    {
        $this->authorizeWrite();

        $data = $this->clean($request->validate($this->rules($request, $twgReport)));

        DB::transaction(function () use ($data, $request, $twgReport) {
            $locked = $twgReport->status === DocStatus::Created;

            $payload = [
                'twg_date' => $data['twg_date'],
                'project_name' => $data['project_name'],
                'abc' => $data['abc'],
                'procurement_mode' => $data['procurement_mode'],
                'philgeps_ref' => $data['philgeps_ref'] ?? null,
                'philgeps_posted' => $data['philgeps_posted'] ?? null,
                'criteria' => $data['criteria'],
                'findings' => $data['findings'],
                'signatories' => $data['signatories'],
                'updated_by' => $request->user()->id,
                'status' => ($locked || $request->input('action') === 'created')
                    ? DocStatus::Created : DocStatus::Draft,
            ];

            // The report number is locked once the report is Created.
            if (! $locked) {
                $payload['twg_no'] = $data['twg_no'];
            }

            $twgReport->update($payload);
            $this->syncSuppliers($twgReport, $twgReport->rfq()->with('items')->first(), $data['suppliers'] ?? []);
        });

        return redirect()->route('twg-reports.show', $twgReport)->with('status', 'TWG report updated.');
    }

    public function destroy(TwgReport $twgReport)
    {
        $this->authorizeWrite();
        abort_unless($twgReport->status === DocStatus::Draft, 403, 'Only drafts can be deleted.');

        $rfq = $twgReport->rfq;
        $twgReport->delete(); // hard delete; suppliers and quotes cascade

        return redirect()->route('rfqs.show', $rfq)->with('status', 'TWG draft deleted.');
    }

    private function rules(Request $request, ?TwgReport $twg): array
    {
        $creating = $request->input('action') === 'created';

        return [
            'twg_no' => ['required', 'string', 'max:255', Rule::unique('twg_reports', 'twg_no')->ignore($twg?->id)],
            'twg_date' => ['required', 'date'],
            'project_name' => ['required', 'string'],
            'abc' => ['required', 'numeric', 'min:0'],
            'procurement_mode' => ['required', 'string', 'max:255'],
            'philgeps_ref' => ['nullable', 'string', 'max:255'],
            'philgeps_posted' => ['nullable', 'string', 'max:255'],
            'criteria' => ['required', 'string', 'max:255'],

            // a report can be saved as a draft without suppliers, but not created
            'suppliers' => [$creating ? 'required' : 'nullable', 'array', $creating ? 'min:1' : 'min:0'],
            'suppliers.*.name' => ['required', 'string', 'max:255'],
            'suppliers.*.remarks' => ['nullable', 'string', 'max:255'],
            'suppliers.*.quotes' => ['nullable', 'array'],
            'suppliers.*.quotes.*' => ['nullable', 'numeric', 'min:0'],

            'findings' => ['nullable', 'array'],
            'findings.*.scope' => ['nullable', 'string', 'max:255'],
            'findings.*.supplier' => ['nullable', 'string', 'max:255'],
            'findings.*.text' => ['nullable', 'string'],

            'signatories' => ['nullable', 'array'],
            'signatories.*.group' => ['required', Rule::in(['evaluated', 'reviewed'])],
            'signatories.*.name' => ['nullable', 'string', 'max:255'],
            'signatories.*.position' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** Drop the blank rows the form leaves behind. */
    private function clean(array $data): array
    {
        $data['findings'] = collect($data['findings'] ?? [])
            ->filter(fn ($f) => filled($f['scope'] ?? null) || filled($f['supplier'] ?? null) || filled($f['text'] ?? null))
            ->values()->all();

        $data['signatories'] = collect($data['signatories'] ?? [])
            ->filter(fn ($s) => filled($s['name'] ?? null))
            ->values()->all();

        return $data;
    }

    private function syncSuppliers(TwgReport $twg, Rfq $rfq, array $rows): void
    {
        $itemIds = $rfq->items
            ->filter(fn ($i) => $i->line_type === LineType::Item)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $twg->suppliers()->delete(); // quotes cascade

        foreach (array_values($rows) as $i => $row) {
            $supplier = $twg->suppliers()->create([
                'sort_order' => $i + 1,
                'name' => $row['name'],
                'remarks' => $row['remarks'] ?? null,
            ]);

            foreach (($row['quotes'] ?? []) as $itemId => $price) {
                // blank price = "No Quotation"; ignore ids that are not item lines of this RFQ
                if ($price === null || $price === '' || ! in_array((int) $itemId, $itemIds, true)) {
                    continue;
                }

                $supplier->quotes()->create([
                    'rfq_item_id' => (int) $itemId,
                    'unit_price' => $price,
                ]);
            }
        }
    }

    private function ensureCreatedRfq(Rfq $rfq): void
    {
        abort_unless($rfq->status === DocStatus::Created, 403, 'A TWG report can only be made from a Created RFQ.');
    }

    private function authorizeWrite(): void
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        abort_unless($user?->hasRole(Role::Bac, Role::Admin), 403);
    }
}
