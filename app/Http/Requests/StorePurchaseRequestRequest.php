<?php

namespace App\Http\Requests;

use App\Enums\LineType;
use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = $this->user();

        return (bool) $user?->hasRole(Role::Bac, Role::Admin);
    }

    public function rules(): array
    {
        $current = $this->route('purchase_request'); // null on create
        $creating = $this->input('action') === 'created';

        return [
            'action' => ['required', Rule::in(['draft', 'created'])],

            'pr_no' => ['required', 'string', 'max:50',
                Rule::unique('purchase_requests', 'pr_no')->ignore($current?->id)],
            'pr_date' => ['required', 'date'],
            'entity_name' => ['required', 'string', 'max:255'],
            'fund_cluster' => ['nullable', 'string', 'max:50'],
            'office_section' => ['required', 'string', 'max:255'],
            'responsibility_center_code' => ['nullable', 'string', 'max:50'],
            'purpose' => ['required', 'string'],
            'end_user' => ['nullable', 'string', 'max:255'],
            'requested_by' => ['nullable', 'string', 'max:255'],
            'approved_by' => ['nullable', 'string', 'max:255'],
            'requested_by_position' => ['nullable', 'string', 'max:255'],
            'certified_funds_available' => ['nullable', 'string', 'max:255'],
            'certified_funds_available_position' => ['nullable', 'string', 'max:255'],
            'approved_by_position' => ['nullable', 'string', 'max:255'],

            'items' => $creating ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'items.*.line_type' => ['required', Rule::enum(LineType::class)],
            'items.*.stock_no' => ['nullable', 'string', 'max:50'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}