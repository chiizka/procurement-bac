@php
    // Expects: $pr (PurchaseRequest with items), $rfq (Rfq|null), $usage (pr_item_id => [rfq_no...]),
    //          $action (form URL), $method ('POST' | 'PUT')
    $locked = $rfq && $rfq->status === \App\Enums\DocStatus::Created;
    $input = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm';

    // Rows already on this RFQ (editing, or re-showing the form after a validation error).
    $rows = old('items', $rfq ? $rfq->items->map(fn ($i) => [
        'pr_item_id' => $i->pr_item_id,
        'line_type' => $i->line_type->value,
        'stock_no' => $i->stock_no,
        'unit' => $i->unit,
        'description' => $i->description,
        'quantity' => $i->quantity,
        'unit_cost' => $i->unit_cost,
    ])->values()->all() : []);

    // PR lines offered in the picker.
    $source = $pr->items->map(fn ($i) => [
        'id' => $i->id,
        'line_type' => $i->line_type->value,
        'stock_no' => $i->stock_no,
        'unit' => $i->unit,
        'description' => $i->description,
        'quantity' => $i->quantity,
        'unit_cost' => $i->unit_cost,
        'on' => $usage[$i->id] ?? [],
    ])->values()->all();
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

    {{-- Header --}}
    <div class="bg-white shadow-sm sm:rounded-lg p-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <label class="block text-sm font-medium text-gray-700">RFQ No. *</label>
            <input type="text" name="rfq_no" value="{{ old('rfq_no', $rfq?->rfq_no) }}" @readonly($locked)
                   class="{{ $input }} {{ $locked ? 'bg-gray-100' : '' }}" placeholder="601-2026">
            @if ($locked)<p class="mt-1 text-xs text-gray-500">Locked once the RFQ is created.</p>@endif
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">RFQ Date *</label>
            <input type="date" name="rfq_date"
                   value="{{ old('rfq_date', $rfq?->rfq_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" class="{{ $input }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Closing date &amp; time <span class="text-gray-400">(required to create)</span></label>
            <input type="datetime-local" name="deadline_at"
                   value="{{ old('deadline_at', $rfq?->deadline_at?->format('Y-m-d\TH:i')) }}" class="{{ $input }}">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Lot label</label>
            <input type="text" name="lot_label" value="{{ old('lot_label', $rfq?->lot_label) }}" class="{{ $input }}" placeholder="Lot 3 - Fishing">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">PR No.</label>
            <input type="text" value="{{ $pr->pr_no }}" readonly class="{{ $input }} bg-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">End User (from PR)</label>
            <input type="text" value="{{ $pr->end_user }}" readonly class="{{ $input }} bg-gray-100">
        </div>

        <div class="sm:col-span-3">
            <label class="block text-sm font-medium text-gray-700">Purpose / Project title *</label>
            <textarea name="purpose" rows="2" class="{{ $input }}">{{ old('purpose', $rfq?->purpose ?? $pr->purpose) }}</textarea>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Contract Duration</label>
            <input type="text" name="contract_duration" value="{{ old('contract_duration', $rfq?->contract_duration) }}"
                   class="{{ $input }}" placeholder="30 days upon receipt of PO">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Delivery Area</label>
            <input type="text" name="delivery_area" value="{{ old('delivery_area', $rfq?->delivery_area) }}" class="{{ $input }}">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">BAC Chairperson</label>
            <input type="text" name="bac_chairperson" value="{{ old('bac_chairperson', $rfq?->bac_chairperson) }}" class="{{ $input }}">
        </div>
    </div>

    <div x-data="rfqEditor(@js($source), @js($rows))" class="space-y-6">

        {{-- Picker: tick PR lines, copy them into this RFQ --}}
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="font-semibold text-gray-800">Items from PR {{ $pr->pr_no }}</h3>
                    <p class="text-xs text-gray-500">Tick the lines for this RFQ and click Add. Ticking a lot header ticks its items. Copied rows stay editable below.</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="selectAll()" class="rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50">Tick all</button>
                    <button type="button" @click="picked = []" class="rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50">Clear</button>
                    <button type="button" @click="addPicked()" :disabled="!picked.length"
                            class="rounded-md bg-gray-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-40">
                        Add to RFQ (<span x-text="picked.length"></span>)
                    </button>
                </div>
            </div>

            <div class="mt-3 max-h-80 overflow-auto border rounded-md">
                <table class="min-w-full text-sm">
                    <tbody>
                        <template x-for="(s, idx) in source" :key="s.id">
                            <tr class="border-t first:border-t-0" :class="s.line_type === 'lot_header' ? 'bg-gray-50 font-semibold' : ''">
                                <td class="px-3 py-2 w-8 align-top">
                                    <input type="checkbox" class="rounded border-gray-300"
                                           :disabled="s.line_type === 'lot_header' ? lotAllAdded(idx) : inRows(s.id)"
                                           :checked="s.line_type === 'lot_header' ? lotChecked(idx) : picked.includes(s.id)"
                                           @change="s.line_type === 'lot_header' ? toggleLot(idx) : toggle(s.id)">
                                </td>
                                <td class="px-2 py-2 align-top">
                                    <span x-text="s.description.split('\n')[0]"></span>
                                    <template x-if="s.line_type === 'note'"><span class="ml-1 text-xs italic text-gray-500">(note)</span></template>
                                </td>
                                <td class="px-2 py-2 align-top whitespace-nowrap text-gray-600">
                                    <template x-if="s.line_type === 'item'">
                                        <span x-text="`${s.quantity ?? ''} ${s.unit ?? ''}`"></span>
                                    </template>
                                </td>
                                <td class="px-2 py-2 align-top text-right whitespace-nowrap text-gray-600">
                                    <template x-if="s.line_type === 'item'"><span x-text="fmt(num(s.unit_cost))"></span></template>
                                </td>
                                <td class="px-2 py-2 align-top whitespace-nowrap text-xs">
                                    <template x-if="s.line_type !== 'lot_header' && inRows(s.id)"><span class="rounded bg-green-50 px-2 py-0.5 text-green-700">added</span></template>
                                    <template x-if="s.on.length"><span class="rounded bg-amber-50 px-2 py-0.5 text-amber-700" x-text="'on RFQ ' + s.on.join(', ')"></span></template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Items editor (this RFQ's own rows) --}}
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">RFQ items</h3>
                <div class="flex gap-2">
                    <button type="button" @click="add('item')" class="rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50">+ Item</button>
                    <button type="button" @click="add('lot_header')" class="rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50">+ Lot header</button>
                    <button type="button" @click="add('note')" class="rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50">+ Note line</button>
                </div>
            </div>

            <p x-show="!rows.length" class="mt-4 text-sm text-gray-500">No rows yet. Pick items above, or add a row by hand.</p>

            <div class="mt-4 overflow-x-auto" x-show="rows.length">
                <table class="min-w-full text-sm">
                    <thead class="text-left text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="px-2 py-2">Type</th>
                            <th class="px-2 py-2">Stock No.</th>
                            <th class="px-2 py-2">Unit</th>
                            <th class="px-2 py-2 w-1/3">Description</th>
                            <th class="px-2 py-2">Qty</th>
                            <th class="px-2 py-2">ABC Unit Cost</th>
                            <th class="px-2 py-2 text-right">ABC Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, i) in rows" :key="row.key">
                            <tr class="align-top border-t">
                                <td class="px-2 py-2">
                                    <input type="hidden" :name="`items[${i}][pr_item_id]`" :value="row.pr_item_id">
                                    <select :name="`items[${i}][line_type]`" x-model="row.line_type" class="{{ $input }}">
                                        <option value="item">Item</option>
                                        <option value="lot_header">Lot header</option>
                                        <option value="note">Note</option>
                                    </select>
                                </td>
                                <td class="px-2 py-2"><input type="text" :name="`items[${i}][stock_no]`" x-model="row.stock_no" class="{{ $input }}"></td>
                                <td class="px-2 py-2"><input type="text" :name="`items[${i}][unit]`" x-model="row.unit" class="{{ $input }}"></td>
                                <td class="px-2 py-2"><textarea rows="3" :name="`items[${i}][description]`" x-model="row.description" class="{{ $input }}"></textarea></td>
                                <td class="px-2 py-2"><input type="number" step="0.01" min="0" :name="`items[${i}][quantity]`" x-model="row.quantity" x-show="row.line_type === 'item'" class="{{ $input }}"></td>
                                <td class="px-2 py-2"><input type="number" step="0.01" min="0" :name="`items[${i}][unit_cost]`" x-model="row.unit_cost" x-show="row.line_type === 'item'" class="{{ $input }}"></td>
                                <td class="px-2 py-2 text-right" x-text="fmt(amount(row))"></td>
                                <td class="px-2 py-2"><button type="button" @click="remove(i)" class="text-red-600 hover:underline">Remove</button></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="border-t font-semibold">
                            <td colspan="6" class="px-2 py-3 text-right">Total ABC</td>
                            <td class="px-2 py-3 text-right" x-text="fmt(total)"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
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
                    class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Create RFQ</button>
        @endif
        <a href="{{ $rfq ? route('rfqs.show', $rfq) : route('purchase-requests.show', $pr) }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
    </div>
</form>

<script>
    function rfqEditor(source, initial) {
        let counter = 0;
        const make = (r = {}) => ({
            key: counter++,
            pr_item_id: r.pr_item_id ?? '',
            line_type: r.line_type ?? 'item',
            stock_no: r.stock_no ?? '',
            unit: r.unit ?? '',
            description: r.description ?? '',
            quantity: r.quantity ?? '',
            unit_cost: r.unit_cost ?? '',
        });
        const fromSource = (s) => make({
            pr_item_id: s.id, line_type: s.line_type, stock_no: s.stock_no, unit: s.unit,
            description: s.description, quantity: s.quantity, unit_cost: s.unit_cost,
        });

        return {
            source,
            picked: [],
            rows: (initial || []).map(make),

            // ---- picker ----
            inRows(id) { return this.rows.some(r => String(r.pr_item_id) === String(id)); },
            toggle(id) {
                this.picked = this.picked.includes(id) ? this.picked.filter(x => x !== id) : [...this.picked, id];
            },
            // ids of the non-header lines under the lot header at index idx
            lotIds(idx) {
                const ids = [];
                for (let k = idx + 1; k < this.source.length && this.source[k].line_type !== 'lot_header'; k++) {
                    ids.push(this.source[k].id);
                }
                return ids;
            },
            lotChecked(idx) {
                const ids = this.lotIds(idx).filter(id => !this.inRows(id));
                return ids.length > 0 && ids.every(id => this.picked.includes(id));
            },
            lotAllAdded(idx) {
                const ids = this.lotIds(idx);
                return ids.length === 0 || ids.every(id => this.inRows(id));
            },
            toggleLot(idx) {
                const ids = this.lotIds(idx).filter(id => !this.inRows(id));
                if (this.lotChecked(idx)) {
                    this.picked = this.picked.filter(id => !ids.includes(id));
                } else {
                    this.picked = [...new Set([...this.picked, ...ids])];
                }
            },
            selectAll() {
                this.picked = this.source
                    .filter(s => s.line_type !== 'lot_header' && !this.inRows(s.id))
                    .map(s => s.id);
            },
            addPicked() {
                let header = null;
                for (const s of this.source) {
                    if (s.line_type === 'lot_header') { header = s; continue; }
                    if (!this.picked.includes(s.id) || this.inRows(s.id)) continue;
                    // bring the lot header along the first time one of its lines is added
                    if (header && !this.inRows(header.id)) this.rows.push(fromSource(header));
                    this.rows.push(fromSource(s));
                }
                this.picked = [];
            },

            // ---- editor ----
            add(type = 'item') { this.rows.push(make({ line_type: type })); },
            remove(i) { this.rows.splice(i, 1); },
            num(v) { const n = parseFloat(v); return isNaN(n) ? null : n; },
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
