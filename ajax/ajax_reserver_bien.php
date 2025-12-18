<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['utilisateur_id'])) {
    echo json_encode(['success' => false, 'message' => 'Connectez-vous']);
    exit;
}

// === VÉRIFICATION DE LA MAJORITÉ (18 ans) ===
// On suppose que la date de naissance est stockée dans $_SESSION['utilisateur']['date_naissance']
if (isset($_SESSION['utilisateur']['date_naissance']) && !empty($_SESSION['utilisateur']['date_naissance'])) {
    try {
        $date_naissance = new DateTime($_SESSION['utilisateur']['date_naissance']);
        $aujourdhui = new DateTime();
        $age = $aujourdhui->diff($date_naissance)->y;

        if ($age < 18) {
            echo json_encode(['success' => false, 'message' => 'Vous devez être majeur pour pouvoir réserver un logement.']);
            exit;
        }
    } catch (Exception $e) {
        // Si la date de naissance est mal formatée, on laisse passer mais on pourrait aussi bloquer
        // Pour l'instant, on ne bloque pas si la date est mal formatée, mais on pourrait le faire.
    }
} else {
    // Si la date de naissance n'est pas renseignée, on bloque la réservation pour garantir la majorité.
    echo json_encode(['success' => false, 'message' => 'Votre date de naissance n\'est pas renseignée. Veuillez mettre à jour votre profil pour confirmer votre majorité.']);
    exit;
}
// ==========================================

if (!isset($_POST['id_bien']) || !isset($_POST['date_debut']) || !isset($_POST['date_fin']) || !isset($_POST['nb_personnes'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$id_bien      = (int)$_POST['id_bien'];
$date_debut   = $_POST['date_debut'];
$date_fin     = date('Y-m-d', strtotime($_POST['date_fin'] . ' -1 day'));
$personnes    = (int)$_POST['nb_personnes'];
$id_locataire = $_SESSION['utilisateur_id'];

// === CORRECTION ERREUR CLE ETRANGERE (locataire) ===
try {
    // Vérifier si le locataire existe déjà
    $checkLocataire = $pdo->prepare("SELECT id_locataire FROM locataire WHERE id_locataire = ?");
    $checkLocataire->execute([$id_locataire]);
    
    // Si le locataire n'existe pas, l'insérer avec les données de la session
    if (!$checkLocataire->fetch()) {
        // Récupérer un id_commune valide (le premier de la table si non renseigné)
        $id_commune = $_SESSION['utilisateur']['id_commune'] ?? null;
        
        if (!$id_commune) {
            $communeStmt = $pdo->query("SELECT id_commune FROM commune LIMIT 1");
            $id_commune = $communeStmt->fetchColumn();
            
            if (!$id_commune) {
                echo json_encode(['success' => false, 'message' => 'Aucune commune disponible dans la base de données.']);
                exit;
            }
        }
        
        $insertLocataire = $pdo->prepare("
            INSERT INTO locataire 
            (id_locataire, id_utilisateur, nom_locataire, prenom_locataire, dna_locataire, 
             email_locataire, rue_locataire, comp_locataire, id_commune, raison_sociale, pass_locataire, tel_locataire, siret) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $insertLocataire->execute([
            $id_locataire,
            $id_locataire, // id_utilisateur = id_locataire
            $_SESSION['utilisateur']['nom'] ?? '',
            $_SESSION['utilisateur']['prenom'] ?? '',
            $_SESSION['utilisateur']['date_naissance'] ?? null,
            $_SESSION['utilisateur']['email'] ?? '',
            $_SESSION['utilisateur']['rue'] ?? '', // Chaîne vide au lieu de null
            $_SESSION['utilisateur']['complement'] ?? '',
            $id_commune, // ID de commune valide
            '', // raison_sociale vide pour les particuliers
            $_SESSION['utilisateur']['password'] ?? '', // Mot de passe de l'utilisateur
            $_SESSION['utilisateur']['telephone'] ?? '', // Téléphone de l'utilisateur
            '' // siret vide pour les particuliers
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la création du locataire: ' . $e->getMessage()]);
    exit;
}
// ===================================================

if (strtotime($date_debut) >= strtotime($date_fin)) {
    echo json_encode(['success' => false, 'message' => 'Dates invalides']);
    exit;
}

try {
    // Vérifier le bien
    $stmt = $pdo->prepare("SELECT nb_couchage FROM bien WHERE id_bien = ?");
    $stmt->execute([$id_bien]);
    $nb_couchage = $stmt->fetchColumn();
    
    if (!$nb_couchage) {
        echo json_encode(['success' => false, 'message' => 'Bien introuvable']);
        exit;
    }
    
    if ($personnes > $nb_couchage) {
        echo json_encode(['success' => false, 'message' => 'Trop de personnes']);
        exit;
    }

    // Vérifier chevauchement
    $check = $pdo->prepare("SELECT 1 FROM reservation 
        WHERE id_bien = ? 
        AND date_debut < ? AND date_fin > ?");
    $check->execute([$id_bien, $date_fin, $date_debut]);
    
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Dates déjà réservées']);
        exit;
    }

    // Calculer la semaine et l'année de la date de début
    $semaine = (int)date('W', strtotime($date_debut));
    $annee = (int)date('Y', strtotime($date_debut));

    // Récupérer le tarif correspondant au bien, à la semaine et à l'année
    $tarifStmt = $pdo->prepare("
        SELECT id_tarif 
        FROM tarif 
        WHERE id_bien = ? 
        AND semaine_tarif = ? 
        AND annee_tarif = ?
        LIMIT 1
    ");
    $tarifStmt->execute([$id_bien, $semaine, $annee]);
    $id_tarif = $tarifStmt->fetchColumn();
    
    // Si pas de tarif trouvé pour cette semaine précise, prendre le premier tarif du bien
    if (!$id_tarif) {
        $tarifStmt = $pdo->prepare("
            SELECT id_tarif 
            FROM tarif 
            WHERE id_bien = ? 
            AND annee_tarif = ?
            LIMIT 1
        ");
        $tarifStmt->execute([$id_bien, $annee]);
        $id_tarif = $tarifStmt->fetchColumn();
    }
    
    // Si toujours pas de tarif, utiliser n'importe quel tarif du bien
    if (!$id_tarif) {
        $tarifStmt = $pdo->prepare("SELECT id_tarif FROM tarif WHERE id_bien = ? LIMIT 1");
        $tarifStmt->execute([$id_bien]);
        $id_tarif = $tarifStmt->fetchColumn();
    }
    
    if (!$id_tarif) {
        echo json_encode(['success' => false, 'message' => 'Aucun tarif disponible pour ce bien']);
        exit;
    }

    // Insertion avec le tarif trouvé
    $insert = $pdo->prepare("INSERT INTO reservation 
        (date_debut, date_fin, id_locataire, id_bien, id_tarif) 
        VALUES (?, ?, ?, ?, ?)");

    if ($insert->execute([$date_debut, $date_fin, $id_locataire, $id_bien, $id_tarif])) {
        echo json_encode(['success' => true, 'message' => 'Réservation confirmée']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'insertion']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}