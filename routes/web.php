<?php

use App\Http\Controllers\ConnexionController;
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
});
