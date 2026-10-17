<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'identifiant' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'identifiant.required' => 'Saisissez votre identifiant.',
            'identifiant.max' => 'L’identifiant ne doit pas dépasser 100 caractères.',
            'password.required' => 'Saisissez votre mot de passe.',
            'password.max' => 'Le mot de passe est trop long.',
        ];
    }
}
