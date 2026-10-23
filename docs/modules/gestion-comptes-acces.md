# Plan d'implémentation - Module 1 : Gestion des comptes et des accès

## 1. Identification

- **Projet** : Gestion des inscriptions - ESGC Verechaguine AK
- **Module** : 1 - Gestion des comptes et des accès (user stories US1, US2, US3)
- **Version du plan** : 1.1 (plan rétrospectif, corrigé après relecture ; version 1.0 conservée dans l'historique Git)
- **Auteur** : à compléter
- **Date** : 22 octobre 2026
- **Statut du module** : codé et testé automatiquement ; **non validé**. Cadrage (étape 0) en cours : décisions Q2, Q3, Q6 prises ; Q1, Q4, Q5, Q7 ouvertes.
- **Validateur** : à renseigner (enseignant)

> **Nature de ce document.** Ce plan a été rédigé **après** l'implémentation du module. Il n'a pas guidé le code : le travail a suivi l'ordre cahier des charges, user stories, tests, code. Pour rester honnête, les statuts ci-dessous ne décrivent que ce qui a été constaté (commandes exécutées, résultats lus). Aucune étape n'est « validée » : aucun validateur n'a encore décidé. Dans le journal des décisions, « validée par l'étudiant » signifie seulement que l'étudiant a fait ce choix ; la validation par l'enseignant reste en attente.
>
> **Version 1.1.** La relecture du plan face au code a montré que la version 1.0 affichait « testé » pour des règles dont seule une partie est testée (R03 : le temps de réponse n'est pas mesuré ; R06 : la protection CSRF est désactivée dans les tests et le renouvellement de session n'est pas testé). Ces états sont corrigés en section 7. Les décisions Q2, Q3 et Q6 ont ajouté deux règles (R13, R14), **prévues et non implémentées**.

**Vocabulaire des états** : *prévu* (décrit) ; *codé* (le code existe) ; *testé* (des tests ont été exécutés avec un résultat lu) ; *validé* (un validateur a accepté). Un état n'implique pas les suivants.

## 2. Contexte, problème et objectifs

**Contexte.** L'école enregistre les inscriptions à la main. Une application web doit protéger des données personnelles d'étudiants : seules les personnes autorisées doivent y accéder, avec les droits correspondant à leur rôle.

**Utilisateurs concernés.**
- **Administrateur** : gère les comptes des utilisateurs.
- **Secrétariat** : utilise l'application au quotidien (modules 2 et 3), sans gérer les comptes.

**Parcours principal.** Un administrateur crée un compte pour un membre du secrétariat. Cette personne se connecte avec son identifiant et son mot de passe, travaille, puis se déconnecte. Si elle quitte l'école, l'administrateur désactive son compte.

**Objectifs.**
- Réserver l'application aux personnes autorisées.
- Limiter chaque personne aux actions de son rôle.
- Rendre les attaques sur la connexion difficiles et laisser une trace des actions sensibles.

## 3. Périmètre

**Inclus.**
- Connexion et déconnexion (US1, US2).
- Liste, création et modification des comptes, y compris activation et désactivation (US3).
- Contrôle des accès par rôle et par état du compte.
- Blocage temporaire après échecs répétés, journal d'audit.
- Déblocage d'un compte bloqué par l'administrateur (R13, **prévu**).
- Protection du dernier administrateur actif (R14, **prévu**).

**Exclus (non réalisés dans ce module).**
- Mot de passe oublié par e-mail, et changement de mot de passe par l'utilisateur lui-même (décision D6 : exclus, car l'envoi d'e-mails dépasse le cadre du projet ; l'administrateur peut définir un nouveau mot de passe d'un compte).
- Suppression d'un compte (la désactivation sert de retrait).
- Double authentification, option « se souvenir de moi », auto-inscription.
- Connexion par un service externe.

## 4. Existant, stack et contraintes

### 4.1 Versions relevées

| Élément | Valeur relevée | Source |
|---|---|---|
| PHP | 8.5.11 | commande `php -v` |
| Laravel | 13.x (framework 13.36.0 à l'installation ; 13.37.0 affiché plus tard) | sortie de `composer create-project`, page d'erreur |
| Composer | 2.10.1 | commande `composer -V` |
| Base de développement | MySQL/MariaDB fournie par XAMPP ; version **à confirmer** | phpMyAdmin |
| Base des tests | SQLite en mémoire | message d'erreur d'un test |
| Tests | Pest ; version **à confirmer** | présence de `tests/Pest.php` |
| Journal d'audit | `spatie/laravel-activitylog` ; version **à confirmer** | `User` utilise `LogsActivity`, table `activity_log` |
| Interface | pages Blade et CSS simple, sans framework JavaScript | dépôt |

### 4.2 Déjà présent, créé par nous, à confirmer

| Catégorie | Éléments |
|---|---|
| **Déjà présents au départ** (origine non documentée, voir Q1) | `User`, `Role`, `AuthenticationService`, `LoginRequest`, `EnsureUserIsActive`, `EnsureUserHasRole`, `UserPolicy`, migration `users`, configurations `csp.php` et `backup.php` |
| **Modifiés par nous** | `AuthenticationService` (compteur remis à zéro à la fin du blocage, hachage factice pour les comptes inconnus), `LoginRequest` (messages en français, longueur maximale du mot de passe) |
| **Créés par nous** | `ConnexionController`, `CompteController`, `StoreUserRequest` et `UpdateUserRequest` (réécrits), routes, enregistrement des middlewares dans `bootstrap/app.php`, vues de connexion, d'accueil et des comptes, feuille de style, tests `ConnexionTest` et `ComptesTest` |
| **À confirmer** | la CSP est-elle appliquée aux pages ? ; durée de vie et expiration de la session ; algorithme de hachage réellement utilisé ; sauvegardes (configurées, jamais exécutées) |

### 4.3 Contraintes

- Application web en pages serveur ; pas d'API REST dans cette version.
- Recommandations OWASP et WCAG 2.2 niveau AA visées (voir le niveau de preuve en section 7).
- La session est stockée dans la table `sessions` de MySQL : si MySQL est arrêté, toute page répond par une erreur 500 (observé pendant le développement).

## 5. Règles métier et autorisations

| N° | Règle |
|---|---|
| **R01** | Toute page, sauf la connexion, exige d'être connecté. Un visiteur est renvoyé vers `/connexion`. |
| **R02** | Le mot de passe est haché avant enregistrement ; il n'est jamais stocké, affiché ni journalisé en clair. |
| **R03** | Tout échec de connexion (identifiant inconnu, mot de passe faux, compte désactivé, compte bloqué) produit le **même message** : « Identifiant ou mot de passe incorrect. ». Le temps de réponse est comparable grâce à un hachage factice. |
| **R04** | Après **5 échecs consécutifs** sur un compte existant, le compte est bloqué **15 minutes**, même avec le bon mot de passe. Le compteur repart à zéro à la fin du blocage et après une connexion réussie. |
| **R05** | Un compte désactivé ne peut pas se connecter. S'il est désactivé pendant une session, l'utilisateur est déconnecté à sa requête suivante. |
| **R06** | La session est renouvelée à la connexion. La déconnexion invalide la session et ne se fait que par requête POST protégée par jeton CSRF. |
| **R07** | Il existe deux rôles : administrateur et secrétariat. Seul l'administrateur accède à la gestion des comptes ; le secrétariat reçoit une erreur 403. Le contrôle est fait **côté serveur** (middleware de rôle et politique), pas seulement en masquant les liens. |
| **R08** | Création et modification d'un compte : nom obligatoire (100 caractères au plus) ; identifiant obligatoire, unique, 100 caractères au plus, composé de lettres, chiffres, point, tiret et tiret bas ; mot de passe de 8 à 255 caractères avec confirmation ; rôle choisi dans la liste. À la modification, un mot de passe laissé vide conserve l'ancien. |
| **R09** | Un administrateur ne peut ni désactiver son propre compte, ni modifier son propre rôle. |
| **R10** | Aucune suppression de compte dans ce module. |
| **R11** | Les actions sensibles sont journalisées avec leur auteur : connexion réussie, connexion échouée, déconnexion, création et modification de compte. Aucun mot de passe n'apparaît dans le journal. |
| **R12** | La route de connexion accepte au plus 10 tentatives par minute. *Configuré ; aucun test n'existe pour cette règle.* |
| **R13** *(prévu)* | L'administrateur peut débloquer un compte bloqué avant la fin des 15 minutes : le compteur d'échecs et la date de fin de blocage sont remis à zéro, et l'action est journalisée avec son auteur. Le secrétariat ne le peut pas (403). |
| **R14** *(prévu)* | Il reste toujours au moins un administrateur **actif** : la désactivation ou le changement de rôle du dernier administrateur actif est refusé, quel que soit l'auteur de la demande. |

### Matrice des accès

| Action | Visiteur | Secrétariat | Administrateur |
|---|---|---|---|
| Afficher la page de connexion | oui | renvoyé vers l'accueil | renvoyé vers l'accueil |
| Se connecter | oui | non applicable | non applicable |
| Accueil et déconnexion | renvoyé vers la connexion | oui | oui |
| Liste, création, modification des comptes | renvoyé vers la connexion | **403** | oui (sauf R09 et R14) |
| Débloquer un compte *(prévu, R13)* | renvoyé vers la connexion | **403** | oui |

## 6. Données, API et interface

### 6.1 Données

**Table `users`** : `id`, `name` (100), `identifiant` (unique), `password` (haché), `role` (texte : `administrateur` ou `secretariat`), `actif` (booléen, vrai par défaut), `failed_login_attempts` (entier), `locked_until` (date, nullable), `remember_token`, `created_at`, `updated_at`.

**Tables associées** : `sessions` (sessions en base), `password_reset_tokens` (créée, **non utilisée**), `activity_log` (journal d'audit).

### 6.2 Routes (pages HTML)

| Méthode et adresse | Nom | Accès | Réponses principales |
|---|---|---|---|
| GET `/connexion` | `connexion` | visiteur | 200 ; renvoi vers l'accueil si déjà connecté |
| POST `/connexion` | `connexion.soumettre` | visiteur, 10 par minute | redirection vers l'accueil ; retour au formulaire avec erreurs ; 429 au-delà de la limite |
| POST `/deconnexion` | `deconnexion` | connecté | redirection vers la connexion ; 405 en GET |
| GET `/` | `accueil` | connecté | 200 |
| GET `/comptes` | `comptes.index` | administrateur | 200 ; 403 pour le secrétariat |
| GET `/comptes/create`, POST `/comptes` | `comptes.create`, `comptes.store` | administrateur | 200 ; redirection avec message ; retour au formulaire avec erreurs |
| GET `/comptes/{user}/edit`, PUT `/comptes/{user}` | `comptes.edit`, `comptes.update` | administrateur | idem |
| POST `/comptes/{user}/debloquer` *(à créer, R13)* | `comptes.debloquer` | administrateur | redirection avec message ; 403 pour le secrétariat |

Un formulaire invalide renvoie vers la page du formulaire avec les erreurs (et non un code 422, propre aux API). Un jeton CSRF absent ou expiré renvoie 419.

### 6.3 Interface et états

- Résumé d'erreurs annoncé (`role="alert"`), champs invalides marqués (`aria-invalid`), message de succès annoncé (`role="status"`).
- Saisie conservée après une erreur (sauf les mots de passe).
- Tableau des comptes avec légende, en-têtes de colonnes et défilement horizontal dans son cadre.
- *État de l'accessibilité : codé ; **non validé*** (voir section 7).

## 7. Qualité et risques

| Domaine | Mesure | État |
|---|---|---|
| Authentification : message unique | R03 (message) | testé |
| Authentification : temps de réponse comparable | R03 (hachage factice) | codé ; **non mesuré** |
| Authentification : blocage | R04, R05 | testé |
| Session : déconnexion par POST | R06 (méthode POST, redirection) | testé |
| Session : renouvellement à la connexion et jeton CSRF | R06 | codé ; **non testé** (la vérification CSRF est désactivée dans les tests de Laravel) |
| Contrôle d'accès | R01, R07 côté serveur | testé |
| Injection et XSS | requêtes paramétrées, échappement automatique des vues | testé (formulaires d'inscription) ; non testé sur les pages des comptes |
| Journalisation | R11 | testé (création de compte et connexion réussie) ; **à corriger** : voir le risque sur l'identifiant saisi |
| Limitation des tentatives | R12 | codé ; **non testé** |
| Accessibilité WCAG 2.2 AA | étiquettes, focus, contrastes calculés | codé ; **non validé** (ni Lighthouse, ni parcours au clavier, ni lecteur d'écran) |
| Sessions | durée et expiration | **à confirmer** |

**Risques.**
- **Journal des échecs de connexion** : il enregistre l'identifiant saisi. Si une personne tape son mot de passe dans le champ identifiant, il s'écrit **en clair** dans le journal (journalisation de données sensibles). Décision attendue : question Q7.
- **Concurrence** : si deux administrateurs se retirent leurs droits en même temps, il pourrait ne plus rester d'administrateur. R14 réduit ce risque ; une vraie garantie suppose un verrouillage en base lors de la modification.
- **Compte de développement** : un compte administrateur de test existe dans la base locale avec un mot de passe plus court que la règle R08. Son mot de passe ne doit figurer dans **aucun fichier du dépôt**, et le compte ne doit pas exister en production.
- **Dépendance à MySQL** : la session étant en base, une coupure rend tout le site inaccessible.
- **Tests sur SQLite** : un comportement propre à MySQL peut passer inaperçu (cas déjà rencontré pour la casse des adresses e-mail).

## 8. Questions ouvertes et décisions

### Questions ouvertes

| N° | Question | Impact | Qui décide | Décision attendue |
|---|---|---|---|---|
| Q1 | D'où viennent les fichiers présents au départ (modèle, politiques, services, configurations de CSP et de sauvegarde) ? | Traçabilité du code et honnêteté du dossier | L'étudiant, avec l'enseignant | Déclarer l'origine dans le dossier |
| ~~Q2~~ | ~~Faut-il un mot de passe oublié ou un changement de mot de passe par l'utilisateur ?~~ | Droits, sécurité, e-mail | Enseignant | **Tranchée** : exclus (D6) |
| ~~Q3~~ | ~~L'administrateur doit-il pouvoir débloquer un compte avant 15 minutes ?~~ | Confort d'exploitation | Enseignant | **Tranchée** : oui (D7, règle R13) |
| Q4 | Quelle durée de session et quelle expiration après inactivité ? | Sécurité | Enseignant | Valeur à fixer, puis à tester |
| Q5 | Quelle politique de mot de passe (aujourd'hui : 8 caractères au moins, sans complexité) ? | Sécurité | Enseignant | Maintenir ou durcir |
| ~~Q6~~ | ~~Comment garantir qu'il reste toujours un administrateur actif ?~~ | Risque de verrouillage total | Enseignant | **Tranchée** : règle et test (D8, règle R14) |
| Q7 | Que faire de l'identifiant saisi dans le journal des échecs de connexion ? | Fuite possible d'un mot de passe tapé au mauvais endroit | Enseignant | Ne plus l'enregistrer, ou l'enregistrer seulement s'il correspond à un compte existant |

### Journal des décisions

| N° | Décision | Origine | Statut |
|---|---|---|---|
| D1 | Deux rôles : administrateur et secrétariat | demandé par l'étudiant | validée par l'étudiant |
| D2 | Mot de passe de 8 caractères au moins avec confirmation | cahier des charges | à valider |
| D3 | Un administrateur ne peut pas se désactiver ni changer son rôle (R09) | proposition de l'assistant IA | à valider |
| D4 | Identifiant limité à lettres, chiffres, point, tiret et tiret bas | proposition de l'assistant IA | à valider |
| D5 | Blocage après 5 échecs pendant 15 minutes (R04) | présent dans le code de départ | à valider |
| D6 | Mot de passe oublié et changement par l'utilisateur exclus du module | choix laissé à l'assistant IA par l'étudiant (« fais ce qui est mieux »), recommandation : exclure | à valider par l'enseignant |
| D7 | L'administrateur peut débloquer un compte (R13) | choisi par l'étudiant | validée par l'étudiant ; à valider par l'enseignant |
| D8 | Impossible de retirer le dernier administrateur actif (R14) | choisi par l'étudiant | validée par l'étudiant ; à valider par l'enseignant |

## Étape 0 - Vérifier et valider le cadrage

**Statut : en cours** (non réalisée avant le code ; rejouée maintenant).

### Objectif et prérequis
Rendre ce plan applicable au projet réel. Prérequis : accès au dépôt et aux consignes de l'enseignant.

### Travaux à réaliser et fichiers concernés
Répondre aux questions Q1 à Q7 ; relire le plan face au code ; relever les faits « à confirmer » ; faire jouer sur papier une connexion réussie, un refus et un blocage ; faire relire ce document par l'enseignant. Fichier concerné : `docs/modules/gestion-comptes-acces.md`.

### Règles applicables et points de vigilance
R01 à R14 (R13 et R14 sont prévues). Ne pas considérer comme acquis ce qui est marqué « à confirmer » en section 4.

### Livrables
Ce document relu et la matrice des accès (section 5).

### Tests et cas limites
Sans objet (revue documentaire).

### Critères de sortie
- [x] Relecture du plan face au code : écarts relevés et corrigés dans la version 1.1.
- [x] Questions Q2, Q3 et Q6 tranchées (D6, D7, D8).
- [ ] Questions Q1, Q4, Q5 et Q7 tranchées par la personne indiquée.
- [ ] Faits « à confirmer » de la section 4 relevés (versions, session, hachage, CSP).
- [ ] Origine du code de départ déclarée (Q1).
- [ ] Document relu par l'enseignant.

### Bilan d'exécution
Relecture du plan contre le code connu : cinq écarts relevés (R03, R06, matrice des accès, journal des échecs, faits à confirmer). Aucune commande de relevé n'a encore été exécutée pour la section 4. Limite : tout le code existe déjà ; l'étape 1 ne commence qu'après validation de l'étape 0.

### Validation
Validateur : à renseigner. Décision : en attente.

## Étape 1 - Données et autorisations

**Statut : à valider.**

### Objectif et prérequis
Disposer d'un socle de comptes protégé. Prérequis : base de données accessible.

### Travaux à réaliser et fichiers concernés
Table `users` (`database/migrations/0001_01_01_000000_create_users_table.php`), `app/Models/User.php`, `app/Enums/Role.php`, `app/Policies/UserPolicy.php`, middlewares `EnsureUserIsActive` et `EnsureUserHasRole`, enregistrement dans `bootstrap/app.php`.

### Règles applicables et points de vigilance
R01, R05, R07, R10. Les droits sont vérifiés par le serveur à chaque requête.

### Livrables
Structure migrée, politique d'accès, middlewares enregistrés, tests d'accès.

### Tests et cas limites
- **Nominal** : l'administrateur ouvre la liste des comptes.
- **Refus d'accès** : le secrétariat reçoit 403 sur les cinq routes des comptes ; un visiteur est renvoyé vers la connexion.
- **Cas limite** : un utilisateur désactivé pendant sa session est déconnecté à la requête suivante.

### Critères de sortie
- [x] Migration appliquée sur la base de développement (`migrate:status` : « Ran »).
- [x] Tests d'accès réussis (fichier `ComptesTest.php`).
- [ ] Migration vérifiée sur une base MySQL de test (les tests utilisent SQLite en mémoire).

### Bilan d'exécution
- **Fichiers** : ceux listés ci-dessus.
- **Commandes** : `php artisan migrate:status` (toutes les migrations « Ran », relevé lors du démarrage du projet) ; suite complète `php artisan test` : **163 tests réussis, 590 assertions** (dernier relevé lu, avant la fusion de la refonte de l'interface).
- **Limites** : comportement sur MySQL non testé par la suite automatique.

### Validation
Validateur : à renseigner. Décision : en attente.

## Étape 2 - Connexion et déconnexion (US1, US2)

**Statut : à valider.**

### Objectif et prérequis
Permettre de se connecter et de se déconnecter de façon sûre. Prérequis : étape 1.

### Travaux à réaliser et fichiers concernés
`app/Services/AuthenticationService.php`, `app/Http/Requests/LoginRequest.php`, `app/Http/Controllers/ConnexionController.php`, `routes/web.php`, `resources/views/auth/connexion.blade.php`, `resources/views/layouts/base.blade.php`, `public/css/ecole.css`, `tests/Feature/ConnexionTest.php`.

**À traiter dans cette étape, après validation de l'étape 0** : test de la limite R12 ; correction du journal des échecs (Q7) ; vérification manuelle de la protection CSRF et du renouvellement de session ; mesure du temps de réponse (R03) si l'enseignant l'exige.

### Règles applicables et points de vigilance
R02, R03, R04, R05, R06, R11, R12. Le message d'échec doit rester identique dans tous les cas, y compris pour le temps de réponse.

### Livrables
Page de connexion accessible, service d'authentification, journal d'audit, 13 tests.

### Tests et cas limites
- **Nominal** : identifiants corrects, accès à l'accueil.
- **Refus** : mot de passe faux, identifiant inconnu, compte désactivé : même message ; champs vides signalés ; déconnexion par simple lien (GET) refusée (405).
- **Cas limites** : blocage après 5 échecs même avec le bon mot de passe ; déblocage après 15 minutes ; pas de reverrouillage au premier échec suivant.

### Critères de sortie
- [x] 13 tests de connexion réussis (35 assertions).
- [x] Essai manuel de la connexion avec le compte administrateur de développement : fonctionne.
- [ ] Test de la limite de 10 tentatives par minute (R12).
- [ ] Journal des échecs corrigé et testé (Q7).
- [ ] Protection CSRF et renouvellement de session vérifiés à la main.
- [ ] Parcours au clavier, zoom à 200 %, Lighthouse.

### Bilan d'exécution
- **Fichiers modifiés** : ceux listés ci-dessus ; fusion dans `develop` par la pull request n°4 (commit `1cc7e16`).
- **Commandes** : `php artisan test --filter=Connexion` : 13 réussis, 35 assertions.
- **Limites** : R12 non testée ; accessibilité non vérifiée ; expiration de session non vérifiée (Q4).

### Validation
Validateur : à renseigner. Décision : en attente.

## Étape 3 - Gestion des comptes (US3)

**Statut : à valider.**

### Objectif et prérequis
Permettre à l'administrateur de créer, modifier, activer et désactiver des comptes. Prérequis : étapes 1 et 2.

### Travaux à réaliser et fichiers concernés
`app/Http/Controllers/CompteController.php`, `app/Http/Requests/StoreUserRequest.php`, `app/Http/Requests/UpdateUserRequest.php`, vues `resources/views/comptes/` (liste, création, modification, formulaire commun), `routes/web.php`, `tests/Feature/ComptesTest.php`.

**À ajouter dans cette étape, après validation de l'étape 0** : déblocage d'un compte par l'administrateur (R13 : route `comptes.debloquer`, bouton sur la liste, journalisation) et protection du dernier administrateur actif (R14 : refus dans `UpdateUserRequest`, avec message clair).

### Règles applicables et points de vigilance
R01, R02, R07, R08, R09, R10, R11, R13, R14. Le mot de passe est facultatif à la modification. Aucun mot de passe ne doit apparaître dans le journal.

### Livrables
Pages de gestion accessibles, validations, journalisation, 23 tests.

### Tests et cas limites
- **Nominal** : création d'un compte valide avec mot de passe haché ; modification du nom et du rôle ; désactivation puis réactivation.
- **Refus d'accès** : le secrétariat reçoit 403 sur toutes les pages et actions ; aucun compte n'est créé.
- **Cas limites** : mot de passe de 7 caractères refusé ; confirmation différente refusée ; identifiant déjà pris refusé ; garder son propre identifiant accepté ; un administrateur ne peut pas se désactiver ni changer son rôle ; aucun mot de passe dans le journal. **À écrire (R13)** : l'administrateur débloque un compte bloqué, qui peut alors se connecter ; le secrétariat reçoit 403 ; l'action est journalisée. **À écrire (R14)** : avec un seul administrateur actif, sa désactivation et son changement de rôle sont refusés, y compris par une requête falsifiée ; avec deux administrateurs, l'un peut modifier l'autre.

### Critères de sortie
- [x] 23 tests de gestion des comptes réussis.
- [ ] Parcours manuel complet avec un compte administrateur et un compte secrétariat.
- [ ] Parcours au clavier, zoom à 200 %, Lighthouse.
- [x] Question Q6 tranchée (D8).
- [ ] Règle R13 (déblocage) implémentée et testée.
- [ ] Règle R14 (dernier administrateur) implémentée et testée.

### Bilan d'exécution
- **Fichiers modifiés** : ceux listés ci-dessus ; fusion dans `develop` par la pull request n°5 (commit `da753e2`).
- **Commandes** : `php artisan test --filter=Comptes` : 23 réussis ; suite complète à ce moment : 38 réussis, 108 assertions.
- **Limites** : R13 et R14 sont prévues mais **non implémentées** ; la suppression, le mot de passe oublié et le changement de mot de passe par l'utilisateur n'existent pas, par décision (voir section 3).

### Validation
Validateur : à renseigner. Décision : en attente.

## Étape 4 - Recette et préparation de la livraison

**Statut : à faire.**

### Objectif et prérequis
Prouver que le module répond au besoin. Prérequis : étapes 1 à 3.

### Travaux à réaliser et fichiers concernés
Exécuter la recette globale ci-dessous ; mesurer l'accessibilité ; tester la limite de tentatives ; vérifier la CSP et la durée de session ; rédiger le rapport.

### Règles applicables et points de vigilance
R01 à R14. Aucun compte de test ni mot de passe de développement en production ; `APP_DEBUG=false` ; HTTPS ; racine du serveur sur le dossier `public/`.

### Livrables
Rapport de recette, limites connues à jour, procédure de déploiement et de retour arrière.

### Tests et cas limites
Scénario complet avec un compte administrateur et un compte secrétariat. Nominal : créer le compte, se connecter, travailler, se déconnecter. Refus : le secrétariat tente d'ouvrir `/comptes`. Limite : compte bloqué puis débloqué, compte désactivé pendant une session.

### Critères de sortie
- [ ] Recette globale exécutée avec preuves.
- [ ] Défauts bloquants résolus.
- [ ] Validation de l'enseignant.

### Bilan d'exécution
Non réalisé. Un seul contrôle de sécurité a été fait : les adresses `/.env` et `/composer.json` répondent 404 sur le serveur de développement.

### Validation
Validateur : à renseigner. Décision : en attente.

## Recette globale

| N° | Critère | Contrôle automatique | Contrôle manuel |
|---|---|---|---|
| CA01 | Identifiants corrects : accès à l'application | réussi | fait (administrateur) |
| CA02 | Échecs de connexion : message unique (temps de réponse non mesuré) | réussi | non fait |
| CA03 | Cinq échecs : blocage ; déblocage après 15 minutes | réussi | non fait |
| CA04 | Déconnexion par POST ; retour à la connexion | réussi | non fait |
| CA05 | Le secrétariat reçoit 403 sur les comptes | réussi | non fait |
| CA06 | Création d'un compte valide, mot de passe haché | réussi | non fait |
| CA07 | Mot de passe trop court ou confirmation différente refusés | réussi | non fait |
| CA08 | Un administrateur ne peut pas se désactiver | réussi | non fait |
| CA09 | Journal sans mot de passe | réussi | non fait |
| CA10 | Limite de 10 tentatives par minute | **absent** | non fait |
| CA11 | Accessibilité WCAG 2.2 AA | **absent** | non fait |
| CA12 | L'administrateur débloque un compte bloqué (R13) | **absent** (prévu) | non fait |
| CA13 | Le dernier administrateur actif ne peut pas être retiré (R14) | **absent** (prévu) | non fait |

## Limites connues

- Pas de mot de passe oublié ni de changement de mot de passe par l'utilisateur (décision D6). Le déblocage manuel (R13) est prévu mais pas encore implémenté.
- Un changement de mot de passe n'invalide pas les autres sessions de la personne (comportement non vérifié).
- Aucune garantie de conserver au moins un administrateur tant que R14 n'est pas implémentée ; même après, deux actions simultanées pourraient la contourner sans verrouillage en base.
- Le journal des échecs de connexion peut contenir un mot de passe saisi dans le champ identifiant (Q7).
- Tests exécutés sur SQLite ; aucun test sur la base MySQL cible.
- Limite de tentatives par minute et accessibilité non vérifiées.
- Journal d'audit non consultable depuis l'application.

## Consignes de livraison

- Créer le premier administrateur en production avec un mot de passe long et unique ; ne jamais réutiliser celui de développement.
- `APP_ENV=production`, `APP_DEBUG=false`, HTTPS obligatoire, racine du serveur sur `public/`.
- Désactiver l'outil de diagnostic de développement en production.
- Contrôler après déploiement que `/.env` et `/composer.json` répondent 404.
- Ce plan et son bilan sont à mettre à jour à chaque étape, avec les résultats réels des commandes.
