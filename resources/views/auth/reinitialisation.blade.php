@extends('layouts.base')

@section('titre', 'Nouveau mot de passe')

@section('contenu')
<div class="carte connexion">
    <div class="connexion-tete">
        <h1>Nouveau mot de passe</h1>
        <p>Compte : {{ $email }}</p>
    </div>

    <div class="connexion-corps">
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

        <form method="post" action="{{ route('mot-de-passe.reinitialiser') }}" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="champ">
                <label for="password">Nouveau mot de passe</label>
                <input id="password" name="password" type="password" minlength="8"
                       autocomplete="new-password" required aria-describedby="aide-password"
                       @error('password') aria-invalid="true" @enderror>
                <p class="aide" id="aide-password">8 caractères au moins.</p>
            </div>

            <div class="champ">
                <label for="password_confirmation">Confirmation du mot de passe</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                       autocomplete="new-password" required>
            </div>

            <button type="submit">Enregistrer le nouveau mot de passe</button>
        </form>

        <p class="lien-secondaire"><a href="{{ route('mot-de-passe.demande') }}">Demander un nouveau lien</a></p>
    </div>
</div>
@endsection