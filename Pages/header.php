<?php
session_start();
?>

<header class="flex items-center justify-between px-6 py-4 bg-white shadow-md mb-6">
    <div class="text-2xl font-bold text-blue-600">Holidaze</div>
    <nav class="flex space-x-6 text-gray-700">
        <a href="/Pages/index.php" class="hover:text-blue-600 transition-colors duration-200">Accueil</a>
        <a href="#" class="hover:text-blue-600 transition-colors duration-200">Louer</a>
        <a href="#" class="hover:text-blue-600 transition-colors duration-200">Carte</a>
        <?php if (isset($_SESSION['utilisateur_id']) && $_SESSION['utilisateur_role'] === 'admin'): ?>
            <a href="/Pages/admin_dashboard.php" class="hover:text-blue-600 transition-colors duration-200">Admin</a>
        <?php endif; ?>
    </nav>
    <div class="flex space-x-4 items-center">
        <?php if (isset($_SESSION['utilisateur_id'])): ?>
            <span class="text-gray-700 font-medium">Bonjour, <?= htmlspecialchars($_SESSION['utilisateur_nom']) ?></span>
            <a href="/Pages/Formulaires/deconnexion.php" class="hover:text-blue-600 transition-colors duration-200">Déconnexion</a>
        <?php else: ?>
            <a href="/Pages/Formulaires/connexion.php" class="hover:text-blue-600 transition-colors duration-200">Connexion</a>
            <a href="/Pages/Formulaires/inscription.php" class="hover:text-blue-600 transition-colors duration-200">Inscription</a>
        <?php endif; ?>
        <button class="hover:text-blue-600 transition-colors duration-200"><i class="fa fa-heart"></i></button>
        <button class="hover:text-blue-600 transition-colors duration-200"><i class="fa fa-user"></i></button>
    </div>
</header>
