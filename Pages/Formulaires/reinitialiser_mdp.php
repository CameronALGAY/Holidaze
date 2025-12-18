<?php
session_start();
require_once '../../include/db.php'; // Chemin vers votre connexion BDD

$message = "";
$message_type = "";
$form_enabled = false;
$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

// --- ÉTAPE 1 : VALIDATION DU TOKEN ET DE L'EMAIL ---
if (!empty($email) && !empty($token)) {
    // Nettoyage des entrées
    $email = trim(strtolower($email));
    $token = trim($token);

    // Recherche de l'utilisateur avec l'email et le token fournis
    $stmt = $pdo->prepare("SELECT id_utilisateur, token_expiry FROM utilisateurs WHERE email = ? AND reset_token = ? AND actif = TRUE");
    $stmt->execute([$email, $token]);
    $utilisateur = $stmt->fetch();

    if ($utilisateur) {
        $expiry_time = strtotime($utilisateur['token_expiry']);
        $current_time = time();

        if ($current_time < $expiry_time) {
            // Token valide et non expiré : afficher le formulaire
            $form_enabled = true;
        } else {
            // Token expiré
            $message = "Ce lien de réinitialisation a expiré. Veuillez refaire une demande de réinitialisation de mot de passe.";
            $message_type = "error";
        }
    } else {
        // Token ou email incorrect
        $message = "Lien de réinitialisation invalide ou déjà utilisé.";
        $message_type = "error";
    }
} else {
    // Pas d'email ou de token dans l'URL
    $message = "Accès non autorisé. Le lien de réinitialisation est incomplet.";
    $message_type = "error";
}


// --- ÉTAPE 2 : TRAITEMENT DU NOUVEAU MOT DE PASSE ---
if ($form_enabled && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nouveau_mdp = $_POST['nouveau_mdp'];
    $confirmer_mdp = $_POST['confirmer_mdp'];
    
    // Règle de validation du mot de passe (doit correspondre à la page d'inscription)
    $password_regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]).{12,}$/'; 

    if ($nouveau_mdp !== $confirmer_mdp) {
        $message = "Les mots de passe ne correspondent pas.";
        $message_type = "error";
    } elseif (!preg_match($password_regex, $nouveau_mdp)) {
        $message = "Le mot de passe ne respecte pas les critères de sécurité (12 caractères, Majuscule, Chiffre, Caractère spécial).";
        $message_type = "error";
    } else {
        // Hachage du mot de passe
        $mot_de_passe_hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);
        
        // Mise à jour du mot de passe et suppression du token pour invalider le lien
        $update_stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ?, reset_token = NULL, token_expiry = NULL WHERE id_utilisateur = ?");
        
        if ($update_stmt->execute([$mot_de_passe_hash, $utilisateur['id_utilisateur']])) {
            $message = "Votre mot de passe a été réinitialisé avec succès ! Vous pouvez maintenant vous connecter.";
            $message_type = "success";
            $form_enabled = false; // Désactiver le formulaire après le succès
        } else {
            $message = "Une erreur est survenue lors de la mise à jour du mot de passe.";
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réinitialiser le Mot de Passe</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full bg-white shadow-xl rounded-lg p-8">
        <h1 class="text-2xl font-bold mb-6 text-center text-gray-800">Définir un Nouveau Mot de Passe</h1>

        <?php if ($message): ?>
            <?php 
                $alert_class = $message_type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
            ?>
            <div class="<?= $alert_class ?> border px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($form_enabled): ?>
            <form method="POST" action="reinitialiser_mdp.php?email=<?= urlencode($email) ?>&token=<?= urlencode($token) ?>" class="space-y-6">
                <div>
                    <label for="nouveau_mdp" class="block text-gray-700 font-medium mb-1">Nouveau Mot de Passe :</label>
                    <input 
                        type="password" 
                        name="nouveau_mdp" 
                        id="nouveau_mdp"
                        required 
                        class="w-full border border-gray-300 rounded-lg p-3 focus:ring-blue-500 focus:border-blue-500" 
                        placeholder="Minimum 12 caractères..."
                    >
                </div>
                <div>
                    <label for="confirmer_mdp" class="block text-gray-700 font-medium mb-1">Confirmer le Mot de Passe :</label>
                    <input 
                        type="password" 
                        name="confirmer_mdp" 
                        id="confirmer_mdp"
                        required 
                        class="w-full border border-gray-300 rounded-lg p-3 focus:ring-blue-500 focus:border-blue-500" 
                    >
                </div>
                <button type="submit" class="bg-blue-600 text-white font-semibold px-4 py-3 rounded-lg hover:bg-blue-700 transition duration-150 w-full shadow-md">
                    Changer le mot de passe
                </button>
            </form>
        <?php endif; ?>
        
        <div class="mt-6 text-center text-sm">
            <?php if (!$form_enabled): ?>
                <a href="connexion.php" class="text-blue-500 hover:underline">Retour à la page de connexion</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>