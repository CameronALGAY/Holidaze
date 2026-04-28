<?php
require_once __DIR__ . '/../include/db.php';

header('Content-Type: application/json; charset=utf-8');

$term = trim($_GET['q'] ?? '');

// Cas spécial : "%%%" => on renvoie toutes les réservations (limitées)
if ($term === '%%%') {
    $sql = "
        SELECT r.id_reservations,
               r.date_debut,
               r.date_fin,
               l.nom_locataire,
               l.prenom_locataire,
               b.nom_bien
        FROM reservation r
        JOIN locataire l ON r.id_locataire = l.id_locataire
        JOIN bien b      ON r.id_bien = b.id_bien
        ORDER BY r.date_debut DESC
        LIMIT 50
    ";
    $stmt = $pdo->query($sql);
} else {
    // On supprime % et _ pour tester la longueur "réelle"
    $clean = preg_replace('/[%_]/', '', $term);

    // On exige au moins 2 caractères propres
    if ($clean === '' || strlen($clean) < 2) {
        echo json_encode(['success' => false, 'reservations' => []]);
        exit;
    }

    $sql = "
        SELECT r.id_reservations,
               r.date_debut,
               r.date_fin,
               l.nom_locataire,
               l.prenom_locataire,
               b.nom_bien
        FROM reservation r
        JOIN locataire l ON r.id_locataire = l.id_locataire
        JOIN bien b      ON r.id_bien = b.id_bien
        WHERE 
            r.id_reservations LIKE :term
            OR l.nom_locataire LIKE :like
            OR l.prenom_locataire LIKE :like
            OR b.nom_bien LIKE :like
        ORDER BY r.date_debut DESC
        LIMIT 20
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':term', $term . '%');
    $stmt->bindValue(':like', '%' . $clean . '%');
    $stmt->execute();
}

$reservations = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $reservations[] = [
        'id_reservations' => $row['id_reservations'],
        'label' => sprintf(
            '#%d - %s %s - %s (%s → %s)',
            $row['id_reservations'],
            $row['prenom_locataire'],
            $row['nom_locataire'],
            $row['nom_bien'],
            $row['date_debut'],
            $row['date_fin']
        )
    ];
}

echo json_encode(['success' => true, 'reservations' => $reservations]);