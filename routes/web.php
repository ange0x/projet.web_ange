<?php

use App\Http\Controllers\CompteController;
use App\Http\Controllers\ConnexionController;
use App\Http\Controllers\EtudiantController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/connexion', [ConnexionController::class, 'afficher'])->name('connexion');
    Route::post('/connexion', [ConnexionController::class, 'soumettre'])
        ->middleware('throttle:10,1')
        ->name('connexion.soumettre');
});

Route::middleware('auth')->group(function () {
    Route::view('/', 'accueil')->name('accueil');
    Route::post('/deconnexion', [ConnexionController::class, 'deconnecter'])->name('deconnexion');

    Route::resource('etudiants', EtudiantController::class)
        ->only(['create', 'store']);

    Route::middleware('role:administrateur')->group(function () {
        Route::resource('comptes', CompteController::class)
            ->only(['index', 'create', 'store', 'edit', 'update'])
            ->parameters(['comptes' => 'user']);
    });
});
