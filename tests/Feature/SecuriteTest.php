<?php

use Illuminate\Support\Facades\Route;

it('ne sert aucun fichier du disque de stockage', function () {
    $routes = collect(Route::getRoutes()->getRoutes())->map(fn ($route) => $route->uri());

    expect($routes->contains(fn (string $uri) => str_starts_with($uri, 'storage/')))->toBeFalse();
});

it('répond 404 pour les fichiers sensibles du projet', function (string $chemin) {
    $this->get($chemin)->assertNotFound();
})->with(['/.env', '/composer.json', '/artisan', '/storage/logs/laravel.log', '/storage/app/private/x']);

it('n\'affiche aucun détail technique sur une page introuvable', function () {
    $this->get('/page-qui-nexiste-pas')
        ->assertNotFound()
        ->assertDontSee('vendor/laravel')
        ->assertDontSee('Stack trace');
});
