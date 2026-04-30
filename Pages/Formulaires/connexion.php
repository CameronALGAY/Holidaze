<?php
/**
 * Connexion — version corrigée
 * Corrections : session fixation, CSRF, display_errors supprimé
 */
session_start();
require_once '../../include/db.php';
require_once '../../include/csrf.php';   // <- NOUVEAU

$message = "";
$temps_restant = 0;

define('MAX_TENTATIVES', 5);
define('DUREE_BLOCAGE', 15 * 60);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- VERIFICATION CSRF ---
    csrf_verify();   // <- NOUVEAU : stoppe si token invalide

    $email       = trim($_POST['email']);
    $mot_de_passe = $_POST['mot_de_passe'];

    $stmt_blocage = $pdo->prepare("SELECT tentatives_connexion, derniere_tentative FROM utilisateurs WHERE email = ?");
    $stmt_blocage->execute([$email]);
    $info_blocage = $stmt_blocage->fetch();

    if ($info_blocage) {
        $tentatives      = $info_blocage['tentatives_connexion'] ?? 0;
        $derniere        = $info_blocage['derniere_tentative'] ? strtotime($info_blocage['derniere_tentative']) : 0;
        $temps_ecoule    = time() - $derniere;

        if ($tentatives >= MAX_TENTATIVES && $temps_ecoule < DUREE_BLOCAGE) {
            $temps_restant = DUREE_BLOCAGE - $temps_ecoule;
            $minutes  = floor($temps_restant / 60);
            $secondes = $temps_restant % 60;
            $message  = "Compte bloqué. Réessayez dans {$minutes} min {$secondes} s.";
        } else {
            if ($tentatives >= MAX_TENTATIVES && $temps_ecoule >= DUREE_BLOCAGE) {
                $pdo->prepare("UPDATE utilisateurs SET tentatives_connexion = 0, derniere_tentative = NULL WHERE email = ?")
                    ->execute([$email]);
                $tentatives = 0;
            }

            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? AND actif = TRUE");
            $stmt->execute([$email]);
            $utilisateur = $stmt->fetch();

            if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {

                // --- CORRECTION SESSION FIXATION ---
                // On régénère l'ID de session AVANT d'y stocker des données sensibles
                session_regenerate_id(true);   // <- NOUVEAU

                // Réinitialiser les tentatives
                $pdo->prepare("UPDATE utilisateurs SET tentatives_connexion = 0, derniere_tentative = NULL WHERE email = ?")
                    ->execute([$email]);

                // Régénérer le token CSRF après connexion
                unset($_SESSION['csrf_token']);   // <- NOUVEAU : force un nouveau token

                // Stocker les données en session
                $_SESSION['utilisateur_id'] = $utilisateur['id_utilisateur'];
                $_SESSION['prenom']         = $utilisateur['prenom'];
                $_SESSION['nom']            = $utilisateur['nom'];
                $_SESSION['role']           = $utilisateur['role'];
                $_SESSION['email']          = $utilisateur['email'];
                $_SESSION['photo_profil']   = !empty($utilisateur['photo_profil'])
                    ? '../../Photo/profil/' . $utilisateur['photo_profil']
                    : null;
                $_SESSION['utilisateur']['date_naissance'] = $utilisateur['date_naissance'];

                if ($utilisateur['role'] === 'admin') {
                    header('Location: /Pages/admin_dashboard.php');
                } else {
                    header('Location: /Pages/index.php');
                }
                exit;

            } else {
                $nouvelles_tentatives = $tentatives + 1;
                $pdo->prepare("UPDATE utilisateurs SET tentatives_connexion = ?, derniere_tentative = NOW() WHERE email = ?")
                    ->execute([$nouvelles_tentatives, $email]);

                if ($nouvelles_tentatives >= MAX_TENTATIVES) {
                    $message = "Trop de tentatives. Compte bloqué " . floor(DUREE_BLOCAGE / 60) . " min.";
                } else {
                    $restantes = MAX_TENTATIVES - $nouvelles_tentatives;
                    $message = "Email ou mot de passe incorrect. {$restantes} tentative(s) restante(s).";
                }
            }
        }
    } else {
        $message = "Email ou mot de passe incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Connexion à votre compte Holidaze.">
    <meta name="robots" content="noindex, nofollow">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-md mx-auto bg-white shadow-md rounded-lg p-6">
        <div class="mb-4">
            <a href="../index.php" class="text-sm text-blue-500 hover:underline">&larr; Retour à l'accueil</a>
        </div>
        <h1 class="text-2xl font-bold mb-6">Connexion</h1>

        <?php if ($message): ?>
            <p class="text-red-500 mb-4 font-semibold"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <?= csrf_field() ?>  <!-- CSRF token injecté automatiquement -->

            <div class="mb-4">
                <label class="block text-gray-700">Email :</label>
                <input type="email" name="email" required
                       class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="mb-4">
                <label class="block text-gray-700">Mot de passe :</label>
                <div class="relative">
                    <input type="password" name="mot_de_passe" id="mot_de_passe" required
                           class="w-full border rounded-lg p-2 pr-10 focus:ring focus:ring-blue-300">
                    <button type="button" id="togglePassword"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-600">
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            <div class="text-right text-sm mb-4">
                <a href="mdp_oublie.php" class="text-blue-500 hover:underline">Mot de passe oublié ?</a>
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 w-full">
                Se connecter
            </button>
        </form>
        <p class="mt-4 text-center">Pas encore inscrit ?
            <a href="inscription.php" class="text-blue-500 hover:underline">Inscrivez-vous</a>
        </p>
    </div>
    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const input = document.getElementById('mot_de_passe');
            const icon  = document.getElementById('eyeIcon');
            const type  = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            icon.classList.toggle('fa-eye', type === 'password');
            icon.classList.toggle('fa-eye-slash', type === 'text');
        });
    </script>
</body>
</html>
