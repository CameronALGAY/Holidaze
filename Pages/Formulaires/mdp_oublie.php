<?php
session_start();
require_once '../../include/db.php'; // Assurez-vous que le chemin vers votre base de données est correct

$message = "";
$message_type = ""; // 'success' ou 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // 1. Vérification de l'existence de l'utilisateur
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? AND actif = TRUE");
    $stmt->execute([$email]);
    $utilisateur = $stmt->fetch();

    if ($utilisateur) {
        // --- LOGIQUE DE GESTION DU TOKEN DE RÉINITIALISATION (SIMULATION) ---
        
        // Générer un token unique
        $token = bin2hex(random_bytes(32)); 
        // Définir une date d'expiration (par exemple, 1 heure)
        $expires_at = date("Y-m-d H:i:s", time() + 3600); 

        // Enregistrer le token et l'expiration dans la base de données
        // Assurez-vous que votre table `utilisateurs` ou une table dédiée `password_resets` 
        // contient des colonnes pour `reset_token` et `token_expiry`.

        $update_stmt = $pdo->prepare("UPDATE utilisateurs SET reset_token = ?, token_expiry = ? WHERE id_utilisateur = ?");
        $update_stmt->execute([$token, $expires_at, $utilisateur['id_utilisateur']]);

        // Construire le lien de réinitialisation
        // Remplacez 'http://votre-site.com' par l'URL de base de votre application
        $reset_link = "http://votre-site.com/Pages/reinitialiser_mdp.php?email=" . urlencode($email) . "&token=" . $token;

        // --- SIMULATION D'ENVOI D'EMAIL ---
        // En production, vous utiliseriez une librairie comme PHPMailer ici.
        
        // $sujet = "Réinitialisation de votre mot de passe";
        // $corps_email = "Veuillez cliquer sur ce lien pour réinitialiser votre mot de passe : " . $reset_link;
        // mail($email, $sujet, $corps_email, "From: support@votre-site.com");

        // Message de succès affiché à l'utilisateur
        $message = "Si cette adresse e-mail existe dans notre système, un lien de réinitialisation de mot de passe vous a été envoyé. Vérifiez votre boîte de réception (et vos spams).";
        $message_type = "success";
        
    } else {
        // Pour des raisons de sécurité, nous donnons un message générique même si l'email n'existe pas.
        $message = "Si cette adresse e-mail existe dans notre système, un lien de réinitialisation de mot de passe vous a été envoyé. Vérifiez votre boîte de réception (et vos spams).";
        $message_type = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mot de passe oublié</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full bg-white shadow-xl rounded-lg p-8">
        <h1 class="text-2xl font-bold mb-6 text-center text-gray-800">Mot de passe oublié ?</h1>
        <p class="mb-6 text-center text-gray-600 text-sm">
            Entrez votre adresse e-mail ci-dessous. Nous vous enverrons un lien pour réinitialiser votre mot de passe.
        </p>

        <?php if ($message): ?>
            <?php 
                $alert_class = $message_type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
                $icon_class = $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
            ?>
            <div class="<?= $alert_class ?> border px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Information :</strong>
                <span class="block sm:inline"><?= htmlspecialchars($message) ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="space-y-6">
            <div>
                <label for="email" class="block text-gray-700 font-medium mb-1">Adresse e-mail :</label>
                <input 
                    type="email" 
                    name="email" 
                    id="email"
                    required 
                    class="w-full border border-gray-300 rounded-lg p-3 focus:ring-blue-500 focus:border-blue-500" 
                    placeholder="votre.email@exemple.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                >
            </div>
            <button type="submit" class="bg-blue-600 text-white font-semibold px-4 py-3 rounded-lg hover:bg-blue-700 transition duration-150 w-full shadow-md">
                Envoyer le lien de réinitialisation
            </button>
        </form>
        
        <div class="mt-6 text-center text-sm">
            <a href="connexion.php" class="text-blue-500 hover:underline">Retour à la page de connexion</a>
        </div>
    </div>
</body>
</html>