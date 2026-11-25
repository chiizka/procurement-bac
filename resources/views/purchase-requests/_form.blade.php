@php
    $locked = $pr && $pr->status === \App\Enums\DocStatus::Created;
    $input = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm';
    $signatoryRoles = ['requested_by' => 'Requested by', 'certified_funds_available' => 'Certified Funds Available', 'approved_by' => 'Approved by'];
    $rows = old('items', $pr ? $pr->items->map(fn ($i) => [
        'line_type' => $i->line_type->value,
        'stock_no' => $i->stock_no,
        'unit' => $i->unit,
        'description' => $i->description,
        'quantity' => $i->quantity,
        'unit_cost' => $i->unit_cost,
    ])->values()->all() : []);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
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

    <div class="bg-white shadow-sm sm:rounded-lg p-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700">PR No. <span class="text-red-600">*</span></label>
            <input type="text" name="pr_no" value="{{ old('pr_no', $pr?->pr_no) }}" @readonly($locked)
                   class="{{ $input }} {{ $locked ? 'bg-gray-100' : '' }}" placeholder="2026-11-1278">
            @if ($locked)<p class="mt-1 text-xs text-gray-500">Locked once the PR is created.</p>@endif
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">PR Date <span class="text-red-600">*</span></label>
            <input type="date" name="pr_date"
                   value="{{ old('pr_date', $pr?->pr_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" class="{{ $input }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Entity <span class="text-red-600">*</span></label>
            <input type="text" name="entity_name" value="{{ old('entity_name', $pr?->entity_name ?? 'DOLE RO5') }}" class="{{ $input }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Fund Cluster</label>
            <input type="text" name="fund_cluster" value="{{ old('fund_cluster', $pr?->fund_cluster) }}" class="{{ $input }}" placeholder="101">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Office / Section <span class="text-red-600">*</span></label>
            <input type="text" name="office_section" value="{{ old('office_section', $pr?->office_section) }}" class="{{ $input }}" placeholder="CSPO">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Responsibility Center Code</label>
            <input type="text" name="responsibility_center_code" value="{{ old('responsibility_center_code', $pr?->responsibility_center_code) }}" class="{{ $input }}">
        </div>
        <div class="sm:col-span-3">
            <label class="block text-sm font-medium text-gray-700">Purpose <span class="text-red-600">*</span></label>
            <textarea name="purpose" rows="2" class="{{ $input }}">{{ old('purpose', $pr?->purpose) }}</textarea>
        </div>
        <div class="sm:col-span-3">
            <label class="block text-sm font-medium text-gray-700">End User</label>
            <input type="text" name="end_user" value="{{ old('end_user', $pr?->end_user) }}" class="{{ $input }}">
        </div>
    </div>

    <section class="bg-white shadow-sm sm:rounded-lg p-6" aria-labelledby="pr-signatories-heading">
        <h3 id="pr-signatories-heading" class="font-semibold text-gray-800">Signatories</h3>
        <div class="mt-4 space-y-3">
            @foreach ($signatoryRoles as $role => $label)
                <div class="grid grid-cols-1 items-center gap-3 sm:grid-cols-12">
                    <label for="{{ $role }}" class="text-sm font-medium text-gray-700 sm:col-span-3">{{ $label }}</label>
                    <input id="{{ $role }}" type="text" name="{{ $role }}" value="{{ old($role, $pr?->{$role}) }}" maxlength="255" placeholder="Full name" aria-label="{{ $label }} name" class="{{ $input }} !mt-0 sm:col-span-5">
                    <input id="{{ $role }}_position" type="text" name="{{ $role }}_position" value="{{ old($role.'_position', $pr?->{$role.'_position'}) }}" maxlength="255" placeholder="Position" aria-label="{{ $label }} position" class="{{ $input }} !mt-0 sm:col-span-4">
                </div>
            @endforeach
        </div>
    </section>

    {{-- Items editor --}}
    <div class="bg-white shadow-sm sm:rounded-lg p-6" x-data="itemsEditor(@js($rows))">
        <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Items</h3>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-xs font-semibold uppercase text-gray-500">
                    <tr>
                        <th class="px-2 py-2">Type</th>
                        <th class="px-2 py-2">Stock No.</th>
                        <th class="px-2 py-2">Unit</th>
                        <th class="px-2 py-2 w-1/3">Description</th>
                        <th class="px-2 py-2">Qty</th>
                        <th class="px-2 py-2">Unit Cost</th>
                        <th class="px-2 py-2 text-right">Amount</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(row, i) in rows" :key="row.key">
                        <tr class="align-top border-t">
                            <td class="px-2 py-2">
                                <select :name="`items[${i}][line_type]`" x-model="row.line_type" class="{{ $input }}">
                                    <option value="item">Item</option>
                                    <option value="note">Note</option>
                                </select>
                            </td>
                            <td class="px-2 py-2"><input type="text" :name="`items[${i}][stock_no]`" x-model="row.stock_no" class="{{ $input }}"></td>
                            <td class="px-2 py-2"><input type="text" :name="`items[${i}][unit]`" x-model="row.unit" class="{{ $input }}"></td>
                            <td class="px-2 py-2"><textarea rows="2" :name="`items[${i}][description]`" x-model="row.description" class="{{ $input }}"></textarea></td>
                            <td class="px-2 py-2"><input type="number" step="0.01" min="0" :name="`items[${i}][quantity]`" x-model="row.quantity" x-show="row.line_type === 'item'" class="{{ $input }}"></td>
                            <td class="px-2 py-2"><input type="number" step="0.01" min="0" :name="`items[${i}][unit_cost]`" x-model="row.unit_cost" x-show="row.line_type === 'item'" class="{{ $input }}"></td>
                            <td class="px-2 py-2 text-right" x-text="fmt(amount(row))"></td>
                            <td class="px-2 py-2"><button type="button" @click="remove(i)" class="text-red-600 hover:underline">Remove</button></td>
                        </tr>
                    </template>
                </tbody>
                <tfoot>
                    <tr class="border-t font-semibold">
                        <td colspan="6" class="px-2 py-3 text-right">Total</td>
                        <td class="px-2 py-3 text-right" x-text="fmt(total)"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <button type="button" @click="add('item')" class="mt-3 rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50">Add item</button>
    </div>

    <div class="flex items-center gap-3">
        @if ($locked)
            <button type="submit" name="action" value="created"
                    class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Save changes</button>
        @else
            <button type="submit" name="action" value="draft"
                    class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-gray-50">Save as draft</button>
            <button type="submit" name="action" value="created"
                    class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Create PR</button>
        @endif
        <a href="{{ route('purchase-requests.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
    </div>
</form>

<script>
    function itemsEditor(initial) {
        let counter = 0;
        const make = (r = {}) => ({
            key: counter++,
            line_type: r.line_type ?? 'item',
            stock_no: r.stock_no ?? '',
            unit: r.unit ?? '',
            description: r.description ?? '',
            quantity: r.quantity ?? '',
            unit_cost: r.unit_cost ?? '',
        });

        return {
            rows: (initial && initial.length ? initial : [{}]).map(make),
            add(type = 'item') { this.rows.push(make({ line_type: type })); },
            remove(i) { this.rows.splice(i, 1); },
            amount(row) {
                if (row.line_type !== 'item') return null;
                const q = parseFloat(row.quantity), c = parseFloat(row.unit_cost);
                return isNaN(q) || isNaN(c) ? null : q * c;
            },
            get total() { return this.rows.reduce((s, r) => s + (this.amount(r) ?? 0), 0); },
            fmt(v) {
                return v === null ? '' : v.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
        };
    }
</script>