<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // la autorización se aplica en las rutas (can:users.create)
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:40'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where(fn ($q) => $q->where('slug', '!=', \App\Models\Role::PORTAL_SLUG))],
            'is_active' => ['boolean'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
