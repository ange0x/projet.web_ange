<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends StoreUserRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('user')) ?? false;
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
                Rule::unique('users', 'identifiant')->ignore($this->route('user')),
            ],
            'email' => [
                'nullable', 'email:rfc', 'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')),
            ],
            'password' => ['nullable', 'string', 'min:8', 'max:255', 'confirmed'],
            'role' => ['required', Rule::enum(Role::class)],
            'actif' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $cible = $this->route('user');

            // R09 : un administrateur ne peut pas se verrouiller hors de l'application.
            if ($cible->is($this->user())) {
                if (! $this->boolean('actif')) {
                    $validator->errors()->add('actif', 'Vous ne pouvez pas désactiver votre propre compte.');
                }

                if ($this->input('role') !== $cible->role->value) {
                    $validator->errors()->add('role', 'Vous ne pouvez pas modifier votre propre rôle.');
                }
            }
        });
    }
}
