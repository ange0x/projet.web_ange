<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEtudiantRequest;
use App\Models\Etudiant;
use App\Services\InscriptionNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EtudiantController extends Controller
{
    private const TRIS_AUTORISES = ['nom', 'date_inscription'];

    private const PAR_PAGE = 15;

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Etudiant::class);

        $tri = in_array($request->query('tri'), self::TRIS_AUTORISES, true)
            ? $request->query('tri')
            : 'nom';
        $ordre = $request->query('ordre') === 'desc' ? 'desc' : 'asc';

        $requete = Etudiant::query();

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
}
