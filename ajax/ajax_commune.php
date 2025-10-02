<?php
require_once '../includes/db.php';

$term = $_GET['term'] ?? '';

if ($term) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT c.id_commune, c.nom_commune
        FROM commune c
        WHERE c.nom_commune LIKE ?
        ORDER BY c.nom_commune
        LIMIT 10
    ");
    $stmt->execute(['%' . $term . '%']);
    $communes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($communes);
} else {
    echo json_encode([]);
}
?>
