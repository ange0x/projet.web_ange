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
            'email' => $request->validated('email'),
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
            'email' => $request->validated('email'),
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

    /**
     * R23 : page de confirmation. Rien n'est archivé ici.
     */
    public function confirmerArchivage(User $user): View
    {
        Gate::authorize('archive', $user);

        return view('comptes.archiver', ['user' => $user]);
    }

    /**
     * R23 et R24 : archive un compte, sauf le sien et le dernier administrateur actif.
     */
    public function archiver(User $user): RedirectResponse
    {
        Gate::authorize('archive', $user);

        if ($user->is(auth()->user())) {
            return $this->refus('Vous ne pouvez pas archiver votre propre compte.');
        }

        $archive = DB::transaction(function () use ($user): bool {
            $administrateursActifs = User::query()
                ->where('role', Role::Administrateur->value)
                ->where('actif', true)
                ->lockForUpdate()
                ->pluck('id');

            if ($administrateursActifs->contains($user->id) && $administrateursActifs->diff([$user->id])->isEmpty()) {
                return false;
            }

            $user->delete();

            return true;
        });

        if (! $archive) {
            return $this->refus('Impossible d’archiver le dernier administrateur actif.');
        }

        // Journal explicite : auteur et cible, aucune donnée personnelle.
        activity('comptes')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->event('archived')
            ->log('Compte archivé');

        return redirect()
            ->route('comptes.index')
            ->with('statut', sprintf('Le compte de %s a été archivé.', $user->name));
    }

    public function archives(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('comptes.archives', [
            'comptes' => User::onlyTrashed()->orderBy('name')->get(),
        ]);
    }

    /**
     * R26 : restaure un compte archivé, avec son mot de passe.
     */
    public function restaurer(User $user): RedirectResponse
    {
        Gate::authorize('restore', $user);

        if (! $user->trashed()) {
            return redirect()
                ->route('comptes.index')
                ->with('statut', sprintf('Le compte de %s n’est pas archivé.', $user->name));
        }

        $user->restore();

        activity('comptes')
            ->causedBy(auth()->user())
            ->performedOn($user)
            ->event('unarchived')
            ->log('Compte restauré');

        return redirect()
            ->route('comptes.archives')
            ->with('statut', sprintf('Le compte de %s a été restauré.', $user->name));
    }

    private function refus(string $message): RedirectResponse
    {
        return redirect()
            ->route('comptes.index')
            ->withErrors(['archive' => $message]);
    }
}
