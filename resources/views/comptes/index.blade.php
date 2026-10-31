@extends('layouts.base')

@section('titre', 'Gestion des comptes')

@section('contenu')
<div class="carte carte-large">
    <h1>Gestion des comptes</h1>

    @if (session('statut'))
        <div class="statut" role="status">{{ session('statut') }}</div>
    @endif

    @if ($errors->any())
        <div class="erreurs" role="alert">
            <h2>Action impossible</h2>
            <ul>
                @foreach ($errors->all() as $erreur)
                    <li>{{ $erreur }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <p class="actions">
        <a class="bouton" href="{{ route('comptes.create') }}">Créer un compte</a>
        <a href="{{ route('comptes.archives') }}">Comptes archivés</a>
        <a href="{{ route('accueil') }}">Retour à l’accueil</a>
    </p>

    <div class="tableau-defilant" role="region" aria-label="Liste des comptes" tabindex="0">
        <table>
            <caption>Comptes des utilisateurs ({{ $comptes->count() }})</caption>
            <thead>
                <tr>
                    <th scope="col">Nom</th>
                    <th scope="col">Identifiant</th>
                    <th scope="col">Rôle</th>
                    <th scope="col">État</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($comptes as $compte)
                    <tr>
                        <th scope="row">{{ $compte->name }}</th>
                        <td>{{ $compte->identifiant }}</td>
                        <td>{{ $compte->roleLabel() }}</td>
                        <td>
                            @if (! $compte->actif)
                                Désactivé
                            @elseif ($compte->isLocked())
                                Bloqué (encore {{ max(1, (int) ceil(now()->diffInMinutes($compte->locked_until))) }} min)
                            @else
                                Actif
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('comptes.edit', $compte) }}">Modifier<span class="visuellement-cache"> le compte de {{ $compte->name }}</span></a>
                                @if ($compte->isLocked())
                                    <form method="post" action="{{ route('comptes.debloquer', $compte) }}">
                                        @csrf
                                        <button type="submit" class="secondaire">Débloquer<span class="visuellement-cache"> le compte de {{ $compte->name }}</span></button>
                                    </form>
                                @endif
                                @unless ($compte->is(auth()->user()))
                                    <a href="{{ route('comptes.archiver.confirmer', $compte) }}">Archiver<span class="visuellement-cache"> le compte de {{ $compte->name }}</span></a>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection