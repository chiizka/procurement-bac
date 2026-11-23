<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">New Group PR</h2>
            <a href="{{ route('purchase-requests.index') }}" class="text-sm text-gray-600 hover:underline">Back to list</a>
        </div>
    </x-slot>

    @php
        $input = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm';
    @endphp

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('group-purchase-requests.store') }}" class="space-y-6"
                  x-data="groupEditor(@js($available), @js(old('lots', [])))">
                @csrf

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
                        <label class="block text-sm font-medium text-gray-700">Group PR No. *</label>
                        <input type="text" name="pr_no" value="{{ old('pr_no') }}" class="{{ $input }}" placeholder="2026-01-26">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">PR Date *</label>
                        <input type="date" name="pr_date" value="{{ old('pr_date', now()->format('Y-m-d')) }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Entity *</label>
                        <input type="text" name="entity_name" value="{{ old('entity_name', 'DOLE RO5') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fund Cluster</label>
                        <input type="text" name="fund_cluster" value="{{ old('fund_cluster') }}" class="{{ $input }}" placeholder="101">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Office / Section *</label>
                        <input type="text" name="office_section" value="{{ old('office_section') }}" class="{{ $input }}" placeholder="TSSD">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Responsibility Center Code</label>
                        <input type="text" name="responsibility_center_code" value="{{ old('responsibility_center_code') }}" class="{{ $input }}">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-gray-700">Purpose *</label>
                        <textarea name="purpose" rows="2" class="{{ $input }}">{{ old('purpose') }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">End User</label>
                        <input type="text" name="end_user" value="{{ old('end_user') }}" class="{{ $input }}">
                    </div>
                </div>

                {{-- Lots --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <h3 class="font-semibold text-gray-800">Lots</h3>
                            <p class="text-xs text-gray-500">Name each lot and tick the PRs that belong to it. A PR can only be in one lot.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="text" x-model="search" placeholder="Search PR No. / purpose"
                                   class="rounded-md border-gray-300 text-sm shadow-sm">
                            <button type="button" @click="addLot()" class="rounded-md border px-3 py-1.5 text-sm hover:bg-gray-50">+ Lot</button>
                        </div>
                    </div>

                    <p x-show="!available.length" class="mt-4 text-sm text-amber-700">
                        No PRs available. Only Created PRs that are not grouped and have no RFQ can be grouped.
                    </p>

                    <div class="mt-4 space-y-4">
                        <template x-for="(lot, i) in lots" :key="i">
                            <div class="rounded-md border">
                                <div class="flex flex-wrap items-center gap-3 border-b bg-gray-50 px-4 py-3">
                                    <input type="text" :name="`lots[${i}][name]`" x-model="lot.name"
                                           placeholder="Lot 1 - Token" class="{{ $input }} !mt-0 max-w-sm">
                                    <span class="text-sm text-gray-600">
                                        <span x-text="lot.pr_ids.length"></span> PR(s) &middot;
                                        <span x-text="fmt(lotTotal(lot))"></span>
                                    </span>
                                    <button type="button" @click="removeLot(i)" x-show="lots.length > 1"
                                            class="ml-auto text-sm text-red-600 hover:underline">Remove lot</button>
                                </div>

                                <template x-for="id in lot.pr_ids" :key="id">
                                    <input type="hidden" :name="`lots[${i}][pr_ids][]`" :value="id">
                                </template>

                                <div class="max-h-64 overflow-auto">
                                    <table class="min-w-full text-sm">
                                        <tbody>
                                            <template x-for="p in visible()" :key="p.id">
                                                <tr class="border-t first:border-t-0" :class="takenElsewhere(p.id, i) ? 'opacity-40' : ''">
                                                    <td class="w-8 px-3 py-2 align-top">
                                                        <input type="checkbox" class="rounded border-gray-300"
                                                               :value="String(p.id)" x-model="lot.pr_ids"
                                                               :disabled="takenElsewhere(p.id, i)">
                                                    </td>
                                                    <td class="whitespace-nowrap px-2 py-2 align-top font-medium text-gray-900" x-text="p.pr_no"></td>
                                                    <td class="whitespace-nowrap px-2 py-2 align-top text-gray-600" x-text="p.office"></td>
                                                    <td class="px-2 py-2 align-top text-gray-600" x-text="p.purpose.length > 80 ? p.purpose.slice(0, 80) + '…' : p.purpose"></td>
                                                    <td class="whitespace-nowrap px-2 py-2 text-right align-top" x-text="fmt(p.total)"></td>
                                                    <td class="whitespace-nowrap px-2 py-2 align-top text-xs text-amber-700">
                                                        <span x-show="takenElsewhere(p.id, i)">in another lot</span>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-4 text-right text-sm font-semibold">
                        Group total: <span x-text="fmt(grandTotal)"></span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                            class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Create Group PR</button>
                    <a href="{{ route('purchase-requests.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function groupEditor(available, initial) {
            return {
                available,
                search: '',
                lots: (initial && initial.length ? initial : [{}]).map(l => ({
                    name: l.name ?? '',
                    pr_ids: (l.pr_ids ?? []).map(String),
                })),

                addLot() { this.lots.push({ name: '', pr_ids: [] }); },
                removeLot(i) { this.lots.splice(i, 1); },

                takenElsewhere(id, i) {
                    return this.lots.some((l, k) => k !== i && l.pr_ids.includes(String(id)));
                },
                visible() {
                    const s = this.search.trim().toLowerCase();
                    if (!s) return this.available;
                    return this.available.filter(p => `${p.pr_no} ${p.purpose} ${p.office}`.toLowerCase().includes(s));
                },
                lotTotal(lot) {
                    return lot.pr_ids.reduce((sum, id) => {
                        const p = this.available.find(x => String(x.id) === String(id));
                        return sum + (p ? p.total : 0);
                    }, 0);
                },
                get grandTotal() { return this.lots.reduce((s, l) => s + this.lotTotal(l), 0); },
                fmt(v) {
                    return v.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },
            };
        }
    </script>
</x-app-layout>
