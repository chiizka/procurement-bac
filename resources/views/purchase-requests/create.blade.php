<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">New Purchase Request</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @include('purchase-requests._form', [
                'pr' => null,
                'action' => route('purchase-requests.store'),
                'method' => 'POST',
            ])
        </div>
    </div>
</x-app-layout>