<?php
// Pages/Admin/bien_validation.php
session_start();

// Sécurité : seul l'admin peut valider
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Accès refusé');
}

// Chemins corrects depuis Pages/Admin/
require_once '../include/db.php';
require_once '../Pages/Bien/bien_class.php';

$controller = new BiensController($pdo);

$id_bien = isset($_POST['id_bien']) ? (int)$_POST['id_bien'] : 0;

if ($id_bien > 0) {
    // CETTE LIGNE CHANGE LA VALEUR DE 0 À 1
    $success = $controller->validateBien($id_bien);
    
    if ($success) {
        echo "success";
    } else {
        http_response_code(500);
        echo "error";
    }
} else {
    http_response_code(400);
    echo "invalid_id";
}
?>