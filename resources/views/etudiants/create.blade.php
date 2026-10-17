@extends('layouts.base')

@section('titre', 'Enregistrer un étudiant')

@section('contenu')
@php
    $sexes = collect(\App\Enums\Sexe::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all();
    $filieres = collect(config('ecole.filieres'))->mapWithKeys(fn ($v) => [$v => $v])->all();
    $niveaux = collect(config('ecole.niveaux'))->mapWithKeys(fn ($v) => [$v => $v])->all();
@endphp
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

        <fieldset>
            <legend>Identité</legend>
            @include('etudiants._champ', ['champ' => 'nom', 'label' => 'Nom', 'obligatoire' => true, 'maxlength' => 100])
            @include('etudiants._champ', ['champ' => 'prenom', 'label' => 'Prénom', 'obligatoire' => true, 'maxlength' => 100])
            @include('etudiants._liste', ['champ' => 'sexe', 'label' => 'Sexe', 'obligatoire' => true, 'options' => $sexes])
            @include('etudiants._champ', ['champ' => 'date_naissance', 'label' => 'Date de naissance', 'type' => 'date', 'obligatoire' => true, 'max' => now()->subDay()->toDateString()])
            @include('etudiants._champ', ['champ' => 'lieu_naissance', 'label' => 'Lieu de naissance', 'maxlength' => 100])
        </fieldset>

        <fieldset>
            <legend>Coordonnées</legend>
            @include('etudiants._champ', ['champ' => 'adresse', 'label' => 'Adresse', 'maxlength' => 255])
            @include('etudiants._champ', ['champ' => 'telephone', 'label' => 'Téléphone', 'type' => 'tel', 'mode' => 'tel', 'obligatoire' => true, 'aide' => '10 chiffres commençant par 01, avec ou sans +229.'])
            @include('etudiants._champ', ['champ' => 'email', 'label' => 'E-mail', 'type' => 'email', 'maxlength' => 255, 'aide' => 'Facultatif. Une adresse ne peut être utilisée que par un seul étudiant.'])
        </fieldset>

        <fieldset>
            <legend>Inscription</legend>
            @include('etudiants._liste', ['champ' => 'filiere', 'label' => 'Filière', 'obligatoire' => true, 'options' => $filieres])
            @include('etudiants._liste', ['champ' => 'niveau', 'label' => 'Niveau', 'obligatoire' => true, 'options' => $niveaux])
            @include('etudiants._champ', ['champ' => 'annee_academique', 'label' => 'Année académique', 'obligatoire' => true, 'defaut' => \App\Support\AcademicYear::current(), 'maxlength' => 9, 'motif' => '[0-9]{4}-[0-9]{4}', 'aide' => 'Exemple : 2026-2027.'])
            @include('etudiants._champ', ['champ' => 'date_inscription', 'label' => 'Date d’inscription', 'type' => 'date', 'obligatoire' => true, 'defaut' => now()->toDateString(), 'max' => now()->toDateString()])
        </fieldset>

        <fieldset>
            <legend>Tuteur (facultatif)</legend>
            @include('etudiants._champ', ['champ' => 'tuteur_nom', 'label' => 'Nom du tuteur', 'maxlength' => 100])
            @include('etudiants._champ', ['champ' => 'tuteur_telephone', 'label' => 'Téléphone du tuteur', 'type' => 'tel', 'mode' => 'tel', 'aide' => '10 chiffres commençant par 01, avec ou sans +229.'])
        </fieldset>

        <div class="actions">
            <button type="submit">Enregistrer l’étudiant</button>
            <a href="{{ route('accueil') }}">Retour à l’accueil</a>
        </div>
    </form>
</div>
@endsection
