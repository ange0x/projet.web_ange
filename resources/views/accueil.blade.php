@extends('layouts.base')

@section('titre', 'Accueil')

@section('contenu')
<h1 class="accueil-titre">Accueil</h1>
<p class="accueil-intro">Bonjour {{ auth()->user()->name }} ({{ auth()->user()->roleLabel() }}). Que voulez-vous faire ?</p>

<ul class="tuiles">
    <li>
        <a class="tuile" href="{{ route('etudiants.index') }}">
            <span class="tuile-icone" aria-hidden="true">☰</span>
            <span class="tuile-titre">Liste des étudiants</span>
            <span class="tuile-texte">Consulter, rechercher, trier et ouvrir une fiche.</span>
        </a>
    </li>
    <li>
        <a class="tuile" href="{{ route('etudiants.create') }}">
            <span class="tuile-icone" aria-hidden="true">+</span>
            <span class="tuile-titre">Enregistrer un étudiant</span>
            <span class="tuile-texte">Inscrire un nouvel étudiant avec son numéro automatique.</span>
        </a>
    </li>
    @if (auth()->user()->isAdministrateur())
        <li>
            <a class="tuile" href="{{ route('comptes.index') }}">
                <span class="tuile-icone" aria-hidden="true">⚙</span>
                <span class="tuile-titre">Gérer les comptes</span>
                <span class="tuile-texte">Créer, modifier, débloquer ou archiver un compte.</span>
            </a>
        </li>
    @endif
</ul>
@endsection