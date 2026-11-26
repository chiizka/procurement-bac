<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">New Purchase Order</h2></x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @include('purchase-orders._form', ['action' => route('abstracts.purchase-orders.store', $abstract), 'method' => 'POST'])
    </div></div>
</x-app-layout>
