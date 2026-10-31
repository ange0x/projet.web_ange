@extends('layouts.base')

@section('titre', 'Archiver un compte')

@section('contenu')
<div class="carte">
    <h1>Archiver le compte</h1>

    <p>Vous allez archiver le compte de <strong>{{ $user->name }}</strong> (identifiant : {{ $user->identifiant }}, rôle : {{ $user->roleLabel() }}).</p>
    <p>Ce compte ne pourra plus se connecter et disparaîtra de la liste. Vous pourrez le restaurer depuis les comptes archivés. Les actions déjà faites avec ce compte restent dans le journal.</p>

    <div class="actions">
        <form method="post" action="{{ route('comptes.archiver', $user) }}">
            @csrf
            <button type="submit" class="danger">Archiver le compte</button>
        </form>
        <a href="{{ route('comptes.index') }}">Annuler</a>
    </div>
</div>
@endsection