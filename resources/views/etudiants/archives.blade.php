@extends('layouts.base')

@section('titre', 'Étudiants archivés')

@section('contenu')
<div class="carte carte-large">
    <h1>Étudiants archivés</h1>

    @if (session('statut'))
        <div class="statut" role="status">{{ session('statut') }}</div>
    @endif

    <p class="actions">
        <a href="{{ route('etudiants.index') }}">Retour à la liste des étudiants</a>
    </p>

    @if ($etudiants->total() === 0)
        <p class="statut-liste" role="status">Aucun étudiant archivé.</p>
    @else
        <div class="tableau-defilant" role="region" aria-label="Étudiants archivés" tabindex="0">
            <table>
                <caption>Étudiants archivés ({{ $etudiants->total() }})</caption>
                <thead>
                    <tr>
                        <th scope="col">Numéro d’inscription</th>
                        <th scope="col">Nom</th>
                        <th scope="col">Prénom</th>
                        <th scope="col">Filière</th>
                        <th scope="col">Niveau</th>
                        <th scope="col">Archivé le</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($etudiants as $etudiant)
                        <tr>
                            <th scope="row">{{ $etudiant->numero_inscription }}</th>
                            <td>{{ $etudiant->nom }}</td>
                            <td>{{ $etudiant->prenom }}</td>
                            <td>{{ $etudiant->filiere }}</td>
                            <td>{{ $etudiant->niveau }}</td>
                            <td>{{ $etudiant->deleted_at->format('d/m/Y à H:i') }}</td>
                            <td>
                                <form method="post" action="{{ route('etudiants.restaurer', $etudiant) }}">
                                    @csrf
                                    <button type="submit" class="secondaire">Restaurer<span class="visuellement-cache"> la fiche de {{ $etudiant->nomComplet() }}</span></button>
                                </form>
                            </td>
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