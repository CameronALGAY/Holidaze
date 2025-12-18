<?php
session_start();
require_once '../../include/db.php';

$message = "";
$temps_restant = 0;

// Configuration du système de blocage
define('MAX_TENTATIVES', 5); // Nombre maximum de tentatives
define('DUREE_BLOCAGE', 15 * 60); // Durée du blocage en secondes (15 minutes)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $mot_de_passe = $_POST['mot_de_passe'];

    // Vérifier si le compte est bloqué
    $stmt_blocage = $pdo->prepare("SELECT tentatives_connexion, derniere_tentative FROM utilisateurs WHERE email = ?");
    $stmt_blocage->execute([$email]);
    $info_blocage = $stmt_blocage->fetch();

    if ($info_blocage) {
        $tentatives = $info_blocage['tentatives_connexion'] ?? 0;
        $derniere_tentative = $info_blocage['derniere_tentative'] ? strtotime($info_blocage['derniere_tentative']) : 0;
        $temps_ecoule = time() - $derniere_tentative;

        // Vérifier si le compte est actuellement bloqué
        if ($tentatives >= MAX_TENTATIVES && $temps_ecoule < DUREE_BLOCAGE) {
            $temps_restant = DUREE_BLOCAGE - $temps_ecoule;
            $minutes = floor($temps_restant / 60);
            $secondes = $temps_restant % 60;
            $message = "Votre compte est temporairement bloqué suite à plusieurs tentatives échouées. Temps restant : {$minutes} minute(s) et {$secondes} seconde(s).";
        } else {
            // Si le délai de blocage est écoulé, réinitialiser les tentatives
            if ($tentatives >= MAX_TENTATIVES && $temps_ecoule >= DUREE_BLOCAGE) {
                $stmt_reset = $pdo->prepare("UPDATE utilisateurs SET tentatives_connexion = 0, derniere_tentative = NULL WHERE email = ?");
                $stmt_reset->execute([$email]);
                $tentatives = 0;
            }

            // Tenter la connexion
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? AND actif = TRUE");
            $stmt->execute([$email]);
            $utilisateur = $stmt->fetch();

            if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
                // Connexion réussie - Réinitialiser les tentatives
                $stmt_reset = $pdo->prepare("UPDATE utilisateurs SET tentatives_connexion = 0, derniere_tentative = NULL WHERE email = ?");
                $stmt_reset->execute([$email]);

                // Stockage des informations en session
                $_SESSION['utilisateur_id'] = $utilisateur['id_utilisateur'];
                $_SESSION['prenom'] = $utilisateur['prenom'];
                $_SESSION['nom'] = $utilisateur['nom'];
                $_SESSION['role'] = $utilisateur['role'];
                $_SESSION['email'] = $utilisateur['email'];
                // Ajout de la photo de profil avec le chemin complet
                $_SESSION['photo_profil'] = !empty($utilisateur['photo_profil']) 
                    ? '../../Photo/profil/' . $utilisateur['photo_profil'] 
                    : null;

                // On crée le tableau 'utilisateur' dans la session et on y stocke la date de naissance
                $_SESSION['utilisateur']['date_naissance'] = $utilisateur['date_naissance'];

                // Redirection selon le rôle
                if ($utilisateur['role'] === 'admin') {
                    header('Location: /Pages/admin_dashboard.php');
                } else {
                    header('Location: /Pages/index.php');
                }
                exit;
            } else {
                // Échec de connexion - Incrémenter les tentatives
                $nouvelles_tentatives = $tentatives + 1;
                $stmt_update = $pdo->prepare("UPDATE utilisateurs SET tentatives_connexion = ?, derniere_tentative = NOW() WHERE email = ?");
                $stmt_update->execute([$nouvelles_tentatives, $email]);

                if ($nouvelles_tentatives >= MAX_TENTATIVES) {
                    $minutes = floor(DUREE_BLOCAGE / 60);
                    $message = "Trop de tentatives échouées. Votre compte est bloqué pour {$minutes} minutes.";
                } else {
                    $tentatives_restantes = MAX_TENTATIVES - $nouvelles_tentatives;
                    $message = "Email ou mot de passe incorrect. Il vous reste {$tentatives_restantes} tentative(s) avant le blocage du compte.";
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
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome pour l'icône œil -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        .password-toggle {
            cursor: pointer;
            user-select: none;
            transition: color 0.3s ease;
        }
        .password-toggle:hover {
            color: #3b82f6;
        }
    </style>
    
    <?php if ($temps_restant > 0): ?>
    <script>
        // Compte à rebours dynamique
        let tempsRestant = <?= $temps_restant ?>;
        
        function mettreAJourCompteur() {
            if (tempsRestant <= 0) {
                location.reload();
                return;
            }
            
            const minutes = Math.floor(tempsRestant / 60);
            const secondes = tempsRestant % 60;
            
            const messageElement = document.getElementById('message-blocage');
            if (messageElement) {
                messageElement.textContent = `Votre compte est temporairement bloqué suite à plusieurs tentatives échouées. Temps restant : ${minutes} minute(s) et ${secondes} seconde(s).`;
            }
            
            tempsRestant--;
            setTimeout(mettreAJourCompteur, 1000);
        }
        
        window.addEventListener('DOMContentLoaded', mettreAJourCompteur);
    </script>
    <?php endif; ?>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-md mx-auto bg-white shadow-md rounded-lg p-6">
        <div class="mb-4">
            <a href="../index.php" class="text-sm text-blue-500 hover:underline">&larr; Retour à l'accueil</a>
        </div>
        <h1 class="text-2xl font-bold mb-6">Connexion</h1>
        <?php if (isset($_SESSION['error_message'])): ?>
            <p class="text-red-500 mb-4"><?= htmlspecialchars($_SESSION['error_message']) ?></p>
            <?php unset($_SESSION['error_message']); ?>
        <?php elseif ($message): ?>
            <p class="text-red-500 mb-4 font-semibold" id="message-blocage"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>
        <form method="POST" action="" class="space-y-4" <?= $temps_restant > 0 ? 'style="pointer-events: none; opacity: 0.5;"' : '' ?>>
            <div>
                <label class="block text-gray-700">Email :</label>
                <input type="email" name="email" required class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" <?= $temps_restant > 0 ? 'disabled' : '' ?>>
            </div>
            <div>
                <label class="block text-gray-700">Mot de passe :</label>
                <div class="relative">
                    <input type="password" 
                           name="mot_de_passe" 
                           id="mot_de_passe"
                           required 
                           class="w-full border rounded-lg p-2 pr-10 focus:ring focus:ring-blue-300" 
                           <?= $temps_restant > 0 ? 'disabled' : '' ?>>
                    <button type="button" 
                            id="togglePassword"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-600 password-toggle"
                            <?= $temps_restant > 0 ? 'disabled' : '' ?>>
                        <i class="fas fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            
            <div class="text-right text-sm">
                <a href="mdp_oublie.php" class="text-blue-500 hover:underline">Mot de passe oublié ?</a>
            </div>
            
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 w-full" <?= $temps_restant > 0 ? 'disabled' : '' ?>>Se connecter</button>
        </form>
        <p class="mt-4 text-center">Pas encore inscrit ? <a href="inscription.php" class="text-blue-500 hover:underline">Inscrivez-vous</a></p>
        
    </div>

    <script>
        // Fonction pour afficher/masquer le mot de passe
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('mot_de_passe');
        const eyeIcon = document.getElementById('eyeIcon');

        if (togglePassword) {
            togglePassword.addEventListener('click', function() {
                // Basculer le type d'input entre 'password' et 'text'
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Changer l'icône de l'œil
                if (type === 'text') {
                    eyeIcon.classList.remove('fa-eye');
                    eyeIcon.classList.add('fa-eye-slash');
                } else {
                    eyeIcon.classList.remove('fa-eye-slash');
                    eyeIcon.classList.add('fa-eye');
                }
            });
        }
    </script>
</body>
</html>