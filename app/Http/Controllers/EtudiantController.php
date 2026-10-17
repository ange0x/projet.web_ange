<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEtudiantRequest;
use App\Models\Etudiant;
use App\Services\InscriptionNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class EtudiantController extends Controller
{
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
