<?php

namespace App\Http\Requests;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'identifiant' => [
                'required', 'string', 'max:100',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('users', 'identifiant'),
            ],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'role' => ['required', Rule::enum(Role::class)],
            'actif' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Saisissez le nom complet.',
            'name.max' => 'Le nom ne doit pas dépasser 100 caractères.',
            'identifiant.required' => 'Saisissez un identifiant.',
            'identifiant.max' => 'L’identifiant ne doit pas dépasser 100 caractères.',
            'identifiant.regex' => 'L’identifiant ne peut contenir que des lettres, des chiffres, des points, des tirets et des tirets bas.',
            'identifiant.unique' => 'Cet identifiant est déjà utilisé.',
            'password.required' => 'Saisissez un mot de passe.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.max' => 'Le mot de passe est trop long.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'role.required' => 'Choisissez un rôle.',
            'role.enum' => 'Le rôle choisi n’existe pas.',
        ];
    }
}
