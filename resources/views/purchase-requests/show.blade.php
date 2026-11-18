<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">PR {{ $pr->pr_no }}</h2>
                <x-status-chip :status="$pr->status->value" />
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('purchase-requests.print', $pr) }}"
                   class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Print</a>
                <a href="{{ route('purchase-requests.index') }}" class="text-sm text-gray-600 hover:underline">Back to list</a>
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
                    <div><dt class="text-gray-500">PR Date</dt><dd>{{ $pr->pr_date->format('M d, Y') }}</dd></div>
                    <div><dt class="text-gray-500">Entity</dt><dd>{{ $pr->entity_name }}</dd></div>
                    <div><dt class="text-gray-500">Fund Cluster</dt><dd>{{ $pr->fund_cluster ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">Office / Section</dt><dd>{{ $pr->office_section }}</dd></div>
                    <div><dt class="text-gray-500">Responsibility Center Code</dt><dd>{{ $pr->responsibility_center_code ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">End User</dt><dd>{{ $pr->end_user ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">Requested by</dt><dd>{{ $pr->requested_by ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">Approved by</dt><dd>{{ $pr->approved_by ?: '—' }}</dd></div>
                    <div class="sm:col-span-3"><dt class="text-gray-500">Purpose</dt><dd class="whitespace-pre-line">{{ $pr->purpose }}</dd></div>
                </dl>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Stock No.</th>
                            <th class="px-4 py-3">Unit</th>
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3 text-right">Qty</th>
                            <th class="px-4 py-3 text-right">Unit Cost</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($pr->items as $item)
                            @if ($item->line_type === \App\Enums\LineType::Item)
                                <tr>
                                    <td class="px-4 py-2">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-2">{{ $item->stock_no }}</td>
                                    <td class="px-4 py-2">{{ $item->unit }}</td>
                                    <td class="px-4 py-2 whitespace-pre-line">{{ $item->description }}</td>
                                    <td class="px-4 py-2 text-right">{{ $item->quantity !== null ? number_format($item->quantity, 2) : '' }}</td>
                                    <td class="px-4 py-2 text-right">{{ $item->unit_cost !== null ? number_format($item->unit_cost, 2) : '' }}</td>
                                    <td class="px-4 py-2 text-right">{{ $item->amount !== null ? number_format($item->amount, 2) : '' }}</td>
                                </tr>
                            @elseif ($item->line_type === \App\Enums\LineType::LotHeader)
                                <tr class="bg-gray-50"><td colspan="7" class="px-4 py-2 font-semibold">{{ $item->description }}</td></tr>
                            @else
                                <tr><td></td><td colspan="6" class="px-4 py-2 italic text-gray-600">{{ $item->description }}</td></tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 font-semibold">
                            <td colspan="6" class="px-4 py-3 text-right">Total</td>
                            <td class="px-4 py-3 text-right">{{ number_format($pr->total_amount, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if (auth()->user()->hasRole(\App\Enums\Role::Bac, \App\Enums\Role::Admin))
                <div class="flex items-center gap-3">
                    <a href="{{ route('purchase-requests.edit', $pr) }}"
                       class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Edit</a>

                    @if ($pr->status === \App\Enums\DocStatus::Draft)
                        <form method="POST" action="{{ route('purchase-requests.destroy', $pr) }}"
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
