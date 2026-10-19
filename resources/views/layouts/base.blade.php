<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titre') – {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/ecole.css') }}">
</head>
<body>
    <a class="lien-evitement" href="#contenu">Aller au contenu</a>

    <header class="entete">
        <div class="entete-interieur">
            @auth
                <a class="marque" href="{{ route('accueil') }}">
                    <span class="marque-nom">{{ config('ecole.nom') }}</span>
                    <span class="marque-service">Gestion des inscriptions</span>
                </a>

                <nav class="navigation" aria-label="Navigation principale">
                    <ul>
                        <li><a href="{{ route('accueil') }}" @if (request()->routeIs('accueil')) aria-current="page" @endif>Accueil</a></li>
                        <li><a href="{{ route('etudiants.index') }}" @if (request()->routeIs('etudiants.index', 'etudiants.show', 'etudiants.edit', 'etudiants.update', 'etudiants.confirmer', 'etudiants.corriger')) aria-current="page" @endif>Étudiants</a></li>
                        <li><a href="{{ route('etudiants.create') }}" @if (request()->routeIs('etudiants.create', 'etudiants.store')) aria-current="page" @endif>Enregistrer un étudiant</a></li>
                        @if (auth()->user()->isAdministrateur())
                            <li><a href="{{ route('comptes.index') }}" @if (request()->routeIs('comptes.*')) aria-current="page" @endif>Comptes</a></li>
                        @endif
                    </ul>
                </nav>

                <div class="session">
                    <span>{{ auth()->user()->name }} ({{ auth()->user()->roleLabel() }})</span>
                    <form method="post" action="{{ route('deconnexion') }}">
                        @csrf
                        <button type="submit" class="bouton-entete">Se déconnecter</button>
                    </form>
                </div>
            @else
                <p class="marque">
                    <span class="marque-nom">{{ config('ecole.nom') }}</span>
                    <span class="marque-service">Gestion des inscriptions</span>
                </p>
            @endauth
        </div>
    </header>

    <main id="contenu">
        @yield('contenu')
    </main>
</body>
</html>
