<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    require_once '../../include/db.php';
    require_once '../../include/smtp_mail.php';

    $action = $_REQUEST['action'] ?? '';

    $respond = function (array $payload, int $status = 200): void {
        http_response_code($status);
        echo json_encode($payload);
        exit;
    };

    $allowedRoles = ['user', 'proprietaire', 'admin'];

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
        $respond(['success' => false, 'message' => 'Methode non autorisee'], 405);
    }

    $id = (int)($_POST['id'] ?? 0);

    // Créer un utilisateur
    if ($action === 'createUser') {
        $prenom = trim($_POST['prenom'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $tel = trim($_POST['tel'] ?? '');
        $role = trim($_POST['role'] ?? 'user');

        if ($prenom === '' || $nom === '' || $email === '') {
            $respond(['success' => false, 'message' => 'Tous les champs obligatoires doivent etre renseignes']);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $respond(['success' => false, 'message' => 'Adresse email invalide']);
        }

        if (!in_array($role, $allowedRoles, true)) {
            $respond(['success' => false, 'message' => 'Role invalide']);
        }

        $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM utilisateurs WHERE email = ?');
        $checkStmt->execute([$email]);
        if ((int)$checkStmt->fetchColumn() > 0) {
            $respond(['success' => false, 'message' => 'Cet email est deja utilise']);
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);
        $temporaryPassword = bin2hex(random_bytes(16));
        $hash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
        $insert = $pdo->prepare(
            'INSERT INTO utilisateurs (email, mot_de_passe, actif, role, tel, photo_profil, type_entite, nom, prenom, reset_token, token_expiry) VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $success = $insert->execute([
            $email,
            $hash,
            $role,
            $tel,
            'default-avatar.png',
            'individu',
            $nom,
            $prenom,
            $token,
            $expiresAt,
        ]);

        $mailSent = false;
        if ($success) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $hostName = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $inviteLink = $scheme . '://' . $hostName . '/Pages/Formulaires/reinitialiser_mdp.php?email=' . urlencode($email) . '&token=' . $token;

            $subject = 'Votre compte Holidaze est prêt';
            $body = "Bonjour $prenom,\n\n";
            $body .= "Un compte Holidaze vient d'etre cree pour vous.\n\n";
            $body .= "Pour definir votre mot de passe, cliquez sur ce lien :\n" . $inviteLink . "\n\n";
            $body .= "Ce lien expire dans 1 heure. Si vous n'etes pas a l'origine de ce message, vous pouvez l'ignorer.";

            $mailSent = sendSmtpMail($email, $subject, $body);
        }

        $respond([
            'success' => $success,
            'mail_sent' => $mailSent,
            'message' => $success
                ? ($mailSent ? 'Utilisateur cree et mail d invitation envoye' : 'Utilisateur cree, mais le mail d invitation a echoue')
                : 'Creation impossible',
        ]);
    }

    // Changer le rôle
    if ($action === 'updateRole') {
        $role = trim($_POST['role'] ?? 'user');

        if ($id <= 0) {
            $respond(['success' => false, 'message' => 'Identifiant invalide']);
        }

        if (!in_array($role, $allowedRoles, true)) {
            $respond(['success' => false, 'message' => 'Role invalide']);
        }

        $stmt = $pdo->prepare("UPDATE utilisateurs SET role = ? WHERE id_utilisateur = ?");
        $success = $stmt->execute([$role, $id]);
        $respond(['success' => $success, 'message' => $success ? 'Role mis a jour' : 'Echec de mise a jour']);
    }

    // Activer / désactiver
    if ($action === 'toggleActif') {
        if ($id <= 0) {
            $respond(['success' => false, 'message' => 'Identifiant invalide']);
        }

        $stmt = $pdo->prepare("UPDATE utilisateurs SET actif = NOT actif WHERE id_utilisateur = ?");
        $success = $stmt->execute([$id]);
        $respond(['success' => $success, 'message' => $success ? 'Statut mis a jour' : 'Echec de mise a jour']);
    }

    // Supprimer
    if ($action === 'delete') {
        if ($id <= 0) {
            $respond(['success' => false, 'message' => 'Identifiant invalide']);
        }

        $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id_utilisateur = ?");
        $success = $stmt->execute([$id]);
        $respond(['success' => $success, 'message' => $success ? 'Utilisateur supprime' : 'Suppression impossible']);
    }

    // Réinitialiser le mot de passe
    if ($action === 'resetPassword') {
        $newPassword = (string)($_POST['password'] ?? '');
        $confirmPassword = (string)($_POST['confirmPassword'] ?? '');

        if ($id <= 0 || $newPassword === '' || $confirmPassword === '') {
            $respond(['success' => false, 'message' => 'Donnees manquantes']);
        }

        if (strlen($newPassword) < 12) {
            $respond(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 12 caracteres']);
        }

        if ($newPassword !== $confirmPassword) {
            $respond(['success' => false, 'message' => 'La confirmation du mot de passe ne correspond pas']);
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id_utilisateur = ?");
        $success = $stmt->execute([$hash, $id]);

        $respond(['success' => $success, 'message' => $success ? 'Mot de passe mis a jour' : 'Reinitialisation impossible']);
    }

    // Action inconnue
    $respond(['success' => false, 'message' => 'Action inconnue'], 400);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>