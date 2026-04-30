# Holidaze

Application de location de vacances développée dans le cadre d'un BTS SIO.

## Prérequis

- Laragon (PHP 8.x, MySQL, Apache)
- Base de données : `holidaze`

## Installation

1. Cloner le projet dans le dossier `www` de Laragon
2. Créer la base de données avec le fichier `schema.sql` (à venir)
3. Copier `.env.example` en `.env` et renseigner les variables
4. Accéder via `http://holidaze.test`

## Variables d'environnement (`.env`)
APP_ENV=development
APP_DEBUG=true
DB_HOST=localhost
DB_NAME=holidaze
DB_USER=root
DB_PASS=

## Structure

- `Pages/` — vues PHP organisées par domaine
- `ajax/` — endpoints AJAX
- `include/` — db.php, csrf.php, smtp_mail.php
- `Photo/` — uploads et photos de profil
