<x-app-layout>
    <x-slot name="header"><div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800">Edit Abstract {{ $abstract->abstract_no }}</h2>
        <a href="{{ route('purchase-requests.show', $twg->rfq->purchase_request_id) }}" class="text-sm text-gray-600 hover:underline">Back to PR overview</a>
    </div></x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @include('abstracts._form', ['action' => route('abstracts.update', $abstract), 'method' => 'PUT'])
    </div></div>
</x-app-layout>
