@extends('layouts.base')

@section('titre', 'Archiver une fiche')

@section('contenu')
<div class="carte">
    <h1>Archiver la fiche</h1>

    <p>Vous allez archiver la fiche de <strong>{{ $etudiant->nomComplet() }}</strong> (numéro d’inscription : {{ $etudiant->numero_inscription }}, {{ $etudiant->filiere }}, {{ $etudiant->niveau }}).</p>
    <p>La fiche disparaîtra des listes et des recherches. Son numéro d’inscription ne sera jamais réattribué et son adresse e-mail restera réservée. Vous pourrez la restaurer depuis les étudiants archivés.</p>

    <div class="actions">
        <form method="post" action="{{ route('etudiants.archiver', $etudiant) }}">
            @csrf
            <button type="submit" class="danger">Archiver la fiche</button>
        </form>
        <a href="{{ route('etudiants.show', $etudiant) }}">Annuler</a>
    </div>
</div>
@endsection