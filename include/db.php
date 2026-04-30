<?php
/**
 * Connexion PDO — Holidaze
 * Lit les credentials depuis .env à la racine du projet.
 * Ne jamais hardcoder de mot de passe dans ce fichier.
 */

// --- Chargement du .env (parser minimal, pas de dépendance externe) ---
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// --- Configuration error reporting selon l'environnement ---
$appEnv   = $_ENV['APP_ENV']   ?? 'production';
$appDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';

if ($appEnv === 'development' && $appDebug) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');   // JAMAIS afficher les erreurs en prod
    error_reporting(E_ALL);           // On les logue quand même
    ini_set('log_errors', '1');
    ini_set('error_log', dirname(__DIR__) . '/logs/php_errors.log');
}

// --- Connexion PDO ---
$host    = $_ENV['DB_HOST'] ?? 'localhost';
$db      = $_ENV['DB_NAME'] ?? 'holidaze';
$user    = $_ENV['DB_USER'] ?? 'root';
$pass    = $_ENV['DB_PASS'] ?? '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // On logue l'erreur réelle mais on n'expose rien à l'utilisateur
    error_log('DB connection error: ' . $e->getMessage());
    http_response_code(503);
    die('Service temporairement indisponible. Veuillez réessayer plus tard.');
}
