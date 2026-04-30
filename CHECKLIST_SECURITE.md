# Checklist sécurité Holidaze

## Fichiers à créer (faits)
- [x] `.env` à la racine
- [x] `.gitignore` (exclut .env et fichiers de test)
- [x] `include/csrf.php` (helper CSRF)

## Fichiers à modifier

### include/db.php
- [x] Lire credentials depuis .env
- [x] display_errors = 0 en production

### Pages/Formulaires/connexion.php
- [x] session_regenerate_id(true) après connexion réussie
- [x] csrf_verify() en début de POST
- [x] <?= csrf_field() ?> dans le formulaire

### À faire — même traitement CSRF sur ces fichiers :
- [x] Pages/Formulaires/inscription.php + inscription_traitement.php
- [x] Pages/Bien/bien_traitement.php
- [x] Pages/Profil/profil.php
- [x] Pages/Contact/contact.php
- [x] Pages/Contact/contact_reply.php
- [x] Pages/Réservations/reservation_traitement.php
- [x] Pages/Saison/saison_form.php
- [x] Pages/Prestations/prestation_form.php
- [x] Pages/Locataire/locataire_form.php
- [x] Pages/Intervenants/intervenants_form.php
- [x] Pages/Menages/menages_form.php
- [x] Pages/Admin/marquer_lu.php

### Fichiers de debug à SUPPRIMER
- [x] test_smtp.php (racine)
- [x] Pages/Réservations/test_locataire.php

### Dossier logs à créer (pour error_log)
- [ ] mkdir logs/ à la racine + touch logs/.gitkeep
- [ ] Ajouter logs/*.log dans .gitignore

## Pattern CSRF dans un formulaire HTML
```html
<form method="POST" action="traitement.php">
    <?= csrf_field() ?>
    <!-- ... autres champs ... -->
</form>
```

## Pattern CSRF dans un traitement PHP
```php
session_start();
require_once '../../include/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    // ... traitement ...
}
```

## Pattern CSRF dans un appel AJAX (fetch)
```js
// Récupérer le token depuis une balise meta dans le HTML :
// <meta name="csrf-token" content="<?= csrf_token() ?>">
const token = document.querySelector('meta[name="csrf-token"]').content;

fetch('traitement.php', {
    method: 'POST',
    headers: { 'X-CSRF-Token': token },
    body: formData
});
```
```php
// Côté PHP, csrf_verify() accepte aussi le header X-CSRF-Token automatiquement.
```
