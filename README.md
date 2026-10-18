# Gestion des inscriptions – ESGC Verechaguine AK

Application web permettant à l'administration de l'ESGC Verechaguine AK
d'enregistrer les étudiants, de consulter leur liste, de les rechercher
et de modifier leurs informations.

## Fonctionnalités

- Connexion et déconnexion, avec blocage temporaire après 5 échecs
- Gestion des comptes par l'administrateur (création, modification, désactivation)
- Enregistrement d'un étudiant, avec numéro d'inscription automatique
- Liste des étudiants avec tri et pagination
- Fiche détaillée d'un étudiant
- Recherche par nom, prénom ou numéro, et filtres par filière et niveau
- Modification d'une fiche avec page de confirmation
- Journal d'audit des actions sensibles

## Technologies

- Backend : PHP et Laravel 13
- Base de données : MySQL (MariaDB avec XAMPP)
- Serveur web : Apache en production, `php artisan serve` en développement
- Interface : pages Blade (HTML, CSS)
- Tests : Pest

## Exigences de qualité

- Sécurité : recommandations OWASP
- Accessibilité : WCAG 2.2 niveau AA

## Installation

Prérequis : PHP 8.3 ou plus, Composer, et un serveur MySQL ou MariaDB.
Node.js n'est pas nécessaire pour l'instant.

```
git clone https://github.com/ange0x/projet.web_ange.git
cd projet.web_ange
composer install
cp .env.example .env
php artisan key:generate
```

Créez la base et un utilisateur dédié (par exemple dans phpMyAdmin, onglet SQL),
en remplaçant `MOT_DE_PASSE_A_CHOISIR` :

```
CREATE DATABASE inscriptions_esgc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'inscriptions_user'@'localhost' IDENTIFIED BY 'MOT_DE_PASSE_A_CHOISIR';
CREATE USER 'inscriptions_user'@'127.0.0.1' IDENTIFIED BY 'MOT_DE_PASSE_A_CHOISIR';
GRANT ALL PRIVILEGES ON inscriptions_esgc.* TO 'inscriptions_user'@'localhost';
GRANT ALL PRIVILEGES ON inscriptions_esgc.* TO 'inscriptions_user'@'127.0.0.1';
FLUSH PRIVILEGES;
```

Renseignez `DB_PASSWORD` dans le fichier `.env` (il n'est jamais envoyé sur GitHub),
puis créez les tables :

```
php artisan migrate
```

Créez le premier compte administrateur, avec un mot de passe d'au moins 8 caractères :

```
php artisan tinker --execute="App\Models\User::create(['name'=>'Administrateur','identifiant'=>'admin','password'=>'MOT_DE_PASSE_A_CHOISIR','role'=>App\Enums\Role::Administrateur,'actif'=>true]);"
```

## Démarrer le projet en développement

Avec XAMPP, démarrez d'abord MySQL :

```
sudo /opt/lampp/lampp startmysql
php artisan serve
```

Ouvrez ensuite http://127.0.0.1:8000.

## Tests et contrôles de qualité

```
php artisan test
./vendor/bin/pint
composer audit
```

Les tests utilisent une base temporaire en mémoire : ils ne touchent pas
à la base de développement.

## Mise en production

- `APP_ENV=production` et `APP_DEBUG=false` dans le fichier `.env`
- HTTPS obligatoire
- Le serveur web ne doit exposer que le dossier `public/`
- Un mot de passe fort et unique pour la base et pour chaque compte

## Documentation

Le cahier des charges et le document d'architecture logicielle
se trouvent dans le dossier [docs/](docs/).

## Organisation du dépôt

- `main` : version stable, protégée (modifiable uniquement par pull request)
- `develop` : version en cours d'assemblage
- `feature/...` : une branche par fonctionnalité, fusionnée dans `develop`

## Équipe

À compléter.
