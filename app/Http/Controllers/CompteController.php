<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CompteController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('comptes.index', [
            'comptes' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('comptes.create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create([
            'name' => $request->validated('name'),
            'identifiant' => $request->validated('identifiant'),
            'password' => $request->validated('password'),
            'role' => $request->validated('role'),
            'actif' => $request->boolean('actif'),
        ]);

        return redirect()
            ->route('comptes.index')
            ->with('statut', 'Le compte a été créé.');
    }

    public function edit(User $user): View
    {
        Gate::authorize('update', $user);

        return view('comptes.edit', ['user' => $user]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $donnees = [
            'name' => $request->validated('name'),
            'identifiant' => $request->validated('identifiant'),
            'role' => $request->validated('role'),
            'actif' => $request->boolean('actif'),
        ];

        // Le mot de passe n'est changé que s'il est saisi.
        if ($request->filled('password')) {
            $donnees['password'] = $request->validated('password');
        }

        DB::transaction(function () use ($user, $donnees): void {
            // R14 : on verrouille les administrateurs actifs, pour que deux modifications
            // simultanées ne puissent pas les retirer tous.
            $administrateursActifs = User::query()
                ->where('role', Role::Administrateur->value)
                ->where('actif', true)
                ->lockForUpdate()
                ->pluck('id');

            $retireUnAdministrateurActif = $administrateursActifs->contains($user->id)
                && ($donnees['actif'] === false || $donnees['role'] !== Role::Administrateur->value);

            if ($retireUnAdministrateurActif && $administrateursActifs->diff([$user->id])->isEmpty()) {
                throw ValidationException::withMessages([
                    $donnees['actif'] === false ? 'actif' : 'role' => 'Impossible de retirer le dernier administrateur actif.',
                ]);
            }

            $user->update($donnees);
        });

        return redirect()
            ->route('comptes.index')
            ->with('statut', 'Le compte a été modifié.');
    }

    /**
     * R13 : l'administrateur remet à zéro le compteur d'échecs et la date de fin de blocage.
     */
    public function debloquer(User $user): RedirectResponse
    {
        Gate::authorize('unlock', $user);

        if (! $user->isLocked() && $user->failed_login_attempts === 0) {
            return redirect()
                ->route('comptes.index')
                ->with('statut', sprintf('Le compte de %s n’est pas bloqué.', $user->name));
        }

        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        // Le journal automatique ignore ces deux colonnes : on enregistre l'action explicitement.
        activity('comptes')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->event('unlocked')
            ->log('Compte débloqué');

        return redirect()
            ->route('comptes.index')
            ->with('statut', sprintf('Le compte de %s a été débloqué.', $user->name));
    }
}
