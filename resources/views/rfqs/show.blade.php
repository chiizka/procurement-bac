<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500">
                    <a href="{{ route('purchase-requests.show', $rfq->purchaseRequest) }}" class="hover:underline">PR {{ $rfq->purchaseRequest->pr_no }}</a>
                    &rsaquo; RFQ {{ $rfq->rfq_no }}
                </p>
                <div class="flex items-center gap-3">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">RFQ {{ $rfq->rfq_no }}</h2>
                    <x-status-chip :status="$rfq->status->value" />
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('rfqs.print', $rfq) }}"
                   class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Print</a>
                <a href="{{ route('purchase-requests.show', $rfq->purchaseRequest) }}" class="text-sm text-gray-600 hover:underline">Back to PR</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3 text-sm">
                    <div><dt class="text-gray-500">RFQ Date</dt><dd>{{ $rfq->rfq_date->format('M d, Y') }}</dd></div>
                    <div><dt class="text-gray-500">Closing</dt><dd>{{ $rfq->deadline_at?->format('M d, Y g:i A') ?? '—' }}</dd></div>
                    <div><dt class="text-gray-500">Lot</dt><dd>{{ $rfq->lot_label ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">PR No.</dt><dd>{{ $rfq->purchaseRequest->pr_no }}</dd></div>
                    <div><dt class="text-gray-500">End User</dt><dd>{{ $rfq->purchaseRequest->end_user ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">BAC Chairperson</dt><dd>{{ $rfq->bac_chairperson ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">Contract Duration</dt><dd>{{ $rfq->contract_duration ?: '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-gray-500">Delivery Area</dt><dd>{{ $rfq->delivery_area ?: '—' }}</dd></div>
                    <div class="sm:col-span-3"><dt class="text-gray-500">Purpose / Project title</dt><dd class="whitespace-pre-line">{{ $rfq->purpose }}</dd></div>
                </dl>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Qty</th>
                            <th class="px-4 py-3">Unit</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3 text-right">ABC Unit Cost</th>
                            <th class="px-4 py-3 text-right">ABC Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($rfq->items as $item)
                            @if ($item->line_type === \App\Enums\LineType::Item)
                                <tr>
                                    <td class="px-4 py-2">{{ $item->quantity !== null ? rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') : '' }}</td>
                                    <td class="px-4 py-2">{{ $item->unit }}</td>
                                    <td class="px-4 py-2 whitespace-pre-line">{{ $item->description }}</td>
                                    <td class="px-4 py-2 text-right">{{ $item->unit_cost !== null ? '₱' . number_format($item->unit_cost, 2) : '' }}</td>
                                    <td class="px-4 py-2 text-right">{{ $item->amount !== null ? '₱' . number_format($item->amount, 2) : '' }}</td>
                                </tr>
                            @elseif ($item->line_type === \App\Enums\LineType::LotHeader)
                                <tr class="bg-gray-50"><td colspan="5" class="px-4 py-2 font-semibold">{{ $item->description }}</td></tr>
                            @else
                                <tr><td></td><td colspan="4" class="px-4 py-2 italic text-gray-600">{{ $item->description }}</td></tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 font-semibold">
                            <td colspan="4" class="px-4 py-3 text-right">Total ABC</td>
                            <td class="px-4 py-3 text-right">₱{{ number_format($rfq->total_amount, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if ($rfq->status === \App\Enums\DocStatus::Created)
                <div class="bg-white shadow-sm sm:rounded-lg p-6 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800">TWG Report</h3>
                    @if ($rfq->twgReport)
                        <a href="{{ route('twg-reports.show', $rfq->twgReport) }}" class="text-sm text-indigo-600 hover:underline">
                            View TWG {{ $rfq->twgReport->twg_no }}
                        </a>
                    @elseif (auth()->user()->hasRole(\App\Enums\Role::Bac, \App\Enums\Role::Admin))
                        <a href="{{ route('rfqs.twg-reports.create', $rfq) }}"
                        class="rounded-md bg-gray-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-gray-700">+ Create TWG Report</a>
                    @endif
                </div>
            @endif

            @if (auth()->user()->hasRole(\App\Enums\Role::Bac, \App\Enums\Role::Admin))
                <div class="flex items-center gap-3">
                    <a href="{{ route('rfqs.edit', $rfq) }}"
                       class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Edit</a>

                    @if ($rfq->status === \App\Enums\DocStatus::Draft)
                        <form method="POST" action="{{ route('rfqs.destroy', $rfq) }}"
                              onsubmit="return confirm('Delete this draft?')">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">Delete draft</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
