<?php
// favoris_action.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../include/db.php';
require_once __DIR__ . '/Favoris/favoris_class.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user'])) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$idUser = $_SESSION['id_user'];
$favorisController = new FavorisController($pdo);

// Récupérer l'action
$action = $_POST['action'] ?? '';
$idBien = (int)($_POST['id_bien'] ?? 0);

if ($idBien <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID bien invalide']);
    exit;
}

switch ($action) {
    case 'ajouter':
        $result = $favorisController->ajouterFavori($idUser, $idBien);
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Ajouté aux favoris' : 'Erreur lors de l\'ajout'
        ]);
        break;

    case 'retirer':
        $result = $favorisController->retirerFavori($idUser, $idBien);
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Retiré des favoris' : 'Erreur lors du retrait'
        ]);
        break;

    case 'toggle':
        // Toggle : ajouter si pas en favori, retirer sinon
        $estEnFavori = $favorisController->estEnFavori($idUser, $idBien);
        
        if ($estEnFavori) {
            $result = $favorisController->retirerFavori($idUser, $idBien);
            $message = 'Retiré des favoris';
            $estEnFavori = false;
        } else {
            $result = $favorisController->ajouterFavori($idUser, $idBien);
            $message = 'Ajouté aux favoris';
            $estEnFavori = true;
        }
        
        echo json_encode([
            'success' => $result,
            'message' => $message,
            'estEnFavori' => $estEnFavori
        ]);
        break;

    case 'verifier':
        $estEnFavori = $favorisController->estEnFavori($idUser, $idBien);
        echo json_encode([
            'success' => true,
            'estEnFavori' => $estEnFavori
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action invalide']);
        break;
}