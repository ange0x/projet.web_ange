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

                <div class="session">
                    <div class="session-identite">
                        <span class="avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="session-texte">
                            <span class="session-nom">{{ auth()->user()->name }}</span>
                            <span class="session-role">{{ auth()->user()->roleLabel() }}</span>
                        </span>
                    </div>
                    <form method="post" action="{{ route('deconnexion') }}">
                        @csrf
                        <button type="submit" class="bouton-entete">Se déconnecter</button>
                    </form>
                </div>

                <nav class="navigation" aria-label="Navigation principale">
                    <ul>
                        <li><a href="{{ route('accueil') }}" @if (request()->routeIs('accueil')) aria-current="page" @endif>Accueil</a></li>
                        <li><a href="{{ route('etudiants.index') }}" @if (request()->routeIs('etudiants.index', 'etudiants.show', 'etudiants.edit', 'etudiants.update', 'etudiants.confirmer', 'etudiants.corriger', 'etudiants.archiver.confirmer', 'etudiants.archives')) aria-current="page" @endif>Étudiants</a></li>
                        <li><a href="{{ route('etudiants.create') }}" @if (request()->routeIs('etudiants.create', 'etudiants.store')) aria-current="page" @endif>Enregistrer un étudiant</a></li>
                        @if (auth()->user()->isAdministrateur())
                            <li><a href="{{ route('comptes.index') }}" @if (request()->routeIs('comptes.*')) aria-current="page" @endif>Comptes</a></li>
                        @endif
                    </ul>
                </nav>
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

    <footer class="pied">
        <p>{{ config('ecole.nom') }} – Gestion des inscriptions</p>
    </footer>
</body>
</html>