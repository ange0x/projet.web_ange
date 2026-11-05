@extends('layouts.base')

@section('titre', 'Mot de passe oublié')

@section('contenu')
<div class="carte connexion">
    <div class="connexion-tete">
        <h1>Mot de passe oublié</h1>
        <p>Saisissez l’adresse e-mail de votre compte : nous vous enverrons un lien pour choisir un nouveau mot de passe.</p>
    </div>

    <div class="connexion-corps">
        @if (session('statut'))
            <div class="statut" role="status">{{ session('statut') }}</div>
        @endif

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

        <form method="post" action="{{ route('mot-de-passe.envoyer') }}" novalidate>
            @csrf

            <div class="champ">
                <label for="email">E-mail</label>
                <input id="email" name="email" type="email" maxlength="255"
                       value="{{ old('email') }}" autocomplete="email" required
                       aria-describedby="aide-email"
                       @error('email') aria-invalid="true" @enderror>
                <p class="aide" id="aide-email">L’adresse enregistrée sur votre compte par l’administrateur.</p>
            </div>

            <button type="submit">Envoyer le lien</button>
        </form>

        <p class="lien-secondaire"><a href="{{ route('connexion') }}">Retour à la connexion</a></p>
    </div>
</div>
@endsection