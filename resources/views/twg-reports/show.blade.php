<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-500">
                    <a href="{{ route('rfqs.show', $twg->rfq) }}" class="hover:underline">RFQ {{ $twg->rfq->rfq_no }}</a>
                    &rsaquo; TWG Report {{ $twg->twg_no }}
                </p>
                <div class="flex items-center gap-3">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">TWG Report {{ $twg->twg_no }}</h2>
                    <x-status-chip :status="$twg->status->value" />
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('twg-reports.print', $twg) }}"
                   class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Print</a>
                <a href="{{ route('rfqs.show', $twg->rfq) }}" class="text-sm text-gray-600 hover:underline">Back to RFQ</a>
            </div>
        </div>
    </x-slot>

    @php
        $rfq = $twg->rfq;
        $suppliers = $twg->suppliers;
        $priceMaps = $suppliers->mapWithKeys(fn ($s) => [$s->id => $s->quotes->keyBy('rfq_item_id')]);
        $canWrite = auth()->user()->hasRole(\App\Enums\Role::Bac, \App\Enums\Role::Admin);
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3 text-sm">
                    <div><dt class="text-gray-500">TWG Report No.</dt><dd>{{ $twg->twg_no }}</dd></div>
                    <div><dt class="text-gray-500">Date</dt><dd>{{ $twg->twg_date->format('F d, Y') }}</dd></div>
                    <div><dt class="text-gray-500">RFQ</dt><dd>{{ $rfq->rfq_no }}{{ $rfq->lot_label ? ' — '.$rfq->lot_label : '' }}</dd></div>
                    <div class="sm:col-span-3"><dt class="text-gray-500">Name of Project</dt><dd class="whitespace-pre-line">{{ $twg->project_name }}</dd></div>
                    <div><dt class="text-gray-500">Approved Budget for the Contract</dt><dd>PhP{{ number_format($twg->abc, 2) }}</dd></div>
                    <div><dt class="text-gray-500">Method of Procurement</dt><dd>{{ $twg->procurement_mode }}</dd></div>
                    <div><dt class="text-gray-500">Criteria for Evaluation</dt><dd>{{ $twg->criteria }}</dd></div>
                    <div><dt class="text-gray-500">PhilGEPS Reference Number</dt><dd>{{ $twg->philgeps_ref ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">Date of PhilGEPS Posting</dt><dd>{{ $twg->philgeps_posted ?: '—' }}</dd></div>
                    <div><dt class="text-gray-500">Number of Quotations Received</dt><dd>{{ $twg->quotationsLabel() }}</dd></div>
                </dl>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Qty / Unit</th>
                            <th class="px-4 py-3">Item/s description</th>
                            @foreach ($suppliers as $s)
                                <th class="px-4 py-3 text-right">{{ $s->name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($rfq->items as $item)
                            @if ($item->line_type === \App\Enums\LineType::Item)
                                <tr class="align-top">
                                    <td class="whitespace-nowrap px-4 py-2">{{ $item->quantity !== null ? rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') : '' }} {{ $item->unit }}</td>
                                    <td class="whitespace-pre-line px-4 py-2">{{ $item->description }}</td>
                                    @foreach ($suppliers as $s)
                                        @php($quote = $priceMaps[$s->id]->get($item->id))
                                        <td class="px-4 py-2 text-right whitespace-nowrap">
                                            @if ($quote)
                                                {{ number_format(round((float) $item->quantity * (float) $quote->unit_price, 2), 2) }}
                                            @else
                                                <span class="text-gray-400">No Quotation</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @elseif ($item->line_type === \App\Enums\LineType::LotHeader)
                                <tr class="bg-gray-50"><td colspan="{{ 2 + $suppliers->count() }}" class="px-4 py-2 font-semibold">{{ $item->description }}</td></tr>
                            @else
                                <tr><td></td><td colspan="{{ 1 + $suppliers->count() }}" class="px-4 py-2 italic text-gray-600">{{ $item->description }}</td></tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 font-semibold">
                            <td></td><td class="px-4 py-3">Total</td>
                            @foreach ($suppliers as $s)
                                @php($t = $s->grandTotal($rfq->items))
                                <td class="px-4 py-3 text-right">
                                    {{ $t === null ? '-' : number_format($t, 2) }}
                                    @if ($t !== null && $t > (float) $twg->abc)
                                        <div class="text-xs font-normal text-amber-700">above ABC</div>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td></td><td class="px-4 py-3 font-semibold">Remarks</td>
                            @foreach ($suppliers as $s)
                                <td class="px-4 py-3 text-right">{{ $s->remarks ?: '—' }}</td>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm">
                <h3 class="font-semibold text-gray-800">TWG findings</h3>
                <p class="mt-1 text-gray-500">Based on the above price quotations offered by different leading dealers/suppliers, the TWG found that:</p>
                <ul class="mt-3 space-y-1">
                    @forelse ($twg->findings ?? [] as $f)
                        <li><span class="font-medium">{{ $f['scope'] ?? '' }}</span> &mdash; <span class="font-semibold">{{ $f['supplier'] ?? '' }}</span> {{ $f['text'] ?? '' }}</li>
                    @empty
                        <li class="text-gray-400">No findings entered.</li>
                    @endforelse
                </ul>

                <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                    @foreach (['evaluated' => 'Evaluated by', 'reviewed' => 'Reviewed by'] as $group => $label)
                        <div>
                            <p class="text-gray-500">{{ $label }}:</p>
                            @foreach ($twg->signatoriesIn($group) as $sg)
                                <p class="mt-2 font-semibold">{{ $sg['name'] }}</p>
                                <p class="text-gray-600">{{ $sg['position'] ?? '' }}</p>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>

            @if ($canWrite)
                <div class="flex items-center gap-3">
                    <a href="{{ route('twg-reports.edit', $twg) }}"
                       class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">Edit</a>

                    @if ($twg->status === \App\Enums\DocStatus::Draft)
                        <form method="POST" action="{{ route('twg-reports.destroy', $twg) }}"
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
