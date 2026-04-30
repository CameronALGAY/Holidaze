<?php
/**
 * Protection CSRF — Holidaze
 *
 * Utilisation :
 *   Dans un formulaire HTML :  <?= csrf_field() ?>
 *   Au début d'un traitement : csrf_verify();
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Génère (ou récupère) le token CSRF de la session.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Retourne un champ hidden prêt à coller dans un <form>.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

/**
 * Vérifie le token envoyé.
 * En cas d'échec : répond 403 et arrête l'exécution.
 */
function csrf_verify(): void
{
    $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        error_log('CSRF validation failed. IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        die(json_encode(['success' => false, 'message' => 'Requête invalide (CSRF).']));
    }
}
