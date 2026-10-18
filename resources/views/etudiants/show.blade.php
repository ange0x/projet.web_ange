@extends('layouts.base')

@section('titre', 'Fiche de l’étudiant')

@section('contenu')
@php
    $champs = [
        'Numéro d’inscription' => $etudiant->numero_inscription,
        'Nom' => $etudiant->nom,
        'Prénom' => $etudiant->prenom,
        'Sexe' => $etudiant->sexe->label(),
        'Date de naissance' => $etudiant->date_naissance->format('d/m/Y'),
        'Lieu de naissance' => $etudiant->lieu_naissance,
        'Adresse' => $etudiant->adresse,
        'Téléphone' => $etudiant->telephone,
        'E-mail' => $etudiant->email,
        'Filière' => $etudiant->filiere,
        'Niveau' => $etudiant->niveau,
        'Année académique' => $etudiant->annee_academique,
        'Date d’inscription' => $etudiant->date_inscription->format('d/m/Y'),
        'Nom du tuteur' => $etudiant->tuteur_nom,
        'Téléphone du tuteur' => $etudiant->tuteur_telephone,
    ];
@endphp
<div class="carte carte-moyenne">
    <h1>Fiche de l’étudiant</h1>

    <dl class="fiche">
        @foreach ($champs as $libelle => $valeur)
            <dt>{{ $libelle }}</dt>
            <dd>{{ filled($valeur) ? $valeur : 'Non renseigné' }}</dd>
        @endforeach
    </dl>

    <h2>Dernière modification</h2>
    <p>
        Le {{ $etudiant->updated_at->format('d/m/Y à H:i') }}@if ($etudiant->modificateur) par {{ $etudiant->modificateur->name }}@endif.
    </p>

    <p class="actions">
        <a href="{{ route('etudiants.index') }}">Retour à la liste</a>
    </p>
</div>
@endsection
