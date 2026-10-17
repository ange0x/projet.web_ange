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

        if ($user?->isLocked()) {
            $this->logFailure($identifiant, $ip);

            return false;
        }

        if ($user && $user->actif && Hash::check($password, $user->password)) {
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

        if ($user) {
            $attempts = $user->failed_login_attempts + 1;
            $user->failed_login_attempts = $attempts;

            if ($attempts >= self::MAX_ATTEMPTS) {
                $user->locked_until = now()->addMinutes(self::LOCK_MINUTES);
            }

            $user->save();
        }

        $this->logFailure($identifiant, $ip);

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

    private function logFailure(string $identifiant, ?string $ip): void
    {
        activity('auth')
            ->causedByAnonymous()
            ->event('login_failed')
            ->withProperties([
                'identifiant' => $identifiant,
                'ip' => $ip,
            ])
            ->log('Connexion échouée');
    }
}
