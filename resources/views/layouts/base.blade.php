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
    <main id="contenu">
        @yield('contenu')
    </main>
</body>
</html>
