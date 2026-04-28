<?php
/**
 * Connexion à la base de données Holidaze (MySQL)
 * - Crée l'objet PDO global $pdo
 * - Gestion d'erreur avec exceptions
 * - Utilisé partout dans l'app via require 'include/db.php'
 */
$host = 'localhost';  // Serveur MySQL
$db = 'holidaze';     // Base de données
$user = 'root';       // Utilisateur DB
$pass = '';           // Mot de passe (local dev)

$dsn = "mysql:host=$host;dbname=$db;charset=utf8";  // DSN PDO avec UTF-8

try {
    $pdo = new PDO($dsn, $user, $pass);  // Connexion PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);  // Active exceptions sur erreurs SQL
} catch (PDOException $e) {
    die('Erreur connexion DB: ' . $e->getMessage());  // Arrêt fatal si échec
}
?>