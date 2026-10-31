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
        ->only(['index', 'create', 'store', 'show', 'edit', 'update'])
        ->parameters(['etudiants' => 'etudiant']);
    Route::post('/etudiants/{etudiant}/confirmer', [EtudiantController::class, 'confirmer'])->name('etudiants.confirmer');
    Route::post('/etudiants/{etudiant}/corriger', [EtudiantController::class, 'corriger'])->name('etudiants.corriger');

    Route::middleware('role:administrateur')->group(function () {
        Route::get('/comptes/archives', [CompteController::class, 'archives'])->name('comptes.archives');
        Route::resource('comptes', CompteController::class)
            ->only(['index', 'create', 'store', 'edit', 'update'])
            ->parameters(['comptes' => 'user']);
        Route::post('/comptes/{user}/debloquer', [CompteController::class, 'debloquer'])->name('comptes.debloquer');
        Route::get('/comptes/{user}/archiver', [CompteController::class, 'confirmerArchivage'])->name('comptes.archiver.confirmer');
        Route::post('/comptes/{user}/archiver', [CompteController::class, 'archiver'])->name('comptes.archiver');
        Route::post('/comptes/{user}/restaurer', [CompteController::class, 'restaurer'])->withTrashed()->name('comptes.restaurer');
    });
});