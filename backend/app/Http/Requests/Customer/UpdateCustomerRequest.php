<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'nullable', 'email', 'max:190'],
            'phone' => ['sometimes', 'string', 'max:30'],
            'national_id' => ['sometimes', 'nullable', 'string', 'max:80'],
            'billing_type' => ['sometimes', 'in:prepaid,postpaid'],
            'status' => ['sometimes', 'in:active,suspended,disconnected'],
            'registered_at' => ['sometimes', 'nullable', 'date'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
