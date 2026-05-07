<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('manage_roles');
    }

    public function rules(): array
    {
        return [
            'full_name'        => 'required|string|max:255',
            'role'             => 'required|exists:roles,name',
            'is_active'        => 'boolean',
            'password'         => ['nullable', Password::min(8)->mixedCase()->numbers()],
            'password_confirm' => 'nullable|same:password',
        ];
    }
}
