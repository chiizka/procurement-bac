<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Purchase Requests</h2>
            @if (auth()->user()->hasRole(\App\Enums\Role::Bac, \App\Enums\Role::Admin))
                <div class="flex items-center gap-2">
                    <a href="{{ route('purchase-requests.create') }}"
                       class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">New PR</a>
                    <a href="{{ route('group-purchase-requests.create') }}"
                       class="rounded-md border px-4 py-2 text-sm font-medium hover:bg-gray-50">Group PRs</a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">PR No.</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Office / Section</th>
                            <th class="px-4 py-3">Purpose</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($purchaseRequests as $pr)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ $pr->pr_no }}
                                    @if ($pr->is_group)
                                        <span class="ml-2 rounded bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">Group</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $pr->pr_date->format('M d, Y') }}</td>
                                <td class="px-4 py-3">{{ $pr->office_section }}</td>
                                <td class="px-4 py-3">{{ \Illuminate\Support\Str::limit($pr->purpose, 60) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($pr->total_amount, 2) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center gap-1 whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium {{ $pr->status === \App\Enums\DocStatus::Created ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}" aria-label="PR {{ $pr->status->value }}" title="PR {{ $pr->status->value }}">
                                            PR
                                            @if ($pr->status === \App\Enums\DocStatus::Created)
                                                <svg aria-hidden="true" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="m4 10 4 4 8-8" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                            @else
                                                Draft
                                            @endif
                                        </span>
                                        <span class="inline-flex whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium {{ $pr->rfqs_count === 0 ? 'bg-gray-100 text-gray-600' : ($pr->rfqs_created_count === $pr->rfqs_count ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700') }}" title="{{ $pr->rfqs_created_count }} created RFQs out of {{ $pr->rfqs_count }} total">
                                            RFQ {{ $pr->rfqs_created_count }}/{{ $pr->rfqs_count }}
                                        </span>
                                        <span class="inline-flex whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium {{ $pr->rfqs_count === 0 ? 'bg-gray-100 text-gray-600' : ($pr->twgs_created_count === $pr->rfqs_count ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700') }}" title="{{ $pr->twgs_created_count }} created TWG reports out of {{ $pr->rfqs_count }} RFQs" aria-label="{{ $pr->twgs_created_count }} created TWG reports out of {{ $pr->rfqs_count }} RFQs">
                                            TWG {{ $pr->twgs_created_count }}/{{ $pr->rfqs_count }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('purchase-requests.show', $pr) }}" class="text-indigo-600 hover:underline">View</a>
                                    @if (auth()->user()->hasRole(\App\Enums\Role::Bac, \App\Enums\Role::Admin) && (! $pr->is_group || $pr->rfqs_count === 0))
                                        <a href="{{ route('purchase-requests.edit', $pr) }}" class="ml-3 text-indigo-600 hover:underline">Edit</a>
                                    @endif
                                </td>
                            </tr>

                            {{-- Lots of a group PR, one indented row each --}}
                            @if ($pr->is_group)
                                @foreach ($pr->lots() as $lot)
                                    <tr class="bg-gray-50 text-xs text-gray-700">
                                        <td class="py-2 pl-10 pr-4 font-medium">
                                            @php
                                                $lotRfqs = $pr->rfqs->filter(fn ($rfq) => $rfq->items->contains(fn ($item) => in_array($item->pr_item_id, $lot['item_ids'])));
                                            @endphp
                                            @forelse ($lotRfqs as $lotRfq)
                                                <a href="{{ route('rfqs.show', $lotRfq) }}" class="block text-indigo-600 hover:underline">RFQ {{ $lotRfq->rfq_no }}</a>
                                            @empty
                                                <span class="text-gray-500">No RFQ yet</span>
                                            @endforelse
                                        </td>
                                        <td colspan="2"></td>
                                        <td class="px-4 py-2">{{ $lot['name'] }}</td>
                                        <td class="px-4 py-2 text-right">{{ number_format($lot['total'], 2) }}</td>
                                        <td colspan="2"></td>
                                    </tr>
                                @endforeach
                            @endif
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No purchase requests yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $purchaseRequests->links() }}</div>
        </div>
    </div>
</x-app-layout>