@extends('layouts.base')

@section('titre', 'Créer un compte')

@section('contenu')
<div class="carte">
    <h1>Créer un compte</h1>
    @include('comptes._formulaire', ['action' => route('comptes.store'), 'bouton' => 'Créer le compte'])
</div>
@endsection
