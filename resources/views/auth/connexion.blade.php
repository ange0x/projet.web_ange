@extends('layouts.base')

@section('titre', 'Connexion')

@section('contenu')
<div class="carte">
    <h1>Connexion</h1>
    <p>Gestion des inscriptions – ESGC Verechaguine AK</p>

    @if ($errors->any())
        <div class="erreurs" role="alert">
            <h2>Veuillez corriger les erreurs suivantes</h2>
            <ul>
                @foreach ($errors->all() as $erreur)
                    <li>{{ $erreur }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('connexion.soumettre') }}" novalidate>
        @csrf

        <div class="champ">
            <label for="identifiant">Identifiant</label>
            <input id="identifiant" name="identifiant" type="text"
                   value="{{ old('identifiant') }}"
                   autocomplete="username" required
                   @error('identifiant') aria-invalid="true" @enderror>
        </div>

        <div class="champ">
            <label for="password">Mot de passe</label>
            <input id="password" name="password" type="password"
                   autocomplete="current-password" required
                   @error('password') aria-invalid="true" @enderror>
        </div>

        <button type="submit">Se connecter</button>
    </form>
</div>
@endsection
