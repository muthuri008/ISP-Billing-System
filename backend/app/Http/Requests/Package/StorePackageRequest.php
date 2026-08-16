<?php

namespace App\Http\Requests\Package;

use Illuminate\Foundation\Http\FormRequest;

class StorePackageRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:60', 'unique:packages,code'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:5000'],
            'download_mbps' => ['required', 'integer', 'min:1', 'max:100000'],
            'upload_mbps' => ['required', 'integer', 'min:1', 'max:100000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'billing_cycle' => ['required', 'in:daily,weekly,monthly,quarterly,annual'],
            'data_limit_gb' => ['nullable', 'integer', 'min:1'],
            'fair_usage_gb' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
