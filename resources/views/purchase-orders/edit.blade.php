<x-app-layout>
    <x-slot name="header"><div class="flex flex-wrap items-center gap-3"><h2 class="text-xl font-semibold text-gray-800">Purchase Order {{ $po->po_no }}</h2><x-status-chip :status="$po->status->value" /></div></x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @include('purchase-orders._form', ['action' => route('purchase-orders.update', $po), 'method' => 'PUT'])
    </div></div>
</x-app-layout>
