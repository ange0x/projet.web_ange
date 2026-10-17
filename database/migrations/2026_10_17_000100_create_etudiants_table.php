<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etudiants', function (Blueprint $table) {
            $table->id();
            $table->string('numero_inscription', 20)->unique();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('nom_search', 100);
            $table->string('prenom_search', 100);
            $table->string('sexe', 20);
            $table->date('date_naissance');
            $table->string('lieu_naissance', 100)->nullable();
            $table->string('adresse', 255)->nullable();
            $table->string('telephone', 20);
            $table->string('email')->nullable()->unique();
            $table->string('filiere');
            $table->string('niveau');
            $table->string('annee_academique', 9);
            $table->date('date_inscription');
            $table->string('tuteur_nom', 100)->nullable();
            $table->string('tuteur_telephone', 20)->nullable();
            $table->foreignId('modifie_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('nom');
            $table->index('prenom');
            $table->index('nom_search');
            $table->index('prenom_search');
            $table->index('filiere');
            $table->index('niveau');
            $table->index('date_inscription');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('etudiants');
    }
};
