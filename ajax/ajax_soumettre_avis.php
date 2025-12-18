<?php
// Fichier : ajax/ajax_soumettre_avis.php
session_start();
require_once '../include/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['utilisateur_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté']);
    exit;
}

$id_bien = isset($_POST['id_bien']) ? (int)$_POST['id_bien'] : 0;
$note = isset($_POST['note']) ? (int)$_POST['note'] : 0;
$commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : '';
$id_utilisateur = $_SESSION['utilisateur_id'];

// Vérifications
if (!$id_bien || $note < 1 || $note > 5) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

// Vérifier que l'utilisateur a déjà loué ce bien
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM reservation 
    WHERE id_bien = ? 
    AND id_locataire = ? 
    AND date_fin < NOW()
");
$stmt->execute([$id_bien, $id_utilisateur]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['count'] == 0) {
    echo json_encode(['success' => false, 'message' => 'Vous devez avoir loué ce bien pour laisser un avis']);
    exit;
}

// Vérifier si l'utilisateur a déjà laissé un avis pour ce bien
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM avis WHERE id_bien = ? AND id_utilisateur = ?");
$stmt->execute([$id_bien, $id_utilisateur]);
$avisExistant = $stmt->fetch(PDO::FETCH_ASSOC);

if ($avisExistant['count'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Vous avez déjà laissé un avis pour ce bien']);
    exit;
}

// Insérer l'avis (non validé par défaut)
try {
    $stmt = $pdo->prepare("
        INSERT INTO avis (id_bien, id_utilisateur, note, commentaire, valide_par_admin) 
        VALUES (?, ?, ?, ?, 0)
    ");
    $stmt->execute([$id_bien, $id_utilisateur, $note, $commentaire]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Votre avis a été soumis et est en attente de vérification par nos administrateurs.'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la soumission : ' . $e->getMessage()]);
}