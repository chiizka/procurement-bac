<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit PR {{ $pr->pr_no }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @include('purchase-requests._form', [
                'pr' => $pr,
                'action' => route('purchase-requests.update', $pr),
                'method' => 'PUT',
            ])
        </div>
    </div>
</x-app-layout>