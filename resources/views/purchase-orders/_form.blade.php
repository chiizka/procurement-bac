@php
    $input = 'block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500';
    $locked = $po?->status === \App\Enums\DocStatus::Created;
    $sid = (string) old('twg_supplier_id', $po?->twg_supplier_id ?? $defaultSupplierId);
    $current = [
        'name' => old('supplier_name', $po?->supplier_name ?? ($suppliers[$sid]['name'] ?? '')),
        'address' => old('supplier_address', $po?->supplier_address ?? ($suppliers[$sid]['address'] ?? '')),
        'tin' => old('supplier_tin', $po?->supplier_tin ?? ($suppliers[$sid]['tin'] ?? '')),
        'contact_no' => old('supplier_contact_no', $po?->supplier_contact_no ?? ($suppliers[$sid]['contact_no'] ?? '')),
    ];
    $deductions = old('deductions', $po?->deductions ?? []);
    $dropdowns = [
        'place_of_delivery' => ['Place of delivery', \App\Support\PoOptions::places(), 'sm:col-span-2'],
        'delivery_term' => ['Delivery term', \App\Support\PoOptions::deliveryTerms(), ''],
        'payment_term' => ['Payment term', \App\Support\PoOptions::paymentTerms(), ''],
    ];
@endphp
<form method="POST" action="{{ $action }}" class="space-y-6"
      x-data="{
          suppliers: @js($suppliers),
          supplierId: @js($sid),
          f: @js($current),
          deductions: @js(array_values($deductions)),
          money(n) { return Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
          get lines() { return this.suppliers[this.supplierId]?.lines ?? []; },
          get total() { return Number(this.suppliers[this.supplierId]?.total ?? 0); },
          get deducted() { return this.deductions.reduce((s, d) => s + (parseFloat(d.amount) || 0), 0); },
          get due() { return this.total - this.deducted; },
          pickSupplier() {
              const s = this.suppliers[this.supplierId];
              if (!s) return;
              this.f = { name: s.name, address: s.address ?? '', tin: s.tin ?? '', contact_no: s.contact_no ?? '' };
          },
      }">
    @csrf
    @if ($method === 'PUT') @method('PUT') @endif
    @if ($errors->any())
        <div role="alert" class="rounded-md bg-red-50 p-4 text-sm text-red-800">
            <p class="font-semibold">Please fix the following:</p>
            <ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <section class="rounded-lg bg-white p-6 shadow-sm">
        <h3 class="font-semibold text-gray-900">Purchase Order</h3>
        <p class="mt-2 text-sm text-gray-500">Abstract {{ $abstract->abstract_no }} &middot; TWG {{ $twg->twg_no }} &middot; RFQ {{ $twg->rfq->rfq_no }}. Items and prices come from the awarded supplier's TWG quotation.</p>
        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div><label for="po_no" class="mb-1 block text-sm font-medium">P.O. No.</label><input id="po_no" name="po_no" value="{{ old('po_no', $po?->po_no) }}" required maxlength="50" placeholder="e.g. 2025-07-362" @readonly($locked) class="{{ $input }} {{ $locked ? 'bg-gray-100' : '' }}">@if ($locked)<p class="mt-1 text-xs text-gray-500">Locked once Created.</p>@endif</div>
            <div><label for="po_date" class="mb-1 block text-sm font-medium">Date</label><input id="po_date" type="date" name="po_date" required value="{{ old('po_date', $po?->po_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" class="{{ $input }}"></div>
            <div><label for="procurement_mode" class="mb-1 block text-sm font-medium">Mode of procurement</label><input id="procurement_mode" name="procurement_mode" required maxlength="255" value="{{ old('procurement_mode', $po?->procurement_mode ?? $abstract->procurement_mode) }}" class="{{ $input }}"></div>
        </div>
    </section>

    <section class="rounded-lg bg-white p-6 shadow-sm">
        <h3 class="font-semibold text-gray-900">Supplier</h3>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2"><label for="twg_supplier_id" class="mb-1 block text-sm font-medium">Awarded supplier</label>
                <select id="twg_supplier_id" name="twg_supplier_id" x-model="supplierId" @change="pickSupplier()" required class="{{ $input }} sm:max-w-xl">
                    @foreach ($suppliers as $supplier)<option value="{{ $supplier['id'] }}">{{ $supplier['name'] }}{{ $supplier['quoted'] ? '' : ' (no quotation)' }}</option>@endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">Choosing a supplier fills in its details from the suppliers list when it has a match. You can still edit them below.</p>
            </div>
            <div><label for="supplier_name" class="mb-1 block text-sm font-medium">Supplier name</label><input id="supplier_name" name="supplier_name" x-model="f.name" required maxlength="255" class="{{ $input }}"></div>
            <div><label for="supplier_address" class="mb-1 block text-sm font-medium">Address</label><input id="supplier_address" name="supplier_address" x-model="f.address" maxlength="255" class="{{ $input }}"></div>
            <div><label for="supplier_tin" class="mb-1 block text-sm font-medium">TIN</label><input id="supplier_tin" name="supplier_tin" x-model="f.tin" maxlength="50" class="{{ $input }}"></div>
            <div><label for="supplier_contact_no" class="mb-1 block text-sm font-medium">Contact no.</label><input id="supplier_contact_no" name="supplier_contact_no" x-model="f.contact_no" maxlength="100" class="{{ $input }}"></div>
        </div>
    </section>

    <section class="rounded-lg bg-white p-6 shadow-sm">
        <h3 class="font-semibold text-gray-900">Delivery and payment</h3>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ($dropdowns as $name => [$label, $options, $span])
                @php($value = old($name, $po?->{$name}))
                <div class="{{ $span }}"><label for="{{ $name }}" class="mb-1 block text-sm font-medium">{{ $label }}</label>
                    <select id="{{ $name }}" name="{{ $name }}" class="{{ $input }}">
                        <option value="">Not selected</option>
                        @foreach ($options as $option)<option value="{{ $option }}" @selected($value === $option)>{{ $option }}</option>@endforeach
                        @if (filled($value) && ! in_array($value, $options, true))<option value="{{ $value }}" selected>{{ $value }}</option>@endif
                    </select>
                </div>
            @endforeach
            <div><label for="date_of_delivery" class="mb-1 block text-sm font-medium">Date of delivery</label><input id="date_of_delivery" name="date_of_delivery" maxlength="100" value="{{ old('date_of_delivery', $po?->date_of_delivery) }}" placeholder="e.g. 1 day" class="{{ $input }}"></div>
        </div>
    </section>

    <section class="rounded-lg bg-white p-6 shadow-sm">
        <h3 class="font-semibold text-gray-900">Items</h3>
        <p class="mt-1 text-sm text-gray-500">Copied from the RFQ and priced from the awarded supplier's quotation. To change an item or price, fix it in the TWG report.</p>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs text-gray-600"><tr><th class="p-3">Unit</th><th class="p-3">Description</th><th class="p-3 text-right">Qty</th><th class="p-3 text-right">Unit cost</th><th class="p-3 text-right">Amount</th></tr></thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-for="(line, i) in lines" :key="i">
                        <tr class="align-top">
                            <td class="p-3" x-text="line.unit"></td>
                            <td class="p-3 whitespace-pre-line" :class="line.line_type === 'lot_header' ? 'font-semibold' : ''" x-text="line.description"></td>
                            <td class="p-3 text-right" x-text="line.quantity === null ? '' : Number(line.quantity)"></td>
                            <td class="p-3 text-right" x-text="line.unit_cost === null ? '' : money(line.unit_cost)"></td>
                            <td class="p-3 text-right" x-text="line.amount === null ? '' : money(line.amount)"></td>
                        </tr>
                    </template>
                    <tr x-show="lines.length === 0"><td colspan="5" class="p-3 text-gray-500">This supplier has no quoted items.</td></tr>
                </tbody>
                <tfoot><tr class="font-semibold"><td colspan="4" class="p-3 text-right">Total</td><td class="p-3 text-right" x-text="money(total)"></td></tr></tfoot>
            </table>
        </div>
    </section>

    <section class="rounded-lg bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3"><h3 class="font-semibold text-gray-900">Warranty security and deductions</h3><button type="button" @click="deductions.push({label: '', rate: '', amount: ''})" class="rounded-md border px-3 py-2 text-sm hover:bg-gray-50">+ Deduction</button></div>
        <p class="mt-2 text-sm text-gray-500">Typed by hand for now. Nothing is computed from tax rates. Amount due is the total less the amounts below. Rows with no amount are ignored.</p>
        <div class="mt-4"><label for="warranty_security" class="mb-1 block text-sm font-medium">Warranty security line (optional)</label><input id="warranty_security" name="warranty_security" maxlength="255" value="{{ old('warranty_security', $po?->warranty_security) }}" placeholder="e.g. Warranty Security 1%" class="{{ $input }} sm:max-w-md"></div>
        <div class="mt-4 space-y-3"><template x-for="(row, index) in deductions" :key="index">
            <div class="grid grid-cols-1 items-start gap-3 sm:grid-cols-12">
                <input :name="`deductions[${index}][label]`" x-model="row.label" aria-label="Deduction label" placeholder="less tax:" maxlength="100" class="{{ $input }} sm:col-span-4">
                <input :name="`deductions[${index}][rate]`" x-model="row.rate" aria-label="Deduction rate" placeholder="Rate, e.g. 5%" maxlength="20" class="{{ $input }} sm:col-span-3">
                <input :name="`deductions[${index}][amount]`" x-model="row.amount" type="number" min="0" step="0.01" aria-label="Deduction amount" placeholder="Amount" class="{{ $input }} text-right sm:col-span-4">
                <button type="button" @click="deductions.splice(index, 1)" class="min-h-10 text-sm text-red-600 hover:underline sm:col-span-1">Remove</button>
            </div>
        </template></div>
        <dl class="mt-5 space-y-1 text-sm sm:max-w-sm">
            <div class="flex justify-between"><dt>Total</dt><dd x-text="money(total)"></dd></div>
            <div class="flex justify-between"><dt>Deductions</dt><dd x-text="money(deducted)"></dd></div>
            <div class="flex justify-between border-t pt-1 font-semibold"><dt>Amount due</dt><dd :class="due < 0 ? 'text-red-600' : ''" x-text="money(due)"></dd></div>
        </dl>
        <p class="mt-2 text-xs text-gray-500">The amount in words is generated from the amount due when the PO is printed.</p>
    </section>

    <section class="rounded-lg bg-white p-6 shadow-sm">
        <h3 class="font-semibold text-gray-900">Signatories</h3>
        <p class="mt-2 text-sm text-gray-500">All optional and blank by default. Leave them empty to sign by hand. Fund Cluster, Funds Available and the ORS/BURS boxes always print blank.</p>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div><label for="approver_name" class="mb-1 block text-sm font-medium">Approving officer</label><input id="approver_name" name="approver_name" maxlength="255" value="{{ old('approver_name', $po?->approver_name) }}" class="{{ $input }}"></div>
            <div><label for="approver_position" class="mb-1 block text-sm font-medium">Position</label><input id="approver_position" name="approver_position" maxlength="255" value="{{ old('approver_position', $po?->approver_position) }}" class="{{ $input }}"></div>
            <div><label for="accountant_name" class="mb-1 block text-sm font-medium">Accountant</label><input id="accountant_name" name="accountant_name" maxlength="255" value="{{ old('accountant_name', $po?->accountant_name) }}" class="{{ $input }}"></div>
        </div>
    </section>

    <div class="flex flex-wrap items-center gap-3">
        @unless ($locked)<button type="submit" name="action" value="draft" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-50">Save as draft</button>@endunless
        <button type="submit" name="action" value="created" class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">{{ $locked ? 'Save changes' : 'Create Purchase Order' }}</button>
        <a href="{{ route('purchase-requests.show', $twg->rfq->purchase_request_id) }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
    </div>
</form>
