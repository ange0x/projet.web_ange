<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;

class AuthenticationService
{
    public const MAX_ATTEMPTS = 5;

    public const LOCK_MINUTES = 15;

    public function attempt(string $identifiant, string $password): bool
    {
        $ip = Request::ip();
        $user = User::query()->where('identifiant', $identifiant)->first();

        // Le blocage est terminé : on repart de zéro.
        if ($user !== null && $user->locked_until !== null && ! $user->isLocked()) {
            $user->forceFill([
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ])->save();
        }

        // Compte inconnu ou bloqué : même temps de réponse qu'un compte normal,
        // pour ne pas révéler quels identifiants existent.
        if ($user === null || $user->isLocked()) {
            Hash::make($password);
            $this->logFailure($user, $ip);

            return false;
        }

        $motDePasseCorrect = Hash::check($password, $user->password);

        if ($user->actif && $motDePasseCorrect) {
            $user->forceFill([
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ])->save();

            Auth::login($user);
            Request::session()->regenerate();

            activity('auth')
                ->causedBy($user)
                ->event('login')
                ->withProperties(['ip' => $ip])
                ->log('Connexion réussie');

            return true;
        }

        $attempts = $user->failed_login_attempts + 1;
        $user->failed_login_attempts = $attempts;

        if ($attempts >= self::MAX_ATTEMPTS) {
            $user->locked_until = now()->addMinutes(self::LOCK_MINUTES);
        }

        $user->save();
        $this->logFailure($user, $ip);

        return false;
    }

    public function logout(): void
    {
        $user = Auth::user();

        Auth::logout();
        Request::session()->invalidate();
        Request::session()->regenerateToken();

        if ($user) {
            activity('auth')
                ->causedBy($user)
                ->event('logout')
                ->log('Déconnexion');
        }
    }

    /**
     * R15 : l'identifiant n'est enregistré que s'il correspond à un compte existant.
     * Un texte saisi dans le mauvais champ (par exemple un mot de passe) n'entre jamais dans le journal.
     */
    private function logFailure(?User $user, ?string $ip): void
    {
        $proprietes = ['ip' => $ip];

        if ($user !== null) {
            $proprietes['identifiant'] = $user->identifiant;
        }

        activity('auth')
            ->causedByAnonymous()
            ->event('login_failed')
            ->withProperties($proprietes)
            ->log('Connexion échouée');
    }
}
