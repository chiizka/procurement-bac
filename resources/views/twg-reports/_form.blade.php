@php
    // Expects: $rfq (Rfq with items + purchaseRequest), $twg (TwgReport|null, with suppliers.quotes),
    //          $lastSignatories (array|null), $action (form URL), $method ('POST' | 'PUT')
    $locked = $twg && $twg->status === \App\Enums\DocStatus::Created;
    $input = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm';

    // RFQ lines shown as the rows of the quotation grid.
    $items = $rfq->items->map(fn ($i) => [
        'id' => $i->id,
        'line_type' => $i->line_type->value,
        'quantity' => $i->quantity,
        'unit' => $i->unit,
        'description' => $i->description,
        'unit_cost' => $i->unit_cost,
    ])->values()->all();

    // Prices are passed as [item_id, price] pairs so PHP never turns them into a JSON list by accident.
    $pairs = fn ($quotes) => collect($quotes ?? [])->map(fn ($v, $k) => [(int) $k, $v])->values()->all();

    $suppliers = old('suppliers') !== null
        ? collect(old('suppliers'))->map(fn ($s) => [
            'name' => $s['name'] ?? '',
            'remarks' => $s['remarks'] ?? '',
            'quotes' => $pairs($s['quotes'] ?? []),
        ])->values()->all()
        : ($twg ? $twg->suppliers->map(fn ($s) => [
            'name' => $s->name,
            'remarks' => $s->remarks,
            'quotes' => $s->quotes->map(fn ($q) => [$q->rfq_item_id, $q->unit_price])->values()->all(),
        ])->values()->all() : []);

    $findings = old('findings', $twg?->findings ?: [['scope' => 'For all the items', 'supplier' => '', 'text' => '']]);

    $signatories = old('signatories', $twg?->signatories ?: ($lastSignatories ?: [
        ['group' => 'evaluated', 'name' => '', 'position' => 'TWG Member'],
        ['group' => 'evaluated', 'name' => '', 'position' => 'TWG Member'],
        ['group' => 'reviewed', 'name' => '', 'position' => 'TWG Member'],
    ]));

    $abc = old('abc', $twg?->abc ?? $rfq->total_amount);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6"
      x-data="twgEditor(@js($items), @js($suppliers), @js($findings), @js($signatories), @js((float) $abc))">
    @csrf
    @if ($method === 'PUT') @method('PUT') @endif

    @if ($errors->any())
        <div class="rounded-md bg-red-50 p-3 text-sm text-red-800">
            <p class="font-medium">Please fix the following:</p>
            <ul class="mt-1 list-disc pl-5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    {{-- Header --}}
    <div class="bg-white shadow-sm sm:rounded-lg p-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700">TWG Report No. *</label>
            <input type="text" name="twg_no" value="{{ old('twg_no', $twg?->twg_no) }}" @readonly($locked)
                   class="{{ $input }} {{ $locked ? 'bg-gray-100' : '' }}" placeholder="2026 - 001">
            @if ($locked)<p class="mt-1 text-xs text-gray-500">Locked once the report is created.</p>@endif
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Date *</label>
            <input type="date" name="twg_date"
                   value="{{ old('twg_date', $twg?->twg_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" class="{{ $input }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">RFQ</label>
            <input type="text" value="{{ $rfq->rfq_no }}{{ $rfq->lot_label ? ' — '.$rfq->lot_label : '' }}" readonly class="{{ $input }} bg-gray-100">
        </div>

        <div class="sm:col-span-3">
            <label class="block text-sm font-medium text-gray-700">Name of Project *</label>
            <textarea name="project_name" rows="2" class="{{ $input }}">{{ old('project_name', $twg?->project_name ?? $rfq->purpose) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Approved Budget for the Contract *</label>
            <input type="number" step="0.01" min="0" name="abc" x-model="abc" class="{{ $input }}">
            <p class="mt-1 text-xs text-gray-500">Copied from the RFQ total. Edit only if it differs.</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Method of Procurement *</label>
            <input type="text" name="procurement_mode"
                   value="{{ old('procurement_mode', $twg?->procurement_mode ?? 'Section 53.9 - Small Value Procurement') }}" class="{{ $input }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Criteria for Evaluation *</label>
            <input type="text" name="criteria" list="twg-criteria"
                   value="{{ old('criteria', $twg?->criteria ?? 'Single Calculated and Responsive Quotation (SCRQ)') }}" class="{{ $input }}">
            <datalist id="twg-criteria">
                <option value="Single Calculated and Responsive Quotation (SCRQ)">
                <option value="Lowest Calculated and Responsive Quotation (LCRQ)">
            </datalist>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">PhilGEPS Reference Number</label>
            <input type="text" name="philgeps_ref" value="{{ old('philgeps_ref', $twg?->philgeps_ref ?? 'Not Available') }}" class="{{ $input }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Date of PhilGEPS Posting</label>
            <input type="text" name="philgeps_posted" value="{{ old('philgeps_posted', $twg?->philgeps_posted ?? 'Not Available') }}" class="{{ $input }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Number of Quotations Received</label>
            <input type="text" readonly class="{{ $input }} bg-gray-100" :value="quotedCount + ' (counted from the grid below)'">
        </div>
    </div>

    {{-- Quotation grid: RFQ lines x suppliers --}}
    <div class="bg-white shadow-sm sm:rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-gray-800">Details of Quotations</h3>
                <p class="text-xs text-gray-500">Type each supplier's price per unit. Leave a cell blank for "No Quotation". Totals are computed.</p>
            </div>
            <button type="button" @click="addSupplier()" class="rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50">+ Supplier</button>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-2 py-2 whitespace-nowrap">Qty / Unit</th>
                        <th class="px-2 py-2 w-1/3">Item description</th>
                        <th class="px-2 py-2 text-right whitespace-nowrap">ABC unit cost</th>
                        <template x-for="(s, si) in suppliers" :key="si">
                            <th class="px-2 py-2 min-w-[11rem] align-top">
                                <input type="text" :name="`suppliers[${si}][name]`" x-model="s.name"
                                       placeholder="Supplier name" class="{{ $input }} !mt-0 normal-case">
                                <button type="button" @click="removeSupplier(si)" class="mt-1 text-xs font-normal normal-case text-red-600 hover:underline">Remove</button>
                            </th>
                        </template>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="it in items" :key="it.id">
                        <tr class="border-t align-top" :class="it.line_type === 'lot_header' ? 'bg-gray-50 font-semibold' : ''">
                            <template x-if="it.line_type === 'item'">
                                <td class="px-2 py-2 whitespace-nowrap" x-text="`${trim(it.quantity)} ${it.unit ?? ''}`"></td>
                            </template>
                            <template x-if="it.line_type !== 'item'"><td class="px-2 py-2"></td></template>

                            <td class="px-2 py-2">
                                <div :class="it.line_type === 'item' ? 'font-medium' : ''" x-text="it.description.split('\n')[0]"></div>
                                <div class="whitespace-pre-line text-xs text-gray-500" x-text="it.description.split('\n').slice(1).join('\n')"></div>
                            </td>
                            <td class="px-2 py-2 text-right whitespace-nowrap text-gray-600"
                                x-text="it.line_type === 'item' ? fmt(parseFloat(it.unit_cost)) : ''"></td>

                            <template x-for="(s, si) in suppliers" :key="si">
                                <td class="px-2 py-2">
                                    <template x-if="it.line_type === 'item'">
                                        <div>
                                            <input type="number" step="0.01" min="0" placeholder="unit price"
                                                   :name="`suppliers[${si}][quotes][${it.id}]`" x-model="s.prices[it.id]"
                                                   class="{{ $input }} !mt-0 text-right">
                                            <div class="mt-1 text-right text-xs text-gray-500"
                                                 x-text="lineTotal(s, it) === null ? 'No Quotation' : 'Total ' + fmt(lineTotal(s, it))"></div>
                                        </div>
                                    </template>
                                </td>
                            </template>
                        </tr>
                    </template>
                </tbody>
                <tfoot>
                    <tr class="border-t font-semibold">
                        <td></td><td class="px-2 py-3">Total</td><td class="px-2 py-3 text-right" x-text="fmt(parseFloat(abc))"></td>
                        <template x-for="(s, si) in suppliers" :key="si">
                            <td class="px-2 py-3 text-right">
                                <span x-text="supplierTotal(s) === null ? '-' : fmt(supplierTotal(s))"></span>
                                <div class="text-xs font-normal text-amber-700" x-show="overAbc(s)">above ABC</div>
                            </td>
                        </template>
                    </tr>
                    <tr class="border-t">
                        <td></td><td class="px-2 py-3 font-semibold">Remarks</td><td></td>
                        <template x-for="(s, si) in suppliers" :key="si">
                            <td class="px-2 py-3">
                                <input type="text" :name="`suppliers[${si}][remarks]`" x-model="s.remarks" list="twg-remarks"
                                       placeholder="SCQ / Failed" class="{{ $input }} !mt-0">
                            </td>
                        </template>
                    </tr>
                </tfoot>
            </table>
            <datalist id="twg-remarks"><option value="SCQ"><option value="Failed"></datalist>
        </div>

        <p x-show="!suppliers.length" class="mt-4 text-sm text-gray-500">No suppliers yet. Click "+ Supplier" to add a column.</p>
    </div>

    {{-- Findings --}}
    <div class="bg-white shadow-sm sm:rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-gray-800">TWG findings</h3>
                <p class="text-xs text-gray-500">"Based on the above price quotations ..., the TWG found that:"</p>
            </div>
            <button type="button" @click="findings.push({scope: '', supplier: '', text: ''})"
                    class="rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50">+ Finding</button>
        </div>

        <div class="mt-4 space-y-3">
            <template x-for="(f, fi) in findings" :key="fi">
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-12">
                    <input type="text" :name="`findings[${fi}][scope]`" x-model="f.scope" placeholder="For all the items"
                           class="{{ $input }} !mt-0 sm:col-span-2">
                    <input type="text" :name="`findings[${fi}][supplier]`" x-model="f.supplier" list="twg-supplier-names" placeholder="Supplier"
                           class="{{ $input }} !mt-0 sm:col-span-3">
                    <input type="text" :name="`findings[${fi}][text]`" x-model="f.text"
                           placeholder="offered the SINGLE CALCULATED AND RESPONSIVE QUOTATION (RA 9184)."
                           class="{{ $input }} !mt-0 sm:col-span-6">
                    <button type="button" @click="findings.splice(fi, 1)" class="text-sm text-red-600 hover:underline sm:col-span-1">Remove</button>
                </div>
            </template>
            <datalist id="twg-supplier-names">
                <template x-for="s in suppliers"><option :value="s.name"></option></template>
            </datalist>
        </div>
    </div>

    {{-- Signatories --}}
    <div class="bg-white shadow-sm sm:rounded-lg p-6">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Signatories</h3>
            <button type="button" @click="signatories.push({group: 'evaluated', name: '', position: 'TWG Member'})"
                    class="rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50">+ Signatory</button>
        </div>

        <div class="mt-4 space-y-3">
            <template x-for="(sg, gi) in signatories" :key="gi">
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-12">
                    <select :name="`signatories[${gi}][group]`" x-model="sg.group" class="{{ $input }} !mt-0 sm:col-span-3">
                        <option value="evaluated">Evaluated by</option>
                        <option value="reviewed">Reviewed by</option>
                    </select>
                    <input type="text" :name="`signatories[${gi}][name]`" x-model="sg.name" placeholder="FULL NAME"
                           class="{{ $input }} !mt-0 sm:col-span-5">
                    <input type="text" :name="`signatories[${gi}][position]`" x-model="sg.position" placeholder="TWG Member"
                           class="{{ $input }} !mt-0 sm:col-span-3">
                    <button type="button" @click="signatories.splice(gi, 1)" class="text-sm text-red-600 hover:underline sm:col-span-1">Remove</button>
                </div>
            </template>
        </div>
    </div>

    <div class="flex items-center gap-3">
        @if ($locked)
            <button type="submit" name="action" value="created"
                    class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Save changes</button>
        @else
            <button type="submit" name="action" value="draft"
                    class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-gray-50">Save as draft</button>
            <button type="submit" name="action" value="created"
                    class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Create TWG Report</button>
        @endif
        <a href="{{ $twg ? route('twg-reports.show', $twg) : route('rfqs.show', $rfq) }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
    </div>
</form>

<script>
    function twgEditor(items, suppliers, findings, signatories, abc) {
        const toPrices = (pairs) => Object.fromEntries((pairs || []).map(([id, v]) => [String(id), v ?? '']));

        return {
            items,
            abc,
            suppliers: suppliers.map(s => ({ name: s.name ?? '', remarks: s.remarks ?? '', prices: toPrices(s.quotes) })),
            findings: findings.map(f => ({ scope: f.scope ?? '', supplier: f.supplier ?? '', text: f.text ?? '' })),
            signatories: signatories.map(s => ({ group: s.group ?? 'evaluated', name: s.name ?? '', position: s.position ?? '' })),

            addSupplier() { this.suppliers.push({ name: '', remarks: '', prices: {} }); },
            removeSupplier(i) { this.suppliers.splice(i, 1); },

            trim(v) { const n = parseFloat(v); return isNaN(n) ? '' : String(+n.toFixed(2)); },
            lineTotal(s, it) {
                const p = parseFloat(s.prices[it.id]), q = parseFloat(it.quantity);
                return isNaN(p) || isNaN(q) ? null : Math.round(p * q * 100) / 100;
            },
            supplierTotal(s) {
                let any = false, total = 0;
                for (const it of this.items) {
                    if (it.line_type !== 'item') continue;
                    const v = this.lineTotal(s, it);
                    if (v !== null) { any = true; total += v; }
                }
                return any ? total : null;
            },
            overAbc(s) {
                const t = this.supplierTotal(s), a = parseFloat(this.abc);
                return t !== null && !isNaN(a) && t > a;
            },
            get quotedCount() { return this.suppliers.filter(s => this.supplierTotal(s) !== null).length; },
            fmt(v) {
                return v === null || v === undefined || isNaN(v)
                    ? ''
                    : v.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
        };
    }
</script>
