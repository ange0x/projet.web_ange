<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConnexionController extends Controller
{
    public function afficher(): View
    {
        return view('auth.connexion');
    }

    public function soumettre(LoginRequest $request, AuthenticationService $authentification): RedirectResponse
    {
        $reussie = $authentification->attempt(
            $request->validated('identifiant'),
            $request->validated('password'),
        );

        if (! $reussie) {
            // Message volontairement identique pour tous les cas d'échec.
            return back()
                ->withErrors(['identifiant' => 'Identifiant ou mot de passe incorrect.'])
                ->onlyInput('identifiant');
        }

        return redirect()->intended(route('accueil'));
    }

    public function deconnecter(AuthenticationService $authentification): RedirectResponse
    {
        $authentification->logout();

        return redirect()->route('connexion');
    }
}
