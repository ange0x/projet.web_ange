<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdministrateur() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'identifiant' => ['required', 'string', 'max:100', 'unique:users,identifiant'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::enum(Role::class)],
            'actif' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'identifiant' => 'identifiant',
            'password' => 'mot de passe',
            'role' => 'rôle',
            'actif' => 'statut',
        ];
    }
}
