@extends('layouts.base')

@section('titre', 'Modifier la fiche')

@section('contenu')
<div class="carte carte-moyenne">
    <h1>Modifier la fiche de l’étudiant</h1>

    <p>Numéro d’inscription : <strong>{{ $etudiant->numero_inscription }}</strong> (non modifiable)</p>

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

    <p class="aide">Les champs marqués d’un astérisque (<span aria-hidden="true">*</span>) sont obligatoires. Vous verrez les changements avant de les enregistrer.</p>

    <form method="post" action="{{ route('etudiants.confirmer', $etudiant) }}" novalidate>
        @csrf

        @include('etudiants._champs', ['valeurs' => $valeurs])

        <div class="actions">
            <button type="submit">Vérifier les modifications</button>
            <a href="{{ route('etudiants.show', $etudiant) }}">Annuler</a>
        </div>
    </form>
</div>
@endsection
