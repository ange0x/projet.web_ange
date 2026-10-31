@extends('layouts.base')

@section('titre', 'Comptes archivés')

@section('contenu')
<div class="carte carte-large">
    <h1>Comptes archivés</h1>

    @if (session('statut'))
        <div class="statut" role="status">{{ session('statut') }}</div>
    @endif

    <p class="actions">
        <a href="{{ route('comptes.index') }}">Retour aux comptes</a>
    </p>

    @if ($comptes->isEmpty())
        <p class="statut-liste" role="status">Aucun compte archivé.</p>
    @else
        <div class="tableau-defilant" role="region" aria-label="Comptes archivés" tabindex="0">
            <table>
                <caption>Comptes archivés ({{ $comptes->count() }})</caption>
                <thead>
                    <tr>
                        <th scope="col">Nom</th>
                        <th scope="col">Identifiant</th>
                        <th scope="col">Rôle</th>
                        <th scope="col">Archivé le</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comptes as $compte)
                        <tr>
                            <th scope="row">{{ $compte->name }}</th>
                            <td>{{ $compte->identifiant }}</td>
                            <td>{{ $compte->roleLabel() }}</td>
                            <td>{{ $compte->deleted_at->format('d/m/Y à H:i') }}</td>
                            <td>
                                <form method="post" action="{{ route('comptes.restaurer', $compte) }}">
                                    @csrf
                                    <button type="submit" class="secondaire">Restaurer<span class="visuellement-cache"> le compte de {{ $compte->name }}</span></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection