<?php

namespace App\Http\Requests;

use App\Enums\Sexe;
use App\Models\Etudiant;
use App\Support\AcademicYear;
use App\Support\TextNormalizer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEtudiantRequest extends FormRequest
{
    private const TELEPHONE = '/^01\d{8}$/';

    public function authorize(): bool
    {
        return $this->user()?->can('create', Etudiant::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        $this->merge([
            'telephone' => $this->normaliserTelephone($this->input('telephone')),
            'tuteur_telephone' => $this->normaliserTelephone($this->input('tuteur_telephone')),
            'email' => is_string($email) ? mb_strtolower(trim($email)) : $email,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'sexe' => ['required', Rule::enum(Sexe::class)],
            'date_naissance' => ['required', 'date_format:Y-m-d', 'before:today'],
            'lieu_naissance' => ['nullable', 'string', 'max:100'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'telephone' => ['required', 'regex:'.self::TELEPHONE],
            'email' => ['nullable', 'email:rfc', 'max:255', Rule::unique('etudiants', 'email')],
            'filiere' => ['required', Rule::in(config('ecole.filieres'))],
            'niveau' => ['required', Rule::in(config('ecole.niveaux'))],
            'annee_academique' => [
                'required', 'string',
                function (string $attribut, mixed $valeur, \Closure $echec): void {
                    if (! AcademicYear::isValid((string) $valeur)) {
                        $echec('L’année académique doit avoir le format 2026-2027 (deux années consécutives).');
                    }
                },
            ],
            'date_inscription' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'tuteur_nom' => ['nullable', 'string', 'max:100'],
            'tuteur_telephone' => ['nullable', 'regex:'.self::TELEPHONE],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $telephone = 'Le numéro doit contenir 10 chiffres et commencer par 01 (avec ou sans +229).';

        return [
            'nom.required' => 'Saisissez le nom de l’étudiant.',
            'nom.max' => 'Le nom ne doit pas dépasser 100 caractères.',
            'prenom.required' => 'Saisissez le prénom de l’étudiant.',
            'prenom.max' => 'Le prénom ne doit pas dépasser 100 caractères.',
            'sexe.required' => 'Choisissez le sexe.',
            'sexe.enum' => 'Le sexe choisi n’est pas valide.',
            'date_naissance.required' => 'Saisissez la date de naissance.',
            'date_naissance.date_format' => 'La date de naissance doit avoir le format AAAA-MM-JJ.',
            'date_naissance.before' => 'La date de naissance doit être dans le passé.',
            'lieu_naissance.max' => 'Le lieu de naissance ne doit pas dépasser 100 caractères.',
            'adresse.max' => 'L’adresse ne doit pas dépasser 255 caractères.',
            'telephone.required' => 'Saisissez le numéro de téléphone.',
            'telephone.regex' => $telephone,
            'email.email' => 'Saisissez une adresse e-mail valide.',
            'email.max' => 'L’adresse e-mail est trop longue.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée par un autre étudiant.',
            'filiere.required' => 'Choisissez la filière.',
            'filiere.in' => 'La filière choisie n’existe pas.',
            'niveau.required' => 'Choisissez le niveau.',
            'niveau.in' => 'Le niveau choisi n’existe pas.',
            'annee_academique.required' => 'Saisissez l’année académique.',
            'date_inscription.required' => 'Saisissez la date d’inscription.',
            'date_inscription.date_format' => 'La date d’inscription doit avoir le format AAAA-MM-JJ.',
            'date_inscription.before_or_equal' => 'La date d’inscription ne peut pas être dans le futur.',
            'tuteur_nom.max' => 'Le nom du tuteur ne doit pas dépasser 100 caractères.',
            'tuteur_telephone.regex' => $telephone,
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['nom', 'prenom', 'date_naissance'])) {
                return;
            }

            // R28 : les étudiants archivés comptent aussi dans la détection des doublons.
            $existe = Etudiant::withTrashed()
                ->where('nom_search', TextNormalizer::normalize($this->input('nom')))
                ->where('prenom_search', TextNormalizer::normalize($this->input('prenom')))
                ->whereDate('date_naissance', $this->input('date_naissance'))
                ->exists();

            if ($existe) {
                $validator->errors()->add('nom', 'Un étudiant avec le même nom, le même prénom et la même date de naissance est déjà enregistré.');
            }
        });
    }

    private function normaliserTelephone(mixed $valeur): mixed
    {
        if (! is_string($valeur)) {
            return $valeur;
        }

        $nettoye = preg_replace('/[\s.\-]/', '', $valeur);

        if (str_starts_with($nettoye, '+229')) {
            $nettoye = substr($nettoye, 4);
        }

        return $nettoye;
    }
}
