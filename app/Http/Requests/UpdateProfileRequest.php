<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // User can update their own profile
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users_accounts,username,' . $userId,
            'email' => 'required|email|unique:users_accounts,email,' . $userId,
            'phone_number' => 'nullable|string',
            'password' => 'nullable|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'This username is already taken',
            'email.unique' => 'This email is already registered',
            'phone_number.unique' => 'This phone number is already registered',
            'current_password.required_with' => 'Current password is required to change password',
            'current_password.current_password' => 'Current password is incorrect',
            'password.confirmed' => 'Password confirmation does not match',
        ];
    }
}
