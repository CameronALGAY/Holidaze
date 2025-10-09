<?php
require_once __DIR__ . '/../include/db.php'; // ✅ même structure que ajax_bien.php

header('Content-Type: application/json');

if (isset($_GET['q']) && strlen($_GET['q']) >= 2) {
    $search = '%' . $_GET['q'] . '%';

    try {
        $stmt = $pdo->prepare("
            SELECT id_typebien, des_typebien
            FROM type_bien
            WHERE des_typebien LIKE ?
            ORDER BY des_typebien
            LIMIT 20
        ");
        
        $stmt->execute([$search]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'typebiens' => $results
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
        'typebiens' => []
    ]);
}
?>
