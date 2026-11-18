<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">New RFQ &mdash; PR {{ $pr->pr_no }}</h2>
            <a href="{{ route('purchase-requests.show', $pr) }}" class="text-sm text-gray-600 hover:underline">Back to PR</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @include('rfqs._form', [
                'action' => route('purchase-requests.rfqs.store', $pr),
                'method' => 'POST',
            ])
        </div>
    </div>
</x-app-layout>
