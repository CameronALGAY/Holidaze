<?php
// ajax/ajax_recherche_filtre.php

header('Content-Type: application/json; charset=utf-8');
require_once '../include/db.php';

$search      = trim($_GET['commune'] ?? '');  // La saisie utilisateur (nom, CP, dept)
$type        = trim($_GET['type'] ?? '');
$prix_min    = $_GET['prix_min'] !== '' ? (float)$_GET['prix_min'] : null;
$prix_max    = $_GET['prix_max'] !== '' ? (float)$_GET['prix_max'] : null;
$prestations = isset($_GET['prestations']) 
    ? (is_array($_GET['prestations']) ? $_GET['prestations'] : [$_GET['prestations']]) 
    : [];
$prestations = array_filter($prestations);

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
";

$params = [];

if ($search !== '') {
    // Cas 1 : Code postal complet (ex: 75008)
    if (preg_match('/^\d{5}$/', $search)) {
        $sql .= " AND c.cp_commune = ?";
        $params[] = $search;
    }
    // Cas 2 : Département (ex: 75, 690, 13, 2A, 971)
    elseif (preg_match('/^\d{1,3}$/', $search) || preg_match('/^[0-9]{2}[A-B]$/', $search)) {
        $sql .= " AND c.cp_commune LIKE ?";
        $params[] = $search . '%';
    }
    // Cas 3 : Nom de commune (ex: Paris, Lyon, Bellevu...)
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