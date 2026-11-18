<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit RFQ {{ $rfq->rfq_no }}</h2>
                <x-status-chip :status="$rfq->status->value" />
            </div>
            <a href="{{ route('rfqs.show', $rfq) }}" class="text-sm text-gray-600 hover:underline">Back to RFQ</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @include('rfqs._form', [
                'action' => route('rfqs.update', $rfq),
                'method' => 'PUT',
            ])
        </div>
    </div>
</x-app-layout>
