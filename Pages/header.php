<?php
/**
 * HEADER / NAVIGATION COMMUN À TOUTES LES PAGES
 * - Bootstrap 5 + icons personnalisés
 * - Session utilisateur (role, photo_profil...)
 * - Menu profil déroulant (messages, admin...)
 * - Responsive mobile OK
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();  // Sécurité : évite multi-start
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holidaze</title>
    <!-- Bootstrap 5 CSS + Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        .navbar-container { max-width: 1200px; margin: 0 auto; padding: 0 15px; }
        .navbar { background-color: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .navbar-brand { font-weight: bold; color: #2563eb; }
        .navbar-brand:hover { color: #1e40af; }
        .nav-link { color: #000 !important; }
        .nav-link:hover { color: #2563eb !important; }
        .icon-btn { font-size: 1.3rem; color: #444; cursor: pointer; }
        .icon-btn:hover { color: #2563eb; }
        .profile-pic { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb; transition: border-color 0.2s; cursor: pointer; }
        .profile-pic:hover { border-color: #2563eb; }
        .profile-icon-fallback { font-size: 2rem; color: #444; cursor: pointer; transition: color 0.2s; }
        .profile-icon-fallback:hover { color: #2563eb; }
        .profile-dropdown { position: relative; }
        .profile-dropdown-menu { position: absolute; top: 50px; right: 0; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 220px; opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.3s ease; z-index: 1000; }
        .profile-dropdown-menu.show { opacity: 1; visibility: visible; transform: translateY(0); }
        .profile-dropdown-menu::before { content: ''; position: absolute; top: -8px; right: 12px; width: 16px; height: 16px; background: white; transform: rotate(45deg); box-shadow: -2px -2px 4px rgba(0,0,0,0.05); }
        .dropdown-header { padding: 15px; border-bottom: 1px solid #e5e7eb; }
        .dropdown-header-name { font-weight: 600; color: #1f2937; margin: 0; }
        .dropdown-header-email { font-size: 0.875rem; color: #6b7280; margin: 0; }
        .dropdown-item-custom { display: flex; align-items: center; padding: 12px 15px; color: #374151; text-decoration: none; transition: background-color 0.2s; border: none; width: 100%; background: none; cursor: pointer; }
        .dropdown-item-custom:hover { background-color: #f3f4f6; color: #2563eb; }
        .dropdown-item-custom i { margin-right: 10px; font-size: 1.1rem; width: 20px; }
        .dropdown-divider-custom { height: 1px; background-color: #e5e7eb; margin: 8px 0; }
        .dropdown-logout { color: #dc2626; }
        .dropdown-logout:hover { background-color: #fee2e2; color: #dc2626; }
        .heart-icon { transition: all 0.2s ease; }
        .heart-icon:hover { color: #ec4899 !important; text-shadow: 0 0 8px rgba(236, 72, 153, 0.5); transform: scale(1.1); }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg bg-white shadow-sm">
    <div class="navbar-container d-flex align-items-center justify-content-between w-100">

        <a class="navbar-brand" href="/Pages/index.php">Holidaze</a>

        <div class="d-flex align-items-center">
            <ul class="navbar-nav me-3">
                <li class="nav-item"><a class="nav-link" href="/Pages/index.php">Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="/Pages/Bien/bien_form.php">Louer</a></li>

                <?php if (isset($_SESSION['utilisateur_id'])): ?>
                <li class="nav-item"><a class="nav-link" href="/Pages/Mes_Reservations/mes_reservations.php">Réservations</a></li>
                <?php endif; ?>
                
                <li class="nav-item"><a class="nav-link" href="/Pages/Carte/carte.php">Carte</a></li>
                
                <!-- Contact (connectés seulement) -->
                <?php if (isset($_SESSION['utilisateur_id'])): ?>
                <li class="nav-item"><a class="nav-link" href="/Pages/Contact/contact.php">Contact</a></li>
                <?php endif; ?>
            </ul>

            <?php if (!empty($_SESSION['utilisateur_id'])): ?>
                <div class="d-flex align-items-center fs-5">
                    <a href="/Pages/Favoris/mes-favoris.php" class="text-dark me-3"><i class="bi bi-heart heart-icon"></i></a>
                    
                    <div class="profile-dropdown">
                        <div id="profileToggle">
                            <?php if (!empty($_SESSION['photo_profil'])): ?>
                                <img src="<?= htmlspecialchars($_SESSION['photo_profil']) ?>" alt="Profil" class="profile-pic" onerror="this.style.display='none';this.nextElementSibling.style.display='inline';">
                                <i class="bi bi-person-circle profile-icon-fallback" style="display:none;"></i>
                            <?php else: ?>
                                <i class="bi bi-person-circle profile-icon-fallback"></i>
                            <?php endif; ?>
                        </div>
                        
                        <div id="profileDropdownMenu" class="profile-dropdown-menu">
                            <div class="dropdown-header">
                                <p class="dropdown-header-name"><?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?></p>
                                <?php if (!empty($_SESSION['email'])): ?><p class="dropdown-header-email"><?= htmlspecialchars($_SESSION['email']) ?></p><?php endif; ?>
                            </div>
                            
                            <a href="/Pages/Profil/profil.php" class="dropdown-item-custom"><i class="bi bi-person"></i> Profil</a>
                            <a href="/Pages/Contact/mes_messages.php" class="dropdown-item-custom"><i class="bi bi-envelope"></i> Messages</a>
                            
                            <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <a href="/Pages/admin_dashboard.php" class="dropdown-item-custom"><i class="bi bi-gear"></i> Administration</a>
                            <?php endif; ?>
                            
                            <div class="dropdown-divider-custom"></div>
                            <a href="/Pages/Formulaires/deconnexion.php" class="dropdown-item-custom dropdown-logout"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <a href="/Pages/Formulaires/connexion.php" class="btn btn-primary btn-sm me-2">Connexion</a>
                <a href="/Pages/Formulaires/inscription.php" class="btn btn-outline-primary btn-sm">Inscription</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('profileToggle');
    const menu = document.getElementById('profileDropdownMenu');
    if (toggle && menu) {
        toggle.onclick = (e) => { e.stopPropagation(); menu.classList.toggle('show'); };
        document.onclick = (e) => { if (!toggle.contains(e.target) && !menu.contains(e.target)) menu.classList.remove('show'); };
        menu.onclick = (e) => e.stopPropagation();
    }
});
</script>

</body>
</html>
