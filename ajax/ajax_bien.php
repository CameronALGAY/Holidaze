<?php
require_once __DIR__ . '/../include/db.php';

header('Content-Type: application/json');

if (isset($_GET['q']) && strlen($_GET['q']) >= 2) {
    $search = '%' . $_GET['q'] . '%';
    
    try {
        $stmt = $pdo->prepare("
            SELECT id_commune, nom_commune, cp_commune, commune_departement
            FROM commune
            WHERE nom_commune LIKE ?
            OR commune_nom_simple LIKE ?
            OR cp_commune LIKE ?
            ORDER BY nom_commune
            LIMIT 20
        ");
        
        $stmt->execute([$search, $search, $search]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'communes' => $results
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur de recherche'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'communes' => []
    ]);
}
?>