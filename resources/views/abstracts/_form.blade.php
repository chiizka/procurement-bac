@php
    $input = 'block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500';
    $locked = $abstract?->status === \App\Enums\DocStatus::Created;
    $evaluations = old('evaluations', $abstract?->evaluations ?? []);
    $signatories = old('signatories', $abstract?->signatories ?? $lastSignatories ?? [
        ['name' => '', 'position' => '', 'role' => 'member'],
        ['name' => '', 'position' => '', 'role' => 'member'],
        ['name' => '', 'position' => '', 'role' => 'vice_chairperson'],
        ['name' => '', 'position' => '', 'role' => 'member'],
        ['name' => '', 'position' => '', 'role' => 'chairperson'],
    ]);
@endphp
<form method="POST" action="{{ $action }}" class="space-y-6"
      x-data="{ recommendation: @js(old('recommendation', $abstract?->recommendation ?? '')), signatories: @js($signatories) }">
    @csrf
    @if ($method === 'PUT') @method('PUT') @endif
    @if ($errors->any())
        <div role="alert" class="rounded-md bg-red-50 p-4 text-sm text-red-800">
            <p class="font-semibold">Please fix the following:</p>
            <ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <section class="rounded-lg bg-white p-6 shadow-sm">
        <h3 class="font-semibold text-gray-900">Particulars</h3>
        <p class="mt-2 text-sm text-gray-500">RFQ {{ $twg->rfq->rfq_no }} &middot; TWG {{ $twg->twg_no }}. Bid amounts start from the TWG report and can be edited for this Abstract.</p>
        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div><label for="abstract_no" class="mb-1 block text-sm font-medium">Abstract No.</label><input id="abstract_no" name="abstract_no" value="{{ old('abstract_no', $abstract?->abstract_no) }}" required maxlength="255" @readonly($locked) class="{{ $input }} {{ $locked ? 'bg-gray-100' : '' }}">@if ($locked)<p class="mt-1 text-xs text-gray-500">Locked once Created.</p>@endif</div>
            <div><label for="abstract_date" class="mb-1 block text-sm font-medium">Abstract date</label><input id="abstract_date" type="date" name="abstract_date" required value="{{ old('abstract_date', $abstract?->abstract_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" class="{{ $input }}"></div>
            <div><label for="bid_deadline_at" class="mb-1 block text-sm font-medium">Deadline of submission of bid</label><input id="bid_deadline_at" type="datetime-local" name="bid_deadline_at" value="{{ old('bid_deadline_at', ($abstract ? $abstract->bid_deadline_at : $twg->rfq->deadline_at)?->format('Y-m-d\TH:i')) }}" class="{{ $input }}"></div>
            <div class="sm:col-span-2 lg:col-span-3"><label for="project_name" class="mb-1 block text-sm font-medium">Project name</label><textarea id="project_name" name="project_name" rows="2" required class="{{ $input }}">{{ old('project_name', $abstract?->project_name ?? $twg->project_name) }}</textarea></div>
            <div><label for="project_location" class="mb-1 block text-sm font-medium">Project location</label><input id="project_location" name="project_location" maxlength="255" value="{{ old('project_location', $abstract?->project_location ?? $twg->rfq->delivery_area) }}" class="{{ $input }}"></div>
            <div><label for="implementing_office" class="mb-1 block text-sm font-medium">Implementing office</label><input id="implementing_office" name="implementing_office" maxlength="255" value="{{ old('implementing_office', $abstract?->implementing_office ?? $twg->rfq->purchaseRequest->office_section) }}" class="{{ $input }}"></div>
            <div><p class="text-sm font-medium">Approved budget for the contract</p><p class="mt-2 text-sm">PhP {{ number_format($twg->abc, 2) }}</p></div>
            <div class="sm:col-span-2 lg:col-span-3"><p class="text-sm font-medium">Method of procurement</p><p class="mt-1 text-sm text-gray-600">{{ $twg->procurement_mode }}</p></div>
        </div>
    </section>
    <section class="rounded-lg bg-white p-6 shadow-sm">
        <h3 class="font-semibold text-gray-900">Abstract of Quotations / Canvass</h3>
        <p class="mt-1 text-sm text-gray-500">Configure each bidder's receipt date, requirements, rank, and remarks. Blank requirements remain unmarked.</p>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full text-sm"><thead class="bg-gray-50 text-left text-xs text-gray-600"><tr>
                <th class="sticky left-0 z-20 bg-gray-50 p-3">Participating bidder</th><th class="p-3">Date and time of receipt</th>
                <th class="p-3">Eligibility</th><th class="p-3">Technical</th><th class="p-3">Financial</th>
                <th class="p-3 text-right">Bid amount</th><th class="p-3">Rank</th><th class="p-3">Remarks</th>
            </tr></thead><tbody class="divide-y divide-gray-200">
                @foreach ($twg->suppliers as $supplier)
                    @php($evaluation = $evaluations[$supplier->id] ?? [])
                    @php($total = $supplier->grandTotal($twg->rfq->items))
                    <tr class="align-top">
                        <th scope="row" class="sticky left-0 z-10 min-w-48 bg-white p-3 text-left font-medium shadow-sm">{{ $supplier->name }}</th>
                        <td class="p-3"><input type="datetime-local" name="evaluations[{{ $supplier->id }}][received_at]" value="{{ $evaluation['received_at'] ?? '' }}" aria-label="{{ $supplier->name }} receipt date and time" class="{{ $input }}"></td>
                        @foreach (['eligibility', 'technical', 'financial'] as $requirement)
                            <td class="min-w-36 p-3" x-data="{ result: @js($evaluation[$requirement] ?? '') }">
                                <input type="hidden" name="evaluations[{{ $supplier->id }}][{{ $requirement }}]" :value="result">
                                <div class="flex items-center gap-4">
                                    @foreach (['pass' => 'Pass', 'fail' => 'Fail'] as $value => $label)
                                        <label class="inline-flex min-h-10 cursor-pointer items-center gap-2 whitespace-nowrap">
                                            <input type="checkbox" :checked="result === '{{ $value }}'" @change="result = $event.target.checked ? '{{ $value }}' : ''"
                                                   aria-label="{{ $supplier->name }} {{ $requirement }} {{ $label }}" class="rounded border-gray-300 text-gray-800 focus:ring-gray-800">
                                            <span>{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </td>
                        @endforeach
                        <td class="min-w-40 p-3"><input type="number" name="evaluations[{{ $supplier->id }}][bid_amount]" min="0" max="999999999999.99" step="0.01"
                            value="{{ array_key_exists('bid_amount', $evaluation) ? $evaluation['bid_amount'] : ($abstract ? $abstract->bidAmountFor($supplier) : $total) }}"
                            aria-label="{{ $supplier->name }} bid amount" placeholder="No Quotation" class="{{ $input }} text-right"></td>
                        <td class="min-w-24 p-3"><input type="number" min="1" max="9999" step="1" name="evaluations[{{ $supplier->id }}][rank]" value="{{ $evaluation['rank'] ?? '' }}" aria-label="{{ $supplier->name }} rank" class="{{ $input }}"></td>
                        <td class="min-w-48 p-3"><textarea name="evaluations[{{ $supplier->id }}][remarks]" rows="2" maxlength="2000" aria-label="{{ $supplier->name }} remarks" class="{{ $input }}">{{ array_key_exists('remarks', $evaluation) ? $evaluation['remarks'] : $supplier->remarks }}</textarea></td>
                    </tr>
                @endforeach
            </tbody></table>
        </div>
    </section>
    <section class="rounded-lg bg-white p-6 shadow-sm">
        <h3 class="font-semibold text-gray-900">Recommendation</h3>
        <label for="recommendation" class="mt-4 mb-1 block text-sm font-medium">Recommendation</label>
        <select id="recommendation" name="recommendation" x-model="recommendation" class="{{ $input }} sm:max-w-md"><option value="">Not selected</option><option value="award">Recommend to award contract</option><option value="failure">Declaration of failure</option></select>
        <div x-show="recommendation === 'award'" class="mt-4 grid gap-4 sm:grid-cols-2">
            <div><label for="award_description" class="mb-1 block text-sm font-medium">Bid winner</label><textarea id="award_description" name="award_description" rows="2" class="{{ $input }}">{{ old('award_description', $abstract?->award_description) }}</textarea></div>
            <div><label for="award_amount" class="mb-1 block text-sm font-medium">Contract price award (PhP)</label><input id="award_amount" name="award_amount" type="number" min="0" max="999999999999.99" step="0.01" value="{{ old('award_amount', $abstract?->award_amount) }}" class="{{ $input }}"><p class="mt-1 text-xs text-gray-500">Amount in words is generated on the saved Abstract.</p></div>
        </div>
        <div x-show="recommendation === 'failure'" class="mt-4"><label for="failure_reason" class="mb-1 block text-sm font-medium">Failure reason</label><select id="failure_reason" name="failure_reason" class="{{ $input }}"><option value="">Not selected</option><option value="ineligible" @selected(old('failure_reason', $abstract?->failure_reason) === 'ineligible')>All prospective bidders are declared ineligible</option><option value="noncompliant" @selected(old('failure_reason', $abstract?->failure_reason) === 'noncompliant')>All bids failed requirements or post-qualification</option></select></div>
    </section>
    <section class="rounded-lg bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3"><h3 class="font-semibold text-gray-900">Resolved by &mdash; BAC signatories</h3><button type="button" @click="signatories.push({name: '', position: '', role: 'member'})" class="rounded-md border px-3 py-2 text-sm hover:bg-gray-50">+ Signatory</button></div>
        <p class="mt-2 text-sm text-gray-500">Members and vice-chairperson print in two columns. The chairperson prints centered below them.</p>
        <div class="mt-4 space-y-3"><template x-for="(signatory, index) in signatories" :key="index">
            <div class="grid grid-cols-1 items-start gap-3 sm:grid-cols-12">
                <select :name="`signatories[${index}][role]`" x-model="signatory.role" aria-label="BAC role" class="{{ $input }} sm:col-span-3"><option value="member">BAC Member</option><option value="vice_chairperson">BAC Vice-Chairperson</option><option value="chairperson">BAC Chairperson</option></select>
                <input :name="`signatories[${index}][name]`" x-model="signatory.name" aria-label="BAC signatory name" placeholder="Full name" maxlength="255" class="{{ $input }} sm:col-span-4">
                <input :name="`signatories[${index}][position]`" x-model="signatory.position" aria-label="BAC signatory position" placeholder="Position" maxlength="255" class="{{ $input }} sm:col-span-4">
                <button type="button" @click="signatories.splice(index, 1)" class="min-h-10 text-sm text-red-600 hover:underline sm:col-span-1">Remove</button>
            </div>
        </template></div>
        <label for="hope_name" class="mt-6 mb-1 block text-sm font-medium">Head of the Procuring Entity</label><input id="hope_name" name="hope_name" maxlength="255" value="{{ old('hope_name', $abstract?->hope_name ?? $lastHope) }}" class="{{ $input }} sm:max-w-md">
        <p class="mt-2 text-sm text-gray-500">Approved / Disapproved / Others stays blank for signing on paper.</p>
    </section>
    <div class="flex flex-wrap items-center gap-3">
        @unless ($locked)<button type="submit" name="action" value="draft" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-50">Save as draft</button>@endunless
        <button type="submit" name="action" value="created" class="rounded-md bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">{{ $locked ? 'Save changes' : 'Create Abstract' }}</button>
        <a href="{{ route('purchase-requests.show', $twg->rfq->purchase_request_id) }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
    </div>
</form>
