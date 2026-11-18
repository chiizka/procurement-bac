<?php

namespace App\Http\Requests;

use App\Enums\LineType;
use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRfqRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = $this->user();

        return (bool) $user?->hasRole(Role::Bac, Role::Admin);
    }

    public function rules(): array
    {
        $current = $this->route('rfq'); // null on create
        $creating = $this->input('action') === 'created';

        return [
            'action' => ['required', Rule::in(['draft', 'created'])],

            'rfq_no' => ['required', 'string', 'max:50',
                Rule::unique('rfqs', 'rfq_no')->ignore($current?->id)],
            'lot_label' => ['nullable', 'string', 'max:255'],
            'rfq_date' => ['required', 'date'],
            // Closing date/time is optional for drafts, required before Created.
            'deadline_at' => $creating ? ['required', 'date'] : ['nullable', 'date'],
            'purpose' => ['required', 'string'],
            'bac_chairperson' => ['nullable', 'string', 'max:255'],
            'contract_duration' => ['nullable', 'string', 'max:255'],
            'delivery_area' => ['nullable', 'string', 'max:255'],

            'items' => $creating ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'items.*.pr_item_id' => ['nullable', 'integer', 'exists:pr_items,id'],
            'items.*.line_type' => ['required', Rule::enum(LineType::class)],
            'items.*.stock_no' => ['nullable', 'string', 'max:50'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'deadline_at.required' => 'The closing date and time is required before the RFQ can be created.',
        ];
    }
}
