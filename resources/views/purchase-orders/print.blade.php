<x-app-layout>
    <x-slot name="header"><div class="flex flex-wrap items-center justify-between gap-3"><h2 class="text-xl font-semibold text-gray-800">Purchase Order {{ $po->po_no }} &mdash; {{ ucfirst($po->status->value) }}</h2><div class="flex flex-wrap items-center gap-3"><a href="{{ route('purchase-requests.show', $po->abstractOfQuotation->twgReport->rfq->purchase_request_id) }}" class="text-sm text-gray-600 hover:underline">Back to PR overview</a><x-primary-button type="button" onclick="window.print()">Print / Save as PDF</x-primary-button></div></div></x-slot>
    <div class="po-preview overflow-x-auto px-4 py-8">@include('purchase-orders._paper')</div>
</x-app-layout>
