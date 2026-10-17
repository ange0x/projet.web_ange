<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
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

        $user->update($donnees);

        return redirect()
            ->route('comptes.index')
            ->with('statut', 'Le compte a été modifié.');
    }
}
