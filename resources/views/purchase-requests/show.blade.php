<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">PR {{ $pr->pr_no }}</h2>
            <a href="{{ route('purchase-requests.index') }}" class="text-sm text-gray-600 hover:underline">Back to list</a>
        </div>
    </x-slot>
    @php
        $canWrite = auth()->user()->hasRole(\App\Enums\Role::Bac, \App\Enums\Role::Admin);
        $isCreatedPr = $pr->status === \App\Enums\DocStatus::Created;
        $canEditPr = $canWrite && ! $pr->group_id && (! $pr->is_group || $pr->rfqs->isEmpty());
        $canCreateRfq = $canWrite && $isCreatedPr && ! $pr->group_id;
        $cov = $pr->coverage();
    @endphp
    <style>
        .pr-document-actions { display: grid; grid-template-columns: 8rem minmax(0, 1fr); align-items: center; gap: .75rem; width: 21rem; max-width: 100%; }
        .pr-document-actions > a[target] { width: 100%; }
        .pr-document-actions > a:not([target]) { justify-self: start; }
        .pr-document-actions > :only-child:not([target]) { grid-column: 2; }
        .pr-document-actions > span { line-height: 1.4; }
        @media (max-width: 639px) { .pr-document-actions { width: 100%; grid-template-columns: 7.5rem minmax(0, 1fr); } }
    </style>
    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div role="status" class="rounded-md bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif
            @if ($pr->group)
                <div class="rounded-md bg-amber-50 p-4 text-sm text-amber-800">
                    Part of group PR <a href="{{ route('purchase-requests.show', $pr->group) }}" class="font-medium underline">{{ $pr->group->pr_no }}</a>.
                    This PR is locked. Open the group overview for its RFQs and TWG reports.
                </div>
            @endif
            <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6" aria-labelledby="key-details">
                <h3 id="key-details" class="text-lg font-semibold text-gray-900">Key details</h3>
                <p class="mt-4 whitespace-pre-line text-gray-800">{{ $pr->purpose }}</p>
                <p class="mt-4 font-semibold text-gray-900">{{ $pr->office_section }} &middot; PhP {{ number_format($pr->total_amount, 2) }}</p>
                <p class="mt-2 text-sm text-gray-500">{{ $pr->pr_date->format('M d, Y') }}@if ($pr->is_group) &middot; Group PR @endif</p>
                <details class="mt-5 border-t border-gray-200 pt-4">
                    <summary class="cursor-pointer rounded-md py-2 font-semibold text-gray-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-800">PR details and items</summary>
                <div class="py-4">
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

                <div class="mt-2 overflow-x-auto">
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

                </details>
            </section>
            <section class="rounded-xl border border-gray-200 bg-white p-5 sm:p-6" aria-labelledby="documents-heading">
                <h3 id="documents-heading" class="text-lg font-semibold text-gray-900">Documents</h3>
                <div class="mt-4 flex flex-wrap items-center justify-between gap-4 border-t border-gray-200 py-5">
                    <div class="flex flex-wrap items-center gap-2"><h4 class="font-semibold text-gray-900">Purchase Request</h4><x-status-chip :status="$pr->status->value" /></div>
                    <div class="pr-document-actions">
                        <x-document-action :href="route('purchase-requests.print', $pr)" preview label="Preview purchase request">Preview</x-document-action>
                        @if ($canEditPr)
                            <x-document-action :href="route('purchase-requests.edit', $pr)">{{ $isCreatedPr ? 'Edit' : 'Continue draft' }}</x-document-action>
                        @else
                            <span class="text-sm text-gray-500">{{ $canWrite ? ($pr->group_id ? 'Locked by group PR' : 'Locked by RFQs') : 'Read-only' }}</span>
                        @endif
                    </div>
                </div>
                @unless ($pr->group_id)
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 py-4">
                        <div><h4 class="font-semibold text-gray-900">RFQs and TWG reports</h4><p class="mt-1 text-sm text-gray-500">Coverage: {{ $cov['covered'] }} of {{ $cov['total'] }} PR items are on an RFQ.</p></div>
                        @if ($canCreateRfq && $pr->rfqs->isNotEmpty())
                            <x-document-action :href="route('purchase-requests.rfqs.create', $pr)">New RFQ</x-document-action>
                        @endif
                    </div>
                    @foreach ($pr->rfqs as $rfq)
                        @include('purchase-requests._rfq-documents', ['rfq' => $rfq])
                    @endforeach
                    @if ($pr->is_group)
                        @foreach ($pr->lots() as $lot)
                            @if (! $pr->rfqs->contains(fn ($rfq) => $rfq->items->contains(fn ($item) => in_array($item->pr_item_id, $lot['item_ids']))))
                                <div class="flex flex-wrap items-center justify-between gap-4 border-t border-gray-200 py-5">
                                    <div><h5 class="font-semibold text-gray-900">{{ $lot['name'] }}</h5><p class="mt-1 text-sm text-gray-500">Request for Quotation &middot; Not started</p></div>
                                    @if ($canCreateRfq)<x-document-action :href="route('purchase-requests.rfqs.create', $pr)">Create RFQ</x-document-action>@endif
                                </div>
                            @endif
                        @endforeach
                    @elseif ($pr->rfqs->isEmpty())
                        <div class="flex flex-wrap items-center justify-between gap-4 border-t border-gray-200 py-5">
                            <div><h5 class="font-semibold text-gray-900">Request for Quotation</h5><p class="mt-1 text-sm text-gray-500">{{ $isCreatedPr ? 'Not started' : 'Create this PR before starting an RFQ.' }}</p></div>
                            @if ($canCreateRfq)<x-document-action :href="route('purchase-requests.rfqs.create', $pr)">Create RFQ</x-document-action>@endif
                        </div>
                    @endif
                @endunless
                @foreach (['Purchase Order', 'Job Order'] as $document)
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 py-5">
                        <h4 class="font-semibold text-gray-900">{{ $document }}</h4>
                        <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Coming soon</span>
                    </div>
                @endforeach
            </section>
            @if ($pr->is_group)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800">PRs in this group</h3>
                        @if (auth()->user()->hasRole(\App\Enums\Role::Bac, \App\Enums\Role::Admin) && $pr->rfqs()->withTrashed()->doesntExist())
                            <form method="POST" action="{{ route('group-purchase-requests.destroy', $pr) }}" onsubmit="return confirm('Ungroup? The group PR will be deleted and its PRs become solo again.')">
                                @csrf @method('DELETE')
                                <button class="text-sm text-red-600 hover:underline">Ungroup</button>
                            </form>
                        @endif
                    </div>
                    <ul class="mt-3 divide-y text-sm">
                        @foreach ($pr->members as $m)
                            <li class="flex justify-between py-2">
                                <a href="{{ route('purchase-requests.show', $m) }}" class="text-indigo-600 hover:underline">{{ $m->pr_no }}</a>
                                <span class="text-gray-600">{{ \Illuminate\Support\Str::limit($m->purpose, 70) }}</span>
                                <span>{{ number_format($m->total_amount, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif


            @if ($canWrite && $pr->status === \App\Enums\DocStatus::Draft)
                <form method="POST" action="{{ route('purchase-requests.destroy', $pr) }}" onsubmit="return confirm('Delete this draft?')">
                    @csrf @method('DELETE')
                    <button class="rounded-md border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50">Delete draft</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
