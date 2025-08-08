# User Manager - Application de Gestion des Utilisateurs

## Présentation du Projet

User Manager est une application web full-stack développée pour la gestion des utilisateurs avec authentification sécurisée, système de rôles et interface d'administration complète. L'application permet de créer, modifier, supprimer et consulter les utilisateurs avec des permissions différenciées selon les rôles.

## Objectifs Réalisés

• Interface d'authentification sécurisée avec JWT
• CRUD complet des utilisateurs
• Système de rôles (admin/user) avec permissions
• Pagination et recherche par nom/email
• Upload de photo de profil
• Filtrage dynamique et tri dans le tableau
• Système de logs pour l'historique des actions
• Tests unitaires avec Pest
• Interface responsive avec PrimeVue
• Sécurisation des tokens JWT via HTTP-only cookies

## Liens du Projet

**Application en ligne** : https://user-manager-front.vercel.app
• Email: admin@nomail.com ou user@nomail.com
• Mot de passe : admin

**Dépôts GitHub**
• Backend (API Laravel) : https://github.com/kjlinux/user-manager-back
• Frontend (Vue.js) : https://github.com/kjlinux/user-manager-front

## Installation et Lancement du Projet

### Prérequis Système

• Apache : Version 2.4 minimum
• PHP : Version 8.2 minimum
• PostgreSQL : Version récente
• Node.js : Version LTS récente
• Git : Pour le clonage des dépôts

### Option 1 : Installation avec Laragon (Recommandée)

#### 1. Installation de Laragon

1. Télécharger et installer Laragon
2. Clic droit sur l'icône Laragon dans la barre des tâches
3. Apache → Version → Sélectionner la version la plus récente
4. PHP → Version → Sélectionner la version la plus récente
5. Clic droit → Outils → Quick Add → PostgreSQL
6. Arrêter et redémarrer Laragon (autoriser les permissions Windows si demandées)

#### 2. Récupération du Code Source

```bash
# Cloner les dépôts ou télécharger en ZIP
git clone https://github.com/kjlinux/user-manager-back.git
git clone https://github.com/kjlinux/user-manager-front.git
```

Placer les deux dossiers dans le répertoire `C:\laragon\www\`

#### 3. Configuration de la Base de Données

Après l'installation de PostgreSQL, il est nécessaire de créer la base de données :

1. Télécharger un client PostgreSQL :
   • pgAdmin 4 (interface graphique officielle)
   • DBeaver (client universel gratuit)
   • Ou tout autre client PostgreSQL

2. Créer la base de données :
   • Se connecter à PostgreSQL via le client choisi
   • Créer une nouvelle base de données nommée `user_manager`
   • Définir un utilisateur avec les permissions appropriées (ou utiliser postgres)

#### 4. Configuration du Backend (user-manager-back)

Ouvrir un terminal dans le dossier `user-manager-back` :

```bash
# Installation des dépendances Composer
composer install

# Création du fichier de configuration
cp .env.example .env
```

Modifier le fichier `.env` avec les configurations suivantes :

```env
APP_NAME=user-manager-api
APP_LOCALE=fr
APP_FALLBACK_LOCALE=fr

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=user_manager
DB_USERNAME=votre_nom_utilisateur
DB_PASSWORD=votre_mot_de_passe
```

```bash
# Génération des clés de sécurité
php artisan key:generate
php artisan jwt:secret

# Migration et seeding de la base de données
php artisan migrate:fresh --seed

# Exécution des tests
php artisan test

# Lancement du serveur de développement
php artisan serve
```

#### 5. Configuration du Frontend (user-manager-front)

Installer PNPM globalement si ce n'est pas déjà fait :

```bash
npm install -g pnpm
```

Ouvrir un terminal dans le dossier `user-manager-front` :

```bash
# Installation des dépendances
pnpm install

# Lancement du serveur de développement
pnpm dev
```

#### 6. Accès à l'Application

1. Se rendre sur l'adresse indiquée par le terminal (généralement localhost:5173)
2. Se connecter avec les identifiants par défaut créés par les seeders

### Option 2 : Installation avec WAMP/XAMPP

Si vous utilisez WAMP ou XAMPP, assurez-vous d'avoir :
• Apache 2.4 minimum
• PHP 8.2 minimum
• PostgreSQL installé séparément

Suivez ensuite les mêmes étapes de configuration que pour Laragon, en adaptant les chemins vers votre environnement local.

## Stack Technique Utilisée

### Architecture Générale

L'application suit une architecture découplée avec une API REST backend et une application frontend monopage (SPA).

### Backend - API REST

**Framework Principal** : Laravel
• Système d'authentification JWT avec cookies HTTP-only
• Migrations et seeders pour la gestion de la base de données

**Base de Données** : PostgreSQL

**Authentification** : JWT (JSON Web Tokens)
• Stockage sécurisé via HTTP-only cookies

**Packages Utilisés** :
• Tymon JWT-Auth
• Spatie Laravel Permission
• Pest (Installé nativement dans Laravel)

### Frontend - Application Web

**Framework Principal** : Vue.js 3

**Gestionnaire d'État** : Pinia

**Interface Utilisateur** : PrimeVue

**Gestionnaire de Paquets** : PNPM

## Structure du Code

### Architecture Backend (user-manager-back)

```
user-manager-back/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── UserController.php
│   │   └── Middleware/
│   │       └── JWTFromCookie.php
│   └── Models/
│       ├── User.php
│       ├── Log.php
│       └── Media.php
├── database/
│   ├── migrations/
│   └── seeders/
│       └── UserSeeder.php
└── tests/
```

**Composants Principaux** :
• **UserController.php** : Gère toutes les opérations CRUD des utilisateurs
• **JWTFromCookie.php** : Middleware pour extraire le token JWT des cookies
• **Models** : User, Log et Media pour la gestion des données
• **Seeders** : Génération de données de test
• **Tests** : Tests unitaires avec Pest (exécutables via `php artisan test`)

### Architecture Frontend (user-manager-front)

```
user-manager-front/
├── src/
│   ├── services/
│   │   └── authService.js
│   ├── stores/
│   │   └── auth.js
│   ├── directives/
│   │   └── roleDirective.js
│   └── views/
│       └── [vues principales]
└──
```

**Composants Principaux** :
• **authService.js** : Service d'authentification et appels API
• **auth.js** : Store Pinia pour la gestion de l'état d'authentification
• **roleDirective.js** : Directive Vue pour l'affichage conditionnel selon les rôles
• **Views** : Vues principales de l'application

## Fonctionnalités

### Authentification

• Connexion sécurisée avec email/mot de passe
• Tokens JWT stockés dans des cookies HTTP-only
• Déconnexion automatique à l'expiration du token

### Gestion des Utilisateurs

• Liste paginée des utilisateurs
• Recherche par nom et email
• Création, modification et suppression d'utilisateurs
• Upload de photos de profil
• Filtrage et tri dynamiques
• Changement de mot de passe
• Activation/Désactivation d'un utilisateur
• Suppression multiple des utilisateurs

### Système de Rôles

• Rôle Administrateur : accès complet (CRUD)
• Rôle Utilisateur : lecture seule
• Affichage conditionnel des fonctionnalités selon les rôles

### Système de Logs

• Historique des actions utilisateurs
• Traçabilité des modifications

### Tests

• Tests unitaires backend avec Pest
• Exécution via `php artisan test`
• Modifier les variables de base de données dans phpunit.xml pour la base de données qui sera utilisée pour les tests
