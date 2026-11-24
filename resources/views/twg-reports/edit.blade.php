<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit TWG Report {{ $twg->twg_no }}</h2>
                <x-status-chip :status="$twg->status->value" />
            </div>
            <a href="{{ route('twg-reports.show', $twg) }}" class="text-sm text-gray-600 hover:underline">Back to report</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @include('twg-reports._form', [
                'action' => route('twg-reports.update', $twg),
                'method' => 'PUT',
            ])
        </div>
    </div>
</x-app-layout>
