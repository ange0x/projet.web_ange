@php
    $v = $valeurs ?? [];
    $sexes = collect(\App\Enums\Sexe::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all();
    $filieres = collect(config('ecole.filieres'))->mapWithKeys(fn ($f) => [$f => $f])->all();
    $niveaux = collect(config('ecole.niveaux'))->mapWithKeys(fn ($n) => [$n => $n])->all();
@endphp
<fieldset>
    <legend>Identité</legend>
    @include('etudiants._champ', ['champ' => 'nom', 'label' => 'Nom', 'obligatoire' => true, 'maxlength' => 100, 'defaut' => $v['nom'] ?? ''])
    @include('etudiants._champ', ['champ' => 'prenom', 'label' => 'Prénom', 'obligatoire' => true, 'maxlength' => 100, 'defaut' => $v['prenom'] ?? ''])
    @include('etudiants._liste', ['champ' => 'sexe', 'label' => 'Sexe', 'obligatoire' => true, 'options' => $sexes, 'defaut' => $v['sexe'] ?? ''])
    @include('etudiants._champ', ['champ' => 'date_naissance', 'label' => 'Date de naissance', 'type' => 'date', 'obligatoire' => true, 'max' => now()->subDay()->toDateString(), 'defaut' => $v['date_naissance'] ?? ''])
    @include('etudiants._champ', ['champ' => 'lieu_naissance', 'label' => 'Lieu de naissance', 'maxlength' => 100, 'defaut' => $v['lieu_naissance'] ?? ''])
</fieldset>

<fieldset>
    <legend>Coordonnées</legend>
    @include('etudiants._champ', ['champ' => 'adresse', 'label' => 'Adresse', 'maxlength' => 255, 'defaut' => $v['adresse'] ?? ''])
    @include('etudiants._champ', ['champ' => 'telephone', 'label' => 'Téléphone', 'type' => 'tel', 'mode' => 'tel', 'obligatoire' => true, 'aide' => '10 chiffres commençant par 01, avec ou sans +229.', 'defaut' => $v['telephone'] ?? ''])
    @include('etudiants._champ', ['champ' => 'email', 'label' => 'E-mail', 'type' => 'email', 'maxlength' => 255, 'aide' => 'Facultatif. Une adresse ne peut être utilisée que par un seul étudiant.', 'defaut' => $v['email'] ?? ''])
</fieldset>

<fieldset>
    <legend>Inscription</legend>
    @include('etudiants._liste', ['champ' => 'filiere', 'label' => 'Filière', 'obligatoire' => true, 'options' => $filieres, 'defaut' => $v['filiere'] ?? ''])
    @include('etudiants._liste', ['champ' => 'niveau', 'label' => 'Niveau', 'obligatoire' => true, 'options' => $niveaux, 'defaut' => $v['niveau'] ?? ''])
    @include('etudiants._champ', ['champ' => 'annee_academique', 'label' => 'Année académique', 'obligatoire' => true, 'maxlength' => 9, 'motif' => '[0-9]{4}-[0-9]{4}', 'aide' => 'Exemple : 2026-2027.', 'defaut' => $v['annee_academique'] ?? ''])
    @include('etudiants._champ', ['champ' => 'date_inscription', 'label' => 'Date d’inscription', 'type' => 'date', 'obligatoire' => true, 'max' => now()->toDateString(), 'defaut' => $v['date_inscription'] ?? ''])
</fieldset>

<fieldset>
    <legend>Tuteur (facultatif)</legend>
    @include('etudiants._champ', ['champ' => 'tuteur_nom', 'label' => 'Nom du tuteur', 'maxlength' => 100, 'defaut' => $v['tuteur_nom'] ?? ''])
    @include('etudiants._champ', ['champ' => 'tuteur_telephone', 'label' => 'Téléphone du tuteur', 'type' => 'tel', 'mode' => 'tel', 'aide' => '10 chiffres commençant par 01, avec ou sans +229.', 'defaut' => $v['tuteur_telephone'] ?? ''])
</fieldset>
