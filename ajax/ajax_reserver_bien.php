<?php
/**
 * AJAX Réserver un bien — Holidaze
 * CORRECTIONS :
 *  - Suppression du "locataire fantôme" : on ne crée plus un locataire vide
 *  - La réservation utilise directement id_utilisateur comme id_locataire
 *    (la table reservation.id_locataire peut référencer utilisateurs.id_utilisateur
 *     OU on s'assure qu'une ligne locataire existe via upsert propre)
 *  - Si la contrainte FK impose une ligne dans locataire, on fait un INSERT IGNORE
 *    avec les vraies données session (pas des chaînes vides)
 *  - header JSON dès le début, ini display_errors = 0
 */
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/db.php';

// --- Auth ---
if (!isset($_SESSION['utilisateur_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Connectez-vous pour réserver']);
    exit;
}

$idUtilisateur = (int)$_SESSION['utilisateur_id'];

// --- Vérification de l'âge (majorité) ---
$dateNaissance = $_SESSION['utilisateur']['date_naissance'] ?? null;

if (empty($dateNaissance)) {
    echo json_encode([
        'success' => false,
        'message' => 'Votre date de naissance n\'est pas renseignée. Mettez à jour votre profil pour confirmer votre majorité.',
    ]);
    exit;
}

try {
    $naissance  = new DateTime($dateNaissance);
    $aujourdhui = new DateTime();
    $age        = $aujourdhui->diff($naissance)->y;

    if ($age < 18) {
        echo json_encode(['success' => false, 'message' => 'Vous devez être majeur (18 ans) pour réserver.']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Date de naissance invalide dans votre profil.']);
    exit;
}

// --- Validation des champs POST ---
$idBien    = filter_input(INPUT_POST, 'id_bien',      FILTER_VALIDATE_INT);
$dateDebut = trim($_POST['date_debut'] ?? '');
$dateFin   = trim($_POST['date_fin']   ?? '');
$personnes = filter_input(INPUT_POST, 'nb_personnes', FILTER_VALIDATE_INT);
$idTarif   = filter_input(INPUT_POST, 'id_tarif',     FILTER_VALIDATE_INT);

if (!$idBien || !$dateDebut || !$dateFin || !$personnes) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes ou invalides']);
    exit;
}

// FullCalendar envoie date_fin exclusive → on retire 1 jour pour la stocker
$dateFinStockee = date('Y-m-d', strtotime($dateFin . ' -1 day'));

if (strtotime($dateDebut) >= strtotime($dateFinStockee)) {
    echo json_encode(['success' => false, 'message' => 'Les dates sont invalides (début ≥ fin)']);
    exit;
}

try {
    // --- Vérifier le bien et la capacité ---
    $stmt = $pdo->prepare("SELECT nb_couchage FROM bien WHERE id_bien = ? AND valide = 1");
    $stmt->execute([$idBien]);
    $nbCouchage = $stmt->fetchColumn();

    if ($nbCouchage === false) {
        echo json_encode(['success' => false, 'message' => 'Bien introuvable ou non disponible']);
        exit;
    }
    if ($personnes > $nbCouchage) {
        echo json_encode(['success' => false, 'message' => "Trop de personnes (max {$nbCouchage} pour ce bien)"]);
        exit;
    }

    // --- Vérifier chevauchement de réservation ---
    $check = $pdo->prepare("
        SELECT 1 FROM reservation
        WHERE id_bien = ?
          AND date_debut < ?
          AND date_fin   > ?
        LIMIT 1
    ");
    $check->execute([$idBien, $dateFinStockee, $dateDebut]);

    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ce bien est déjà réservé pour cette période']);
        exit;
    }

    // --- Résoudre le tarif ---
    // Priorité : tarif fourni par le client (déjà calculé côté JS)
    if (!$idTarif) {
        // Fallback : trouver le tarif de la semaine correspondant à date_debut
        $semaine = (int)date('W', strtotime($dateDebut));
        $annee   = (int)date('Y', strtotime($dateDebut));

        $tarifStmt = $pdo->prepare("
            SELECT id_tarif FROM tarif
            WHERE id_bien = ? AND semaine_tarif = ? AND annee_tarif = ?
            LIMIT 1
        ");
        $tarifStmt->execute([$idBien, $semaine, $annee]);
        $idTarif = $tarifStmt->fetchColumn();

        // Fallback 2 : n'importe quel tarif du bien pour cette année
        if (!$idTarif) {
            $tarifStmt = $pdo->prepare("SELECT id_tarif FROM tarif WHERE id_bien = ? AND annee_tarif = ? LIMIT 1");
            $tarifStmt->execute([$idBien, $annee]);
            $idTarif = $tarifStmt->fetchColumn();
        }

        // Fallback 3 : dernier tarif disponible pour ce bien
        if (!$idTarif) {
            $tarifStmt = $pdo->prepare("SELECT id_tarif FROM tarif WHERE id_bien = ? ORDER BY annee_tarif DESC, semaine_tarif DESC LIMIT 1");
            $tarifStmt->execute([$idBien]);
            $idTarif = $tarifStmt->fetchColumn();
        }
    }

    if (!$idTarif) {
        echo json_encode(['success' => false, 'message' => 'Aucun tarif disponible pour ce bien']);
        exit;
    }

    // --- Gestion de la contrainte FK locataire ---
    // Le projet a une table `locataire` séparée des `utilisateurs`.
    // On s'assure qu'une ligne existe pour cet utilisateur, avec INSERT IGNORE
    // pour ne pas écraser des données existantes et ne pas insérer de données vides.
    $checkLoc = $pdo->prepare("SELECT id_locataire FROM locataire WHERE id_locataire = ?");
    $checkLoc->execute([$idUtilisateur]);

    if (!$checkLoc->fetch()) {
        // Récupérer les vraies infos de l'utilisateur depuis la BDD
        $userStmt = $pdo->prepare("SELECT nom, prenom, email, tel, date_naissance FROM utilisateurs WHERE id_utilisateur = ?");
        $userStmt->execute([$idUtilisateur]);
        $userData = $userStmt->fetch();

        if (!$userData) {
            echo json_encode(['success' => false, 'message' => 'Utilisateur introuvable']);
            exit;
        }

        // Trouver une commune par défaut (obligatoire selon le schéma)
        $communeStmt = $pdo->query("SELECT id_commune FROM commune LIMIT 1");
        $idCommune   = $communeStmt->fetchColumn();

        if (!$idCommune) {
            echo json_encode(['success' => false, 'message' => 'Aucune commune disponible dans la base']);
            exit;
        }

        // INSERT IGNORE : si une contrainte UNIQUE empêche l'insert, on continue sans erreur
        $insertLoc = $pdo->prepare("
            INSERT IGNORE INTO locataire
                (id_locataire, nom_locataire, prenom_locataire, dna_locataire,
                 email_locataire, rue_locataire, comp_locataire,
                 id_commune, raison_sociale, pass_locataire, tel_locataire, siret)
            VALUES (?, ?, ?, ?, ?, '', '', ?, '', '', ?, '')
        ");
        $insertLoc->execute([
            $idUtilisateur,
            $userData['nom']             ?? '',
            $userData['prenom']          ?? '',
            $userData['date_naissance']  ?? null,
            $userData['email']           ?? '',
            $idCommune,
            $userData['tel']             ?? '',
        ]);
    }

    // --- Insérer la réservation ---
    $insert = $pdo->prepare("
        INSERT INTO reservation (date_debut, date_fin, id_locataire, id_bien, id_tarif)
        VALUES (?, ?, ?, ?, ?)
    ");

    if ($insert->execute([$dateDebut, $dateFinStockee, $idUtilisateur, $idBien, $idTarif])) {
        echo json_encode(['success' => true, 'message' => 'Réservation confirmée !']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement']);
    }

} catch (Exception $e) {
    error_log('ajax_reserver_bien error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
}