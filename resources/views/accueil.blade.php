@extends('layouts.base')

@section('titre', 'Accueil')

@section('contenu')
<div class="carte">
    <h1>Accueil</h1>
    <p>Bonjour {{ auth()->user()->name }} ({{ auth()->user()->roleLabel() }}).</p>

    <form method="post" action="{{ route('deconnexion') }}">
        @csrf
        <button type="submit">Se déconnecter</button>
    </form>
</div>
@endsection
