@extends('layouts.base')

@section('titre', 'Liste des étudiants')

@section('contenu')
@php
    $lienTri = function (string $colonne) use ($tri, $ordre): string {
        $suivant = ($tri === $colonne && $ordre === 'asc') ? 'desc' : 'asc';

        return route('etudiants.index', ['tri' => $colonne, 'ordre' => $suivant]);
    };
    $sens = fn (string $colonne): ?string => $tri === $colonne
        ? ($ordre === 'asc' ? 'ascending' : 'descending')
        : null;
    $fleche = fn (string $colonne): string => $tri === $colonne
        ? ($ordre === 'asc' ? ' ▲' : ' ▼')
        : '';
@endphp
<div class="carte carte-large">
    <h1>Liste des étudiants</h1>

    <p class="actions">
        <a class="bouton" href="{{ route('etudiants.create') }}">Enregistrer un étudiant</a>
        <a href="{{ route('accueil') }}">Retour à l’accueil</a>
    </p>

    @if ($etudiants->total() === 0)
        <p>Aucun étudiant enregistré pour le moment.</p>
    @else
        <div class="tableau-defilant" role="region" aria-label="Liste des étudiants" tabindex="0">
            <table>
                <caption>Étudiants inscrits ({{ $etudiants->total() }})</caption>
                <thead>
                    <tr>
                        <th scope="col">Numéro d’inscription</th>
                        <th scope="col" @if ($sens('nom')) aria-sort="{{ $sens('nom') }}" @endif>
                            <a href="{{ $lienTri('nom') }}">Nom<span aria-hidden="true">{{ $fleche('nom') }}</span><span class="visuellement-cache"> (trier)</span></a>
                        </th>
                        <th scope="col">Prénom</th>
                        <th scope="col">Filière</th>
                        <th scope="col">Niveau</th>
                        <th scope="col" @if ($sens('date_inscription')) aria-sort="{{ $sens('date_inscription') }}" @endif>
                            <a href="{{ $lienTri('date_inscription') }}">Date d’inscription<span aria-hidden="true">{{ $fleche('date_inscription') }}</span><span class="visuellement-cache"> (trier)</span></a>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($etudiants as $etudiant)
                        <tr>
                            <th scope="row">{{ $etudiant->numero_inscription }}</th>
                            <td>
                                <a href="{{ route('etudiants.show', $etudiant) }}">{{ $etudiant->nom }}<span class="visuellement-cache"> : voir la fiche de {{ $etudiant->nomComplet() }}</span></a>
                            </td>
                            <td>{{ $etudiant->prenom }}</td>
                            <td>{{ $etudiant->filiere }}</td>
                            <td>{{ $etudiant->niveau }}</td>
                            <td>{{ $etudiant->date_inscription->format('d/m/Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($etudiants->lastPage() > 1)
            <nav class="pagination" aria-label="Pagination">
                <p>Page {{ $etudiants->currentPage() }} sur {{ $etudiants->lastPage() }}</p>
                <ul>
                    @if ($etudiants->previousPageUrl())
                        <li><a href="{{ $etudiants->previousPageUrl() }}" rel="prev">Page précédente</a></li>
                    @endif
                    @if ($etudiants->nextPageUrl())
                        <li><a href="{{ $etudiants->nextPageUrl() }}" rel="next">Page suivante</a></li>
                    @endif
                </ul>
            </nav>
        @endif
    @endif
</div>
@endsection
