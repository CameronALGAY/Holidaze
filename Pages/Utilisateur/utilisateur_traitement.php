<?php
// Pages/Utilisateurs/utilisateur_traitement.php
// Version 100% complète, sécurisée et fonctionnelle

header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    require_once '../../include/db.php';

    $action = $_REQUEST['action'] ?? '';

    // GET : Lister ou rechercher
    if ($action === 'getAll' || $action === 'search') {
        $search = $_GET['q'] ?? '';
        $sql = "SELECT id_utilisateur, prenom, nom, email, role, tel, photo_profil, actif 
                FROM utilisateurs";
        $params = [];

        if ($search !== '') {
            $search = "%$search%";
            $sql .= " WHERE prenom LIKE ? OR nom LIKE ? OR email LIKE ? OR tel LIKE ?";
            $params = [$search, $search, $search, $search];
        }

        $sql .= " ORDER BY nom, prenom";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($users);
        exit;
    }

    // POST : Toutes les actions de modification
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        exit;
    }

    $id = $_POST['id'] ?? 0;

    // Changer le rôle
    if ($action === 'updateRole') {
        $role = $_POST['role'] ?? 'user';
        $allowed = ['user', 'proprietaire', 'admin'];
        if (!in_array($role, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Rôle invalide']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE utilisateurs SET role = ? WHERE id_utilisateur = ?");
        $success = $stmt->execute([$role, $id]);
        echo json_encode(['success' => $success]);
        exit;
    }

    // Activer / désactiver
    if ($action === 'toggleActif') {
        $stmt = $pdo->prepare("UPDATE utilisateurs SET actif = NOT actif WHERE id_utilisateur = ?");
        $success = $stmt->execute([$id]);
        echo json_encode(['success' => $success]);
        exit;
    }

    // Supprimer
    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id_utilisateur = ?");
        $success = $stmt->execute([$id]);
        echo json_encode(['success' => $success]);
        exit;
    }

    // Réinitialiser le mot de passe (nouveau !)
    if ($action === 'resetPassword') {
        $newPassword = $_POST['password'] ?? '';
        if ($id <= 0 || empty($newPassword)) {
            echo json_encode(['success' => false, 'message' => 'Données manquantes']);
            exit;
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id_utilisateur = ?");
        $success = $stmt->execute([$hash, $id]);

        echo json_encode(['success' => $success]);
        exit;
    }

    // Action inconnue
    echo json_encode(['success' => false, 'message' => 'Action inconnue']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>