<?php
require_once '../../include/db.php';
require_once 'bien_class.php';
require_once '../Communes/communes_class.php';
require_once '../TypeBien/typebien_class.php';
require_once '../Tarifs/tarifs_class.php';

// Démarrer la session pour accéder à l'utilisateur connecté
session_start();

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$controller = new BiensController($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: bien_form.php');
    exit;
}

$action = $_POST['action'] ?? '';

// GESTION DE LA SUPPRESSION DE PHOTO (AJAX)
if ($action === 'delete_photo') {
    header('Content-Type: application/json');
    
    $id_photo = isset($_POST['id_photo']) ? (int)$_POST['id_photo'] : null;
    
    if (!$id_photo) {
        echo json_encode(['success' => false, 'message' => 'ID photo manquant']);
        exit;
    }
    
    try {
        // Récupérer les infos de la photo
        $stmt = $pdo->prepare("SELECT lien_photo FROM photo WHERE id_photo = ?");
        $stmt->execute([$id_photo]);
        $photo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$photo) {
            echo json_encode(['success' => false, 'message' => 'Photo non trouvée']);
            exit;
        }
        
        // Supprimer le fichier physique
        $filePath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . $photo['lien_photo'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
        
        // Supprimer l'entrée en base de données
        $stmt = $pdo->prepare("DELETE FROM photo WHERE id_photo = ?");
        $stmt->execute([$id_photo]);
        
        echo json_encode(['success' => true, 'message' => 'Photo supprimée avec succès']);
    } catch (Exception $e) {
        error_log("Erreur suppression photo: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
    }
    exit;
}

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

// Champs latitude et longitude
$latitude = isset($_POST['latitudeBien']) ? (float)$_POST['latitudeBien'] : null;
$longitude = isset($_POST['longitudeBien']) ? (float)$_POST['longitudeBien'] : null;

// Champs du sous-formulaire tarif
$semaine = isset($_POST['semaine_tarif']) ? trim($_POST['semaine_tarif']) : '';
$annee = isset($_POST['annee_tarif']) ? trim($_POST['annee_tarif']) : '';
$tarif_value = isset($_POST['tarif']) ? trim($_POST['tarif']) : '';
$id_saison = isset($_POST['id_saison']) && $_POST['id_saison'] !== '' ? (int)$_POST['id_saison'] : null;

// FONCTION UPLOAD PHOTO
function uploadPhoto($pdo, $id_bien) {
    // Choix du nom attendu : 'photos' (tableau) ou 'photo' (simple)
    if (!isset($_FILES['photos']) && !isset($_FILES['photo'])) {
        return true; // rien à faire
    }

    // Normaliser en tableau $files pour itérer
    $files = [];
    if (isset($_FILES['photos'])) {
        // structure: ['name'=>[], 'type'=>[], ...]
        $raw = $_FILES['photos'];
        // Si on a un seul fichier sans multiple, normaliser en tableau
        if (!is_array($raw['name'])) {
            $files[] = [
                'name' => $raw['name'],
                'type' => $raw['type'],
                'tmp_name' => $raw['tmp_name'],
                'error' => $raw['error'],
                'size' => $raw['size'],
            ];
        } else {
            for ($i = 0; $i < count($raw['name']); $i++) {
                $files[] = [
                    'name' => $raw['name'][$i],
                    'type' => $raw['type'][$i],
                    'tmp_name' => $raw['tmp_name'][$i],
                    'error' => $raw['error'][$i],
                    'size' => $raw['size'][$i],
                ];
            }
        }
    } else {
        // fallback single 'photo'
        $raw = $_FILES['photo'];
        $files[] = [
            'name' => $raw['name'],
            'type' => $raw['type'],
            'tmp_name' => $raw['tmp_name'],
            'error' => $raw['error'],
            'size' => $raw['size'],
        ];
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $maxSize = 5 * 1024 * 1024; // 5 Mo par fichier
    $uploadDir = 'Photo/uploads/'; // chemin relatif pour stockage (sans leading slash)

    // s'assurer que le dossier existe (utilise DOCUMENT_ROOT pour chemin absolu)
    $absUploadDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . $uploadDir;
    if (!is_dir($absUploadDir)) {
        if (!mkdir($absUploadDir, 0755, true)) {
            error_log("Impossible de créer dossier d'upload: $absUploadDir");
            return false;
        }
    }

    // Traiter chaque fichier
    foreach ($files as $f) {
        if ($f['error'] !== UPLOAD_ERR_OK) {
            // ignorer les emplacements vides ou journaliser l'erreur
            if ($f['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            error_log("Erreur upload fichier: code " . $f['error']);
            continue;
        }

        $fileName = basename($f['name']);
        $fileTmpPath = $f['tmp_name'];
        $fileSize = $f['size'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedExtensions)) {
            error_log("Type de fichier non autorisé: $fileExtension pour $fileName");
            continue;
        }
        if ($fileSize > $maxSize) {
            error_log("Fichier trop volumineux: $fileName taille=$fileSize");
            continue;
        }

        // Nouveau nom unique
        $newFileName = uniqid('bien_', true) . '.' . $fileExtension;
        $destPath = $absUploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            // Stocker le chemin relatif en base pour affichage via <img src="...">
            $lienPhoto = $uploadDir . $newFileName;

            try {
                $sql = "INSERT INTO photo (nom_photo, lien_photo, id_bien) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([substr($fileName, 0, 50), $lienPhoto, $id_bien]);
            } catch (Exception $e) {
                error_log("Erreur insertion photo en base: " . $e->getMessage());
                // Option: supprimer fichier si insertion échoue
                @unlink($destPath);
            }
        } else {
            error_log("Echec move_uploaded_file pour $fileName vers $destPath");
        }
    }

    return true;
}

// FONCTION POUR METTRE À JOUR LE RÔLE DE L'UTILISATEUR EN LOCATAIRE
function updateUserToLocataire($pdo, $userId) {
    try {
        // Vérifier le rôle actuel
        $stmt = $pdo->prepare("SELECT role FROM utilisateurs WHERE id_utilisateur = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si l'utilisateur n'est pas déjà locataire ou admin, le passer en locataire
        if ($user && $user['role'] !== 'propriétaire' && $user['role'] !== 'admin') {
            $stmt = $pdo->prepare("UPDATE utilisateurs SET role = 'propriétaire' WHERE id_utilisateur = ?");
            $stmt->execute([$userId]);
            
            // Mettre à jour la session
            $_SESSION['role'] = 'propriétaire';
            
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Erreur mise à jour rôle propriétaire: " . $e->getMessage());
        return false;
    }
}

// ========== NOUVELLE FONCTION POUR LES PRESTATIONS ==========
function savePrestations($pdo, $id_bien, $prestations) {
    try {
        // Supprimer les anciennes associations
        $stmt = $pdo->prepare("DELETE FROM secompose WHERE id_bien = ?");
        $stmt->execute([$id_bien]);
        
        // Ajouter les nouvelles associations
        if (!empty($prestations)) {
            $stmt = $pdo->prepare("INSERT INTO secompose (id_bien, id_prestation, quantite) VALUES (?, ?, ?)");
            foreach ($prestations as $id_prestation => $quantite) {
                if ($quantite > 0) {
                    $stmt->execute([$id_bien, $id_prestation, $quantite]);
                }
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Erreur sauvegarde prestations: " . $e->getMessage());
        return false;
    }
}
// ========== FIN NOUVELLE FONCTION ==========

try {
    if ($action === 'create') {
        // Vérifier que l'utilisateur est connecté
        if (!isset($_SESSION['utilisateur_id'])) {
            header('Location: ../Formulaires/connexion.php');
            exit;
        }
        
        $pdo->beginTransaction();

        $newIdBien = $controller->createBien($nom, $description, $rue, $com, $superficie, $animaux, $nbCouchages, $id_commune, $id_typebien, $latitude, $longitude);

        if (!$newIdBien) {
            $pdo->rollBack();
            header('Location: bien_form.php?success=0&error=' . urlencode('Erreur création bien'));
            exit;
        }

        // Après la ligne qui définit $newIdBien
        if ($newIdBien && isset($_SESSION['utilisateur_id'])) {
            // Associer le bien au propriétaire
            $stmt = $pdo->prepare("UPDATE bien SET id_utilisateur_proprietaire = ? WHERE id_bien = ?");
            $stmt->execute([$_SESSION['utilisateur_id'], $newIdBien]);
        }

        // Upload photo si fournie
        uploadPhoto($pdo, $newIdBien);

        // ========== AJOUT : Gérer les prestations ==========
        $prestations = $_POST['prestations'] ?? [];
        savePrestations($pdo, $newIdBien, $prestations);
        // ========== FIN AJOUT ==========

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

        // Mettre à jour le rôle de l'utilisateur en locataire
        updateUserToLocataire($pdo, $_SESSION['utilisateur_id']);

        $pdo->commit();
        header('Location: bien_form.php?success=1&role_updated=1');
        exit;

    } elseif ($action === 'update') {
        if (!$id_bien) {
            header('Location: bien_form.php?success=0&error=' . urlencode('ID bien manquant'));
            exit;
        }

        $pdo->beginTransaction();

        $ok = $controller->updateBien($id_bien, $nom, $description, $rue, $com, $superficie, $animaux, $nbCouchages, $id_commune, $id_typebien, $latitude, $longitude);
        if (!$ok) {
            $pdo->rollBack();
            header('Location: bien_form.php?success=0&error=' . urlencode('Erreur update bien'));
            exit;
        }

        // Upload nouvelle photo si fournie
        uploadPhoto($pdo, $id_bien);

        // ========== AJOUT : Gérer les prestations ==========
        $prestations = $_POST['prestations'] ?? [];
        savePrestations($pdo, $id_bien, $prestations);
        // ========== FIN AJOUT ==========

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