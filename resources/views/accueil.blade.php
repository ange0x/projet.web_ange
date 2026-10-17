@extends('layouts.base')

@section('titre', 'Accueil')

@section('contenu')
<div class="carte">
    <h1>Accueil</h1>
    <p>Bonjour {{ auth()->user()->name }} ({{ auth()->user()->roleLabel() }}).</p>

    @if (auth()->user()->isAdministrateur())
        <p><a href="{{ route('comptes.index') }}">Gérer les comptes</a></p>
    @endif

    <form method="post" action="{{ route('deconnexion') }}">
        @csrf
        <button type="submit">Se déconnecter</button>
    </form>
</div>
@endsection
