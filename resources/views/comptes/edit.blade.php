@extends('layouts.base')

@section('titre', 'Modifier un compte')

@section('contenu')
<div class="carte">
    <h1>Modifier le compte</h1>
    @if ($user->is(auth()->user()))
        <p class="aide">Vous modifiez votre propre compte : vous ne pouvez ni le désactiver ni changer votre rôle.</p>
    @endif
    @include('comptes._formulaire', ['action' => route('comptes.update', $user), 'bouton' => 'Enregistrer les modifications'])
</div>
@endsection
