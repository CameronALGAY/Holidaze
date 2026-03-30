<?php
/**
 * AJAX RECHERCHE AVANCÉE (appelé par index.php JS)
 * - Filtres : commune (nom/CP/dept), type_bien, prix min/max, prestations
 * - JSON response : count + biens (photo, prix_min, note_moyenne)
 * - Requêtes subqueries optimisées (1ère photo, min tarif, AVG notes)
 * - WHERE dynamique + LIKE/IN préparés (sécurisé)
 */
header('Content-Type: application/json; charset=utf-8');
require_once '../include/db.php';  // $pdo

// Récupère + nettoie params GET (depuis index.php)
$search      = trim($_GET['commune'] ?? '');  // Ville/CP/dept
$type        = trim($_GET['type'] ?? '');     // Type bien
$prix_min    = $_GET['prix_min'] !== '' ? (float)$_GET['prix_min'] : null;
$prix_max    = $_GET['prix_max'] !== '' ? (float)$_GET['prix_max'] : null;
$prestations = isset($_GET['prestations']) 
    ? (is_array($_GET['prestations']) ? $_GET['prestations'] : [$_GET['prestations']]) 
    : [];
$prestations = array_filter($prestations);    // Nettoie vides

// Requête de base (biens + infos essentielles)
$sql = "
    SELECT DISTINCT 
        b.id_bien,
        b.nom_bien,
        b.superficie_bien,
        b.nb_couchage,
        b.animaux_bien,
        c.nom_commune,
        c.cp_commune,
        tb.des_typebien,
        (SELECT lien_photo FROM photo WHERE id_bien = b.id_bien ORDER BY id_photo LIMIT 1) AS premiere_photo_lien,
        (SELECT MIN(tarif) FROM tarif WHERE id_bien = b.id_bien) AS prix_min_nuit
    FROM bien b
    LEFT JOIN commune c ON b.id_commune = c.id_commune
    LEFT JOIN type_bien tb ON b.id_typebien = tb.id_typebien
    LEFT JOIN secompose sc ON b.id_bien = sc.id_bien
    WHERE 1=1
    AND b.valide = 1";

$params = [];  // Params pour prepared stmt

// FILTRE COMMUNE (3 cas intelligents)
if ($search !== '') {
    // 1. CP exact (75008)
    if (preg_match('/^\\d{5}$/', $search)) {
        $sql .= " AND c.cp_commune = ?";
        $params[] = $search;
    }
    // 2. Dépt (75, 69, 2A...)
    elseif (preg_match('/^\\d{1,3}$/', $search) || preg_match('/^[0-9]{2}[A-B]$/', $search)) {
        $sql .= " AND c.cp_commune LIKE ?";
        $params[] = $search . '%';
    }
    // 3. Nom ville partiel
    else {
        $sql .= " AND c.nom_commune LIKE ?";
        $params[] = '%' . $search . '%';
    }
}


// Type de bien
if ($type !== '') {
    $sql .= " AND tb.des_typebien = ?";
    $params[] = $type;
}

// Prix
if ($prix_min !== null) {
    $sql .= " AND EXISTS (SELECT 1 FROM tarif t WHERE t.id_bien = b.id_bien AND t.tarif >= ?)";
    $params[] = $prix_min;
}
if ($prix_max !== null) {
    $sql .= " AND EXISTS (SELECT 1 FROM tarif t WHERE t.id_bien = b.id_bien AND t.tarif <= ?)";
    $params[] = $prix_max;
}

// Prestations
if (!empty($prestations)) {
    $placeholders = str_repeat('?,', count($prestations) - 1) . '?';
    $sql .= " AND sc.id_prestation IN ($placeholders)";
    $params = array_merge($params, $prestations);
}

$sql .= " ORDER BY b.nom_bien LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$biens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ajout des notes moyennes
foreach ($biens as &$bien) {
    $note = $pdo->prepare("SELECT AVG(note) as avg, COUNT(*) as nb FROM avis WHERE id_bien = ?");
    $note->execute([$bien['id_bien']]);
    $res = $note->fetch();
    $bien['note_moyenne'] = $res['avg'] ? round((float)$res['avg'], 1) : null;
    $bien['nb_avis'] = (int)$res['nb'];
}
unset($bien);

echo json_encode([
    'success' => true,
    'count'   => count($biens),
    'biens'   => $biens
]);