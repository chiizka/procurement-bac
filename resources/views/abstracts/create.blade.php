<x-app-layout>
    <x-slot name="header"><div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-semibold text-gray-800">New Abstract</h2>
        <a href="{{ route('purchase-requests.show', $twg->rfq->purchase_request_id) }}" class="text-sm text-gray-600 hover:underline">Back to PR overview</a>
    </div></x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @include('abstracts._form', ['action' => route('twg-reports.abstracts.store', $twg), 'method' => 'POST'])
    </div></div>
</x-app-layout>
