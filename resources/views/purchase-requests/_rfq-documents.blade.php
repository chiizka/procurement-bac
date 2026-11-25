<section class="border-t border-gray-200 py-5" aria-labelledby="rfq-{{ $rfq->id }}">
    <h5 id="rfq-{{ $rfq->id }}" class="font-semibold text-gray-900">{{ $rfq->lot_label ?: 'RFQ '.$rfq->rfq_no }}</h5>
    <div class="mt-4 divide-y divide-gray-100 sm:pl-4">
        <div class="flex flex-wrap items-center justify-between gap-4 py-3">
            <div>
                <div class="flex flex-wrap items-center gap-2"><span class="text-sm font-medium text-gray-900">Request for Quotation</span><x-status-chip :status="$rfq->status->value" /></div>
                <p class="mt-1 text-sm text-gray-500">RFQ {{ $rfq->rfq_no }} &middot; PhP {{ number_format($rfq->total_amount, 2) }} &middot; {{ $rfq->item_count }} items</p>
            </div>
            <div class="pr-document-actions">
                <x-document-action :href="route('rfqs.print', $rfq)" preview :label="'Preview RFQ '.$rfq->rfq_no">Preview</x-document-action>
                @if ($canWrite && ! $rfq->twgReport)
                    <x-document-action :href="route('rfqs.edit', $rfq)">{{ $rfq->status === \App\Enums\DocStatus::Draft ? 'Continue draft' : 'Edit' }}</x-document-action>
                @else
                    <span class="text-sm text-gray-500">{{ $canWrite ? 'Locked by TWG report' : 'Read-only' }}</span>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-4 py-3">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-medium text-gray-900">TWG Evaluation Report</span>
                    @if ($rfq->twgReport)<x-status-chip :status="$rfq->twgReport->status->value" />
                    @else <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Not started</span>@endif
                </div>
                <p class="mt-1 text-sm text-gray-500">{{ $rfq->twgReport ? 'TWG '.$rfq->twgReport->twg_no : ($rfq->status === \App\Enums\DocStatus::Draft ? 'Create this RFQ before starting a TWG report.' : 'No TWG report yet.') }}</p>
            </div>
            <div class="pr-document-actions">
                @if ($rfq->twgReport)
                    <x-document-action :href="route('twg-reports.print', $rfq->twgReport)" preview :label="'Preview TWG '.$rfq->twgReport->twg_no">Preview</x-document-action>
                    @if ($canWrite && ! $rfq->twgReport->abstractOfQuotation)
                        <x-document-action :href="route('twg-reports.edit', $rfq->twgReport)">{{ $rfq->twgReport->status === \App\Enums\DocStatus::Draft ? 'Continue draft' : 'Edit' }}</x-document-action>
                    @else <span class="text-sm text-gray-500">{{ $canWrite ? 'Locked by Abstract' : 'Read-only' }}</span>@endif
                @elseif ($canWrite && $rfq->status === \App\Enums\DocStatus::Created)
                    <x-document-action :href="route('rfqs.twg-reports.create', $rfq)">Create TWG</x-document-action>
                @endif
            </div>
        </div>
        @php($abstract = $rfq->twgReport?->abstractOfQuotation)
        <div class="flex flex-wrap items-center justify-between gap-4 py-3">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-medium text-gray-900">Abstract of Quotations</span>
                    @if ($abstract)<x-status-chip :status="$abstract->status->value" />
                    @else <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Not started</span>@endif
                </div>
                <p class="mt-1 text-sm text-gray-500">{{ $abstract ? 'Abstract '.$abstract->abstract_no : 'Available after the TWG report is Created.' }}</p>
            </div>
            <div class="pr-document-actions">
                @if ($abstract)
                    <x-document-action :href="route('abstracts.print', $abstract)" preview :label="'Preview Abstract '.$abstract->abstract_no">Preview</x-document-action>
                    @if ($canWrite)<x-document-action :href="route('abstracts.edit', $abstract)">{{ $abstract->status === \App\Enums\DocStatus::Draft ? 'Continue draft' : 'Edit' }}</x-document-action>@endif
                @elseif ($canWrite && $rfq->twgReport?->status === \App\Enums\DocStatus::Created)
                    <x-document-action :href="route('twg-reports.abstracts.create', $rfq->twgReport)">Create Abstract</x-document-action>
                @endif
            </div>
        </div>
    </div>
</section>
