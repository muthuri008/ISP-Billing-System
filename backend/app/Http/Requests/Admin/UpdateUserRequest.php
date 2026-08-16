<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('user');

        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'email' => ['sometimes', 'email', 'max:190', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($userId)],
            'password' => ['sometimes', 'nullable', 'string', 'min:12', 'max:72'],
            'is_active' => ['sometimes', 'boolean'],
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['integer', Rule::exists('roles', 'id')],
        ];
    }
}
