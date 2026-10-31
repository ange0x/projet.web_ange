@extends('layouts.base')

@section('titre', 'Liste des étudiants')

@section('contenu')
@php
    $parametres = array_filter([
        'terme' => $terme,
        'filiere' => $filiere,
        'niveau' => $niveau,
    ], fn ($valeur) => filled($valeur));

    $lienTri = function (string $colonne) use ($tri, $ordre, $parametres): string {
        $suivant = ($tri === $colonne && $ordre === 'asc') ? 'desc' : 'asc';

        return route('etudiants.index', $parametres + ['tri' => $colonne, 'ordre' => $suivant]);
    };
    $sens = fn (string $colonne): ?string => $tri === $colonne
        ? ($ordre === 'asc' ? 'ascending' : 'descending')
        : null;
    $fleche = fn (string $colonne): string => $tri === $colonne
        ? ($ordre === 'asc' ? ' ▲' : ' ▼')
        : '';
    $nombre = $etudiants->total();
@endphp
<div class="carte carte-large">
    <h1>Liste des étudiants</h1>

    @if (session('statut'))
        <div class="statut" role="status">{{ session('statut') }}</div>
    @endif

    <p class="actions">
        <a class="bouton" href="{{ route('etudiants.create') }}">Enregistrer un étudiant</a>
        <a href="{{ route('etudiants.archives') }}">Étudiants archivés</a>
        <a href="{{ route('accueil') }}">Retour à l’accueil</a>
    </p>

    <form class="recherche" method="get" action="{{ route('etudiants.index') }}" role="search" aria-label="Rechercher un étudiant">
        <div class="champ">
            <label for="terme">Rechercher un étudiant</label>
            <input id="terme" name="terme" type="search" maxlength="100"
                   value="{{ $terme }}" autocomplete="off"
                   aria-describedby="aide-terme">
            <p class="aide" id="aide-terme">Nom, prénom ou numéro d’inscription. Vous pouvez saisir plusieurs mots.</p>
        </div>

        <div class="champ">
            <label for="filiere">Filière</label>
            <select id="filiere" name="filiere">
                <option value="">Toutes les filières</option>
                @foreach (config('ecole.filieres') as $option)
                    <option value="{{ $option }}" @selected($filiere === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>

        <div class="champ">
            <label for="niveau">Niveau</label>
            <select id="niveau" name="niveau">
                <option value="">Tous les niveaux</option>
                @foreach (config('ecole.niveaux') as $option)
                    <option value="{{ $option }}" @selected($niveau === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>

        @if ($tri !== 'nom' || $ordre !== 'asc')
            <input type="hidden" name="tri" value="{{ $tri }}">
            <input type="hidden" name="ordre" value="{{ $ordre }}">
        @endif

        <div class="actions">
            <button type="submit">Rechercher</button>
            @if ($filtresActifs)
                <a href="{{ route('etudiants.index') }}">Afficher tous les étudiants</a>
            @endif
        </div>
    </form>

    @if ($nombre === 0)
        @if ($filtresActifs)
            <p class="statut-liste" role="status">Aucun étudiant ne correspond à votre recherche.</p>
        @else
            <p class="statut-liste" role="status">Aucun étudiant enregistré pour le moment.</p>
        @endif
    @else
        @if ($filtresActifs)
            <p class="statut-liste" role="status">{{ $nombre }} {{ $nombre > 1 ? 'étudiants trouvés' : 'étudiant trouvé' }}.</p>
        @endif

        <div class="tableau-defilant" role="region" aria-label="Liste des étudiants" tabindex="0">
            <table>
                <caption>Étudiants inscrits ({{ $nombre }})</caption>
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