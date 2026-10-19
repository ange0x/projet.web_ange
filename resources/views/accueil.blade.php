@extends('layouts.base')

@section('titre', 'Accueil')

@section('contenu')
<div class="carte">
    <h1>Accueil</h1>
    <p>Bonjour {{ auth()->user()->name }} ({{ auth()->user()->roleLabel() }}).</p>

    <ul class="liens">
        <li><a href="{{ route('etudiants.index') }}">Liste des étudiants</a></li>
        <li><a href="{{ route('etudiants.create') }}">Enregistrer un étudiant</a></li>
        @if (auth()->user()->isAdministrateur())
            <li><a href="{{ route('comptes.index') }}">Gérer les comptes</a></li>
        @endif
    </ul>
</div>
@endsection
