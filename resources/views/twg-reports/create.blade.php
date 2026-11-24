<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">New TWG Report &mdash; RFQ {{ $rfq->rfq_no }}</h2>
            <a href="{{ route('rfqs.show', $rfq) }}" class="text-sm text-gray-600 hover:underline">Back to RFQ</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('twg-reports._form', [
                'action' => route('rfqs.twg-reports.store', $rfq),
                'method' => 'POST',
            ])
        </div>
    </div>
</x-app-layout>
