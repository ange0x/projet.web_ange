<?php

namespace App\Http\Controllers;

use App\Enums\Sexe;
use App\Http\Requests\StoreEtudiantRequest;
use App\Http\Requests\UpdateEtudiantRequest;
use App\Models\Etudiant;
use App\Services\InscriptionNumberGenerator;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EtudiantController extends Controller
{
    private const TRIS_AUTORISES = ['nom', 'date_inscription'];

    private const PAR_PAGE = 15;

    private const LIBELLES = [
        'nom' => 'Nom',
        'prenom' => 'Prénom',
        'sexe' => 'Sexe',
        'date_naissance' => 'Date de naissance',
        'lieu_naissance' => 'Lieu de naissance',
        'adresse' => 'Adresse',
        'telephone' => 'Téléphone',
        'email' => 'E-mail',
        'filiere' => 'Filière',
        'niveau' => 'Niveau',
        'annee_academique' => 'Année académique',
        'date_inscription' => 'Date d’inscription',
        'tuteur_nom' => 'Nom du tuteur',
        'tuteur_telephone' => 'Téléphone du tuteur',
    ];

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Etudiant::class);

        $tri = in_array($request->query('tri'), self::TRIS_AUTORISES, true)
            ? $request->query('tri')
            : 'nom';
        $ordre = $request->query('ordre') === 'desc' ? 'desc' : 'asc';

        $terme = $this->texte($request->query('terme'));
        $filiere = $this->choix($request->query('filiere'), config('ecole.filieres'));
        $niveau = $this->choix($request->query('niveau'), config('ecole.niveaux'));

        $requete = Etudiant::query()->filtrer($terme, $filiere, $niveau);

        if ($tri === 'nom') {
            $requete->orderBy('nom_search', $ordre)->orderBy('prenom_search', $ordre);
        } else {
            $requete->orderBy('date_inscription', $ordre)
                ->orderBy('nom_search')
                ->orderBy('prenom_search');
        }

        $etudiants = $requete->orderBy('id')->paginate(self::PAR_PAGE)->withQueryString();

        return view('etudiants.index', [
            'etudiants' => $etudiants,
            'tri' => $tri,
            'ordre' => $ordre,
            'terme' => $terme,
            'filiere' => $filiere,
            'niveau' => $niveau,
            'filtresActifs' => $terme !== '' || $filiere !== null || $niveau !== null,
        ]);
    }

    public function show(Etudiant $etudiant): View
    {
        Gate::authorize('view', $etudiant);

        return view('etudiants.show', [
            'etudiant' => $etudiant->load('modificateur'),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Etudiant::class);

        return view('etudiants.create');
    }

    public function store(StoreEtudiantRequest $request, InscriptionNumberGenerator $generateur): RedirectResponse
    {
        $etudiant = DB::transaction(function () use ($request, $generateur): Etudiant {
            $etudiant = new Etudiant($request->validated());
            $etudiant->numero_inscription = $generateur->generate();
            $etudiant->save();

            return $etudiant;
        });

        return redirect()
            ->route('etudiants.create')
            ->with('statut', sprintf(
                'L’étudiant %s a été enregistré. Numéro d’inscription : %s.',
                $etudiant->nomComplet(),
                $etudiant->numero_inscription,
            ));
    }

    public function edit(Etudiant $etudiant): View
    {
        Gate::authorize('update', $etudiant);

        return view('etudiants.edit', [
            'etudiant' => $etudiant,
            'valeurs' => $this->valeursActuelles($etudiant),
        ]);
    }

    /**
     * Étape 2 : montre ce qui va changer, sans rien enregistrer.
     */
    public function confirmer(UpdateEtudiantRequest $request, Etudiant $etudiant): View|RedirectResponse
    {
        $donnees = $request->validated();
        $changements = $this->changements($etudiant, $donnees);

        if ($changements === []) {
            return $this->aucunChangement($etudiant);
        }

        return view('etudiants.confirmer', [
            'etudiant' => $etudiant,
            'donnees' => $donnees,
            'changements' => $changements,
        ]);
    }

    /**
     * « Modifier à nouveau » : retour au formulaire avec ce qui a été saisi.
     */
    public function corriger(Request $request, Etudiant $etudiant): View
    {
        Gate::authorize('update', $etudiant);

        $saisies = array_map(
            fn (mixed $valeur): string => is_string($valeur) ? $valeur : '',
            $request->only(array_keys(self::LIBELLES)),
        );

        return view('etudiants.edit', [
            'etudiant' => $etudiant,
            'valeurs' => array_merge($this->valeursActuelles($etudiant), $saisies),
        ]);
    }

    /**
     * Étape 3 : enregistre. Tout est revalidé, les champs cachés ne sont pas crus.
     */
    public function update(UpdateEtudiantRequest $request, Etudiant $etudiant): RedirectResponse
    {
        $donnees = $request->validated();

        if ($this->changements($etudiant, $donnees) === []) {
            return $this->aucunChangement($etudiant);
        }

        $etudiant->update($donnees);

        return redirect()
            ->route('etudiants.show', $etudiant)
            ->with('statut', sprintf('La fiche de %s a été modifiée.', $etudiant->nomComplet()));
    }

    /**
     * R27 : page de confirmation. Rien n'est archivé ici.
     */
    public function confirmerArchivage(Etudiant $etudiant): View
    {
        Gate::authorize('archive', $etudiant);

        return view('etudiants.archiver', ['etudiant' => $etudiant]);
    }

    /**
     * R27 et R30 : archive la fiche et journalise l'action, sans donnée de l'étudiant.
     */
    public function archiver(Etudiant $etudiant): RedirectResponse
    {
        Gate::authorize('archive', $etudiant);

        $etudiant->delete();

        activity('etudiants')
            ->causedBy(auth()->user())
            ->performedOn($etudiant)
            ->event('archived')
            ->log('Fiche archivée');

        return redirect()
            ->route('etudiants.index')
            ->with('statut', sprintf('La fiche de %s a été archivée.', $etudiant->nomComplet()));
    }

    /**
     * R29 : liste des fiches archivées.
     */
    public function archives(): View
    {
        Gate::authorize('viewArchived', Etudiant::class);

        return view('etudiants.archives', [
            'etudiants' => Etudiant::onlyTrashed()
                ->orderBy('nom_search')
                ->orderBy('prenom_search')
                ->orderBy('id')
                ->paginate(self::PAR_PAGE),
        ]);
    }

    /**
     * R29 et R30 : restaure une fiche archivée.
     */
    public function restaurer(Etudiant $etudiant): RedirectResponse
    {
        Gate::authorize('restore', $etudiant);

        if (! $etudiant->trashed()) {
            return redirect()
                ->route('etudiants.index')
                ->with('statut', sprintf('La fiche de %s n’est pas archivée.', $etudiant->nomComplet()));
        }

        $etudiant->restore();

        activity('etudiants')
            ->causedBy(auth()->user())
            ->performedOn($etudiant)
            ->event('unarchived')
            ->log('Fiche restaurée');

        return redirect()
            ->route('etudiants.archives')
            ->with('statut', sprintf('La fiche de %s a été restaurée.', $etudiant->nomComplet()));
    }

    private function aucunChangement(Etudiant $etudiant): RedirectResponse
    {
        return redirect()
            ->route('etudiants.show', $etudiant)
            ->with('statut', 'Aucune information n’a été modifiée.');
    }

    /**
     * @return array<string, string>
     */
    private function valeursActuelles(Etudiant $etudiant): array
    {
        $valeurs = [];

        foreach (array_keys(self::LIBELLES) as $champ) {
            $valeur = $etudiant->{$champ};

            $valeurs[$champ] = match (true) {
                $valeur instanceof Sexe => $valeur->value,
                $valeur instanceof \DateTimeInterface => $valeur->format('Y-m-d'),
                default => (string) ($valeur ?? ''),
            };
        }

        return $valeurs;
    }

    /**
     * @param  array<string, mixed>  $donnees
     * @return array<int, array{libelle: string, ancienne: string, nouvelle: string}>
     */
    private function changements(Etudiant $etudiant, array $donnees): array
    {
        $actuelles = $this->valeursActuelles($etudiant);
        $changements = [];

        foreach (self::LIBELLES as $champ => $libelle) {
            if (! array_key_exists($champ, $donnees)) {
                continue;
            }

            $nouvelle = (string) ($donnees[$champ] ?? '');

            if ($actuelles[$champ] !== $nouvelle) {
                $changements[] = [
                    'libelle' => $libelle,
                    'ancienne' => $this->afficher($champ, $actuelles[$champ]),
                    'nouvelle' => $this->afficher($champ, $nouvelle),
                ];
            }
        }

        return $changements;
    }

    private function afficher(string $champ, string $valeur): string
    {
        if ($valeur === '') {
            return 'Non renseigné';
        }

        if ($champ === 'sexe') {
            return Sexe::tryFrom($valeur)?->label() ?? $valeur;
        }

        if (in_array($champ, ['date_naissance', 'date_inscription'], true)) {
            return Carbon::createFromFormat('Y-m-d', $valeur)->format('d/m/Y');
        }

        return $valeur;
    }

    /**
     * Un paramètre falsifié (tableau, texte trop long) devient une chaîne vide.
     */
    private function texte(mixed $valeur): string
    {
        return is_string($valeur) ? mb_substr(trim($valeur), 0, 100) : '';
    }

    /**
     * @param  array<int, string>  $autorises
     */
    private function choix(mixed $valeur, array $autorises): ?string
    {
        return is_string($valeur) && in_array($valeur, $autorises, true) ? $valeur : null;
    }
}
