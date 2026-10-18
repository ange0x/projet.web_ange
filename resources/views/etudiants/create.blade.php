@extends('layouts.base')

@section('titre', 'Enregistrer un étudiant')

@section('contenu')
<div class="carte carte-moyenne">
    <h1>Enregistrer un étudiant</h1>

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

    <p class="aide">Les champs marqués d’un astérisque (<span aria-hidden="true">*</span>) sont obligatoires. Le numéro d’inscription est attribué automatiquement.</p>

    <form method="post" action="{{ route('etudiants.store') }}" novalidate>
        @csrf

        @include('etudiants._champs', ['valeurs' => [
            'annee_academique' => \App\Support\AcademicYear::current(),
            'date_inscription' => now()->toDateString(),
        ]])

        <div class="actions">
            <button type="submit">Enregistrer l’étudiant</button>
            <a href="{{ route('accueil') }}">Retour à l’accueil</a>
        </div>
    </form>
</div>
@endsection
