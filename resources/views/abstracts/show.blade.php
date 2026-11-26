<x-app-layout>
    <x-slot name="header"><div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3"><h2 class="text-xl font-semibold text-gray-800">Abstract {{ $abstract->abstract_no }}</h2><x-status-chip :status="$abstract->status->value" /></div>
        <a href="{{ route('purchase-requests.show', $twg->rfq->purchase_request_id) }}" class="text-sm text-gray-600 hover:underline">Back to PR overview</a>
    </div></x-slot>
    <div class="py-8"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))<div role="status" class="rounded-md bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</div>@endif
        <div class="flex flex-wrap items-center gap-3">
            <x-document-action :href="route('abstracts.print', $abstract)" preview label="Preview Abstract">Preview</x-document-action>
            @if (auth()->user()->hasRole(\App\Enums\Role::Bac, \App\Enums\Role::Admin) && ! $abstract->purchaseOrder)
                <x-document-action :href="route('abstracts.edit', $abstract)">{{ $abstract->status === \App\Enums\DocStatus::Draft ? 'Continue draft' : 'Edit' }}</x-document-action>
                @if ($abstract->status === \App\Enums\DocStatus::Draft)<form method="POST" action="{{ route('abstracts.destroy', $abstract) }}" onsubmit="return confirm('Delete this Abstract draft? The TWG report will become editable again.')">@csrf @method('DELETE')<button class="rounded-md border border-red-300 px-4 py-2 text-sm text-red-700 hover:bg-red-50">Delete draft</button></form>@endif
            @endif
        </div>
        <div class="overflow-x-auto rounded-lg bg-white p-4 shadow-sm">@include('abstracts._paper')</div>
        <p class="text-sm text-gray-500">The source TWG report is locked while this Abstract exists.</p>
    </div></div>
</x-app-layout>
