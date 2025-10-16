<?php
require_once '../../include/db.php';
require_once 'bien_class.php';
require_once '../Communes/communes_class.php';
require_once '../TypeBien/typebien_class.php';

header('Content-Type: application/json');

// Vérification de la connexion
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base']);
    exit;
}

// Instanciation du contrôleur
$controller = new BiensController($pdo);

// Fonction pour uploader la photo
// Fonction pour uploader la photo
// Fonction pour uploader la photo
function uploadPhoto($pdo, $id_bien) {
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $fileTmpPath = $_FILES['photo']['tmp_name'];
    $fileName = basename($_FILES['photo']['name']);
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($fileExtension, $allowedExtensions)) {
        return false;
    }

    $newFileName = uniqid('img_', true) . '.' . $fileExtension;
    $uploadDir = '../Photo/uploads/'; // Chemin basé sur /Photo/uploads/ dans la racine
    $destPath = $_SERVER['DOCUMENT_ROOT'] . $uploadDir . $newFileName;

    if (!is_dir($_SERVER['DOCUMENT_ROOT'] . $uploadDir)) {
        mkdir($_SERVER['DOCUMENT_ROOT'] . $uploadDir, 0755, true);
    }

    if (move_uploaded_file($fileTmpPath, $destPath)) {
        $lienPhoto = $uploadDir . $newFileName; // Chemin relatif à la racine : /Photo/uploads/...
        $sql = "INSERT INTO photo (nom_photo, lien_photo, id_bien) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([substr($fileName, 0, 50), $lienPhoto, $id_bien]);
        return true;
    }
    return false;
}

// --- Gestion des requêtes GET pour l'API ---
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'getAll':
            echo json_encode(['success' => true, 'data' => $controller->getAllBiens()]);
            exit;
        case 'getById':
            $id = $_GET['id'] ?? 0;
            $bien = $controller->getBienById($id);
            echo json_encode(['success' => $bien !== false, 'bien' => $bien]);
            exit;
        case 'search':
            $search = $_GET['search'] ?? '';
            echo json_encode(['success' => true, 'data' => $controller->searchBiens($search)]);
            exit;
        default:
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
            exit;
    }
}

// --- Gestion des requêtes POST pour CRUD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $action = $_POST['action'] ?? '';
    $nom = $_POST['nomBien'] ?? null;
    $description = $_POST['descriptionBien'] ?? null;
    $rue = $_POST['rueBien'] ?? null;
    $com = $_POST['compBien'] ?? '';
    $superficie = $_POST['superficieBien'] ?? null;
    $animaux = $_POST['animauxBien'] ?? 0;
    $nbCouchages = $_POST['nbCouchagesBien'] ?? null;
    $id_commune = $_POST['communeIdInput'] ?? null;
    $id_typebien = $_POST['typebienIdInput'] ?? null;
    $id_bien = $_POST['editId'] ?? $_POST['id_bien'] ?? null;

    try {
        switch ($action) {
            case 'create':
                $result = $controller->createBien($nom, $description, $rue, $com, $superficie, $animaux, $nbCouchages, $id_commune, $id_typebien);
                if ($result) {
                    $id_bien = $pdo->lastInsertId();
                    uploadPhoto($pdo, $id_bien);
                }
                header('Location: bien_form.php?success=' . ($result ? '1' : '0'));
                exit;
            case 'update':
                if ($id_bien) {
                    $result = $controller->updateBien($id_bien, $nom, $description, $rue, $com, $superficie, $animaux, $nbCouchages, $id_commune, $id_typebien);
                    if ($result) {
                        uploadPhoto($pdo, $id_bien);
                    }
                    header('Location: bien_form.php?success=' . ($result ? '1' : '0'));
                    exit;
                }
                echo json_encode(['success' => false, 'message' => 'ID manquant pour la mise à jour']);
                exit;
            case 'delete':
                if ($id_bien) {
                    $result = $controller->deleteBien($id_bien);
                    echo json_encode(['success' => $result]);
                    exit;
                }
                echo json_encode(['success' => false, 'message' => 'ID manquant pour la suppression']);
                exit;
            default:
                echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
                exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        exit;
    }
}
?>