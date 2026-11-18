<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Purchase Requests</h2>
            @if (auth()->user()->hasRole(\App\Enums\Role::Bac, \App\Enums\Role::Admin))
                <a href="{{ route('purchase-requests.create') }}"
                   class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">New PR</a>
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
                            <th class="px-4 py-3">PR</th>
                            <th class="px-4 py-3">RFQ</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($purchaseRequests as $pr)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $pr->pr_no }}</td>
                                <td class="px-4 py-3">{{ $pr->pr_date->format('M d, Y') }}</td>
                                <td class="px-4 py-3">{{ $pr->office_section }}</td>
                                <td class="px-4 py-3">{{ \Illuminate\Support\Str::limit($pr->purpose, 60) }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($pr->total_amount, 2) }}</td>
                                <td class="px-4 py-3"><x-status-chip :status="$pr->status->value" /></td>
                                <td class="px-4 py-3">
                                    @if ($pr->rfqs_count === 0)
                                        <span class="text-xs text-gray-400">None</span>
                                    @else
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                            {{ $pr->rfqs_created_count === $pr->rfqs_count ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700' }}">
                                            RFQ {{ $pr->rfqs_created_count }}/{{ $pr->rfqs_count }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('purchase-requests.show', $pr) }}" class="text-indigo-600 hover:underline">View</a>
                                    @if (auth()->user()->hasRole(\App\Enums\Role::Bac, \App\Enums\Role::Admin))
                                        <a href="{{ route('purchase-requests.edit', $pr) }}" class="ml-3 text-indigo-600 hover:underline">Edit</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No purchase requests yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $purchaseRequests->links() }}</div>
        </div>
    </div>
</x-app-layout>