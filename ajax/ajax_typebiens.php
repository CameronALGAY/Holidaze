<?php
require_once __DIR__ . '/../include/db.php'; 

header('Content-Type: application/json');

$search = $_GET['search'] ?? '';

if (strlen($search) >= 1) { // tu avais >=2 pour communes, mais >=1 ici pour typebien
    $searchTerm = '%' . $search . '%';

    try {
        $stmt = $pdo->prepare("
            SELECT id_typebien, des_typebien
            FROM type_bien
            WHERE des_typebien LIKE ?
            ORDER BY des_typebien
            LIMIT 20
        ");
        
        $stmt->execute([$searchTerm]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $results
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
        'data' => []
    ]);
}
?>
