<?php
require_once __DIR__ . '/../include/db.php';

header('Content-Type: application/json; charset=utf-8');

$term = trim($_GET['q'] ?? '');

// Cas spécial : "%%%" => renvoie tous les intervenants (limités)
if ($term === '%%%') {
    $sql = "
        SELECT id_intervenant, nom_intervenant, prenom_intervenant
        FROM intervenants
        ORDER BY nom_intervenant, prenom_intervenant
        LIMIT 50
    ";
    $stmt = $pdo->query($sql);
} else {
    $clean = preg_replace('/[%_]/', '', $term);

    if ($clean === '' || strlen($clean) < 1) {
        echo json_encode(['success' => false, 'intervenants' => []]);
        exit;
    }

    $sql = "
        SELECT id_intervenant, nom_intervenant, prenom_intervenant
        FROM intervenants
        WHERE nom_intervenant LIKE :like
           OR prenom_intervenant LIKE :like
        ORDER BY nom_intervenant, prenom_intervenant
        LIMIT 20
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':like', '%' . $clean . '%');
    $stmt->execute();
}

$intervenants = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $intervenants[] = [
        'id_intervenant' => $row['id_intervenant'],
        'label' => sprintf(
            '#%d - %s %s',
            $row['id_intervenant'],
            $row['prenom_intervenant'],
            $row['nom_intervenant']
        )
    ];
}

echo json_encode(['success' => true, 'intervenants' => $intervenants]);