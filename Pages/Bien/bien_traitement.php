<?php
require_once '../../include/db.php';
require_once 'bien_class.php';
require_once '../Communes/communes_class.php';
require_once '../TypeBien/typebien_class.php';
require_once '../Tarifs/tarifs_class.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$controller = new BiensController($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: bien_form.php');
    exit;
}

$action = $_POST['action'] ?? '';

// Récupération sécurisée des champs du bien
$nom = isset($_POST['nomBien']) ? trim($_POST['nomBien']) : null;
$description = isset($_POST['descriptionBien']) ? trim($_POST['descriptionBien']) : null;
$rue = isset($_POST['rueBien']) ? trim($_POST['rueBien']) : null;
$com = isset($_POST['compBien']) ? trim($_POST['compBien']) : '';
$superficie = isset($_POST['superficieBien']) ? trim($_POST['superficieBien']) : null;
$animaux = isset($_POST['animauxBien']) ? (int)$_POST['animauxBien'] : 0;
$nbCouchages = isset($_POST['nbCouchagesBien']) ? trim($_POST['nbCouchagesBien']) : null;
$id_commune = isset($_POST['communeIdInput']) && $_POST['communeIdInput'] !== '' ? (int)$_POST['communeIdInput'] : null;
$id_typebien = isset($_POST['typebienIdInput']) && $_POST['typebienIdInput'] !== '' ? (int)$_POST['typebienIdInput'] : null;
$id_bien = isset($_POST['id_bien']) && $_POST['id_bien'] !== '' ? (int)$_POST['id_bien'] : null;

// Champs du sous-formulaire tarif
$semaine = isset($_POST['semaine_tarif']) ? trim($_POST['semaine_tarif']) : '';
$annee = isset($_POST['annee_tarif']) ? trim($_POST['annee_tarif']) : '';
$tarif_value = isset($_POST['tarif']) ? trim($_POST['tarif']) : '';
$id_saison = isset($_POST['id_saison']) && $_POST['id_saison'] !== '' ? (int)$_POST['id_saison'] : null;

// FONCTION UPLOAD PHOTO
function uploadPhoto($pdo, $id_bien) {
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        return true; // Pas d'erreur si pas de photo
    }

    $fileTmpPath = $_FILES['photo']['tmp_name'];
    $fileName = basename($_FILES['photo']['name']);
    $fileSize = $_FILES['photo']['size'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $maxSize = 5 * 1024 * 1024; // 5 Mo

    if (!in_array($fileExtension, $allowedExtensions)) {
        error_log("Type de fichier non autorisé: $fileExtension");
        return false;
    }

    if ($fileSize > $maxSize) {
        error_log("Fichier trop volumineux: $fileSize > $maxSize");
        return false;
    }

    $uploadDir = 'Photo/uploads/';
    if (!is_dir($_SERVER['DOCUMENT_ROOT'] . '/' . $uploadDir)) {
        mkdir($_SERVER['DOCUMENT_ROOT'] . '/' . $uploadDir, 0755, true);
    }

    $newFileName = uniqid('bien_', true) . '.' . $fileExtension;
    $destPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $uploadDir . $newFileName;

    if (move_uploaded_file($fileTmpPath, $destPath)) {
        $lienPhoto = $uploadDir . $newFileName;
        $sql = "INSERT INTO photo (nom_photo, lien_photo, id_bien) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([substr($fileName, 0, 50), $lienPhoto, $id_bien]);
    }
    return false;
}

try {
    if ($action === 'create') {
        $pdo->beginTransaction();

        $newIdBien = $controller->createBien($nom, $description, $rue, $com, $superficie, $animaux, $nbCouchages, $id_commune, $id_typebien);

        if (!$newIdBien) {
            $pdo->rollBack();
            header('Location: bien_form.php?success=0&error=' . urlencode('Erreur création bien'));
            exit;
        }

        // Upload photo si fournie
        uploadPhoto($pdo, $newIdBien);

        // Insertion tarif si renseigné
        $tarifRenseigné = ($semaine !== '' || $annee !== '' || $tarif_value !== '');
        if ($tarifRenseigné && $id_saison) {
            $tarifObj = new Tarifs(
                $pdo,
                null,
                $semaine !== '' ? (string)$semaine : '',
                $annee !== '' ? (string)$annee : '',
                $tarif_value !== '' ? (string)$tarif_value : '',
                $newIdBien,
                $id_saison
            );
            $tarifObj->create();
        }

        $pdo->commit();
        header('Location: bien_form.php?success=1');
        exit;

    } elseif ($action === 'update') {
        if (!$id_bien) {
            header('Location: bien_form.php?success=0&error=' . urlencode('ID bien manquant'));
            exit;
        }

        $pdo->beginTransaction();

        $ok = $controller->updateBien($id_bien, $nom, $description, $rue, $com, $superficie, $animaux, $nbCouchages, $id_commune, $id_typebien);
        if (!$ok) {
            $pdo->rollBack();
            header('Location: bien_form.php?success=0&error=' . urlencode('Erreur update bien'));
            exit;
        }

        // Upload nouvelle photo si fournie
        uploadPhoto($pdo, $id_bien);

        // Nouveau tarif si renseigné
        $tarifRenseigné = ($semaine !== '' || $annee !== '' || $tarif_value !== '');
        if ($tarifRenseigné && $id_saison) {
            $tarifObj = new Tarifs(
                $pdo,
                null,
                $semaine !== '' ? (string)$semaine : '',
                $annee !== '' ? (string)$annee : '',
                $tarif_value !== '' ? (string)$tarif_value : '',
                $id_bien,
                $id_saison
            );
            $tarifObj->create();
        }

        $pdo->commit();
        header('Location: bien_form.php?success=1');
        exit;

    } elseif ($action === 'delete') {
        $id_to_delete = isset($_POST['id_bien']) ? (int)$_POST['id_bien'] : null;
        if ($id_to_delete) {
            $res = $controller->deleteBien($id_to_delete);
            echo json_encode(['success' => (bool)$res]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID manquant']);
        }
        exit;
    } else {
        header('Location: bien_form.php?success=0&error=' . urlencode('Action non reconnue'));
        exit;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("bien_traitement.php Exception: " . $e->getMessage());
    header('Location: bien_form.php?success=0&error=' . urlencode($e->getMessage()));
    exit;
}
?>