@extends('layouts.base')

@section('titre', 'Confirmer la modification')

@section('contenu')
<div class="carte carte-moyenne">
    <h1>Modifications à confirmer</h1>

    <p>Fiche n° <strong>{{ $etudiant->numero_inscription }}</strong>. Vérifiez les changements ci-dessous : rien n’est enregistré tant que vous ne confirmez pas.</p>

    <div class="tableau-defilant" role="region" aria-label="Tableau des modifications" tabindex="0">
        <table>
            <caption>Informations modifiées ({{ count($changements) }})</caption>
            <thead>
                <tr>
                    <th scope="col">Information</th>
                    <th scope="col">Ancienne valeur</th>
                    <th scope="col">Nouvelle valeur</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($changements as $changement)
                    <tr>
                        <th scope="row">{{ $changement['libelle'] }}</th>
                        <td>{{ $changement['ancienne'] }}</td>
                        <td>{{ $changement['nouvelle'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="actions">
        <form method="post" action="{{ route('etudiants.update', $etudiant) }}">
            @csrf
            @method('PUT')
            @include('etudiants._caches', ['donnees' => $donnees])
            <button type="submit">Confirmer la modification</button>
        </form>

        <form method="post" action="{{ route('etudiants.corriger', $etudiant) }}">
            @csrf
            @include('etudiants._caches', ['donnees' => $donnees])
            <button type="submit" class="secondaire">Modifier à nouveau</button>
        </form>

        <a href="{{ route('etudiants.show', $etudiant) }}">Annuler</a>
    </div>
</div>
@endsection
