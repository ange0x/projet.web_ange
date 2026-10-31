<?php

namespace App\Http\Requests;

use App\Models\Etudiant;
use App\Support\TextNormalizer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateEtudiantRequest extends StoreEtudiantRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('etudiant')) ?? false;
    }

    protected function getRedirectUrl(): string
    {
        return route('etudiants.edit', $this->route('etudiant'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'email' => [
                'nullable', 'email:rfc', 'max:255',
                Rule::unique('etudiants', 'email')->ignore($this->route('etudiant')),
            ],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['nom', 'prenom', 'date_naissance'])) {
                return;
            }

            // R28 : les étudiants archivés comptent aussi dans la détection des doublons.
            $existe = Etudiant::withTrashed()
                ->whereKeyNot($this->route('etudiant')->getKey())
                ->where('nom_search', TextNormalizer::normalize($this->input('nom')))
                ->where('prenom_search', TextNormalizer::normalize($this->input('prenom')))
                ->whereDate('date_naissance', $this->input('date_naissance'))
                ->exists();

            if ($existe) {
                $validator->errors()->add('nom', 'Un autre étudiant avec le même nom, le même prénom et la même date de naissance est déjà enregistré.');
            }
        });
    }
}
