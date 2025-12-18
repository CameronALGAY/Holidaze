<?php
// mes-favoris.php
session_start();
require_once dirname(dirname(__DIR__)) . '/include/db.php';
require_once __DIR__ . '/favoris_class.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: ../Formulaires/connexion.php');
    exit;
}

$idUser = $_SESSION['utilisateur_id'];
$favorisController = new FavorisController($pdo);

// Récupérer tous les favoris
$favoris = $favorisController->getFavorisByUser($idUser);
$nbFavoris = count($favoris);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Favoris - Holidaze</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" href="../Photo/icon.png" type="image/png">
    <style>
        .badge-note {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }
        .line-clamp-1 {
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .stars {
            font-size: 0.875rem;
            line-height: 1;
        }
        .heart-btn {
            transition: all 0.3s ease;
        }
        .heart-btn:hover {
            transform: scale(1.1);
        }
        .empty-state {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="font-sans text-gray-800 bg-gray-50 antialiased">

    <?php include '../header.php'; ?>

    <!-- HEADER SECTION -->
    <section class="bg-gradient-to-br from-pink-600 to-purple-700 text-white py-16 px-4">
        <div class="max-w-6xl mx-auto">
            <div class="flex items-center gap-3 mb-4">
                <i class="fas fa-heart text-4xl"></i>
                <h1 class="text-4xl md:text-5xl font-bold">Mes Favoris</h1>
            </div>
            <p class="text-xl opacity-90">
                <?php if ($nbFavoris > 0): ?>
                    Vous avez <span class="font-bold"><?= $nbFavoris ?></span> bien<?= $nbFavoris > 1 ? 's' : '' ?> sauvegardé<?= $nbFavoris > 1 ? 's' : '' ?>
                <?php else: ?>
                    Vous n'avez pas encore de favoris
                <?php endif; ?>
            </p>
        </div>
    </section>

    <!-- CONTENU PRINCIPAL -->
    <div class="container max-w-6xl mx-auto px-4 py-12">
        
        <?php if ($nbFavoris > 0): ?>
            <!-- GRILLE DES FAVORIS -->
            <div class="listings-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                <?php foreach ($favoris as $bien): ?>
                    <?php 
                    $photos = $favorisController->getPhotosByBienId($bien['id_bien']);
                    $firstPhoto = $photos && count($photos) > 0 ? $photos[0] : null;
                    $tarifs = $favorisController->getTarifsByBienId($bien['id_bien']);
                    $prixNuit = $tarifs && count($tarifs) > 0 ? min(array_column($tarifs, 'tarif')) : null;

                    // Calcul moyenne avis
                    $avgNote = null;
                    $nbAvis = 0;
                    $sqlAvg = "SELECT AVG(note) as moyenne, COUNT(*) as total FROM avis WHERE id_bien = ?";
                    $stmtAvg = $pdo->prepare($sqlAvg);
                    $stmtAvg->execute([$bien['id_bien']]);
                    $result = $stmtAvg->fetch(PDO::FETCH_ASSOC);
                    $avgNote = $result['moyenne'] ? round($result['moyenne'], 1) : null;
                    $nbAvis = (int)$result['total'];
                    ?>

                    <div class="listing-card block rounded-xl overflow-hidden shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 flex flex-col h-full relative">
                        
                        <!-- Bouton Retirer des favoris -->
                        <button 
                            onclick="retirerFavori(<?= $bien['id_bien'] ?>)"
                            class="heart-btn absolute top-3 left-3 z-20 bg-white text-red-500 w-10 h-10 rounded-full flex items-center justify-center shadow-lg hover:bg-red-50">
                            <i class="fas fa-heart text-xl"></i>
                        </button>

                        <a href="../Bien/bien_detail.php?id=<?= $bien['id_bien'] ?>" class="flex flex-col h-full">
                            <!-- Image -->
                            <div class="listing-image relative h-48 bg-gray-200 overflow-hidden">
                                <?php if ($firstPhoto): ?>
                                    <img src="/<?= htmlspecialchars($firstPhoto['lien_photo']) ?>" 
                                         alt="<?= htmlspecialchars($firstPhoto['nom_photo']) ?>" 
                                         class="w-full h-full object-cover transition-transform duration-500 hover:scale-110">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center">
                                        <i class="fas fa-home text-4xl text-gray-400"></i>
                                    </div>
                                <?php endif; ?>

                                <!-- Badge note -->
                                <?php if ($avgNote !== null): ?>
                                    <span class="badge-note absolute top-2 right-2 text-white px-2.5 py-1 rounded-full text-xs font-bold flex items-center gap-1">
                                        <i class="fas fa-star text-yellow-400 text-sm"></i>
                                        <?= number_format($avgNote, 1) ?>
                                        <?php if ($nbAvis > 0): ?>
                                            <span class="ml-1 text-xs opacity-80">(<?= $nbAvis ?>)</span>
                                        <?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="absolute top-2 right-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white px-2.5 py-1 rounded-full text-xs font-bold">
                                        Nouveau
                                    </span>
                                <?php endif; ?>

                                <!-- Badge animaux -->
                                <?php if ($bien['animaux_bien']): ?>
                                    <span class="absolute bottom-2 left-2 bg-green-500 text-white px-2 py-1 rounded-full text-xs font-medium">
                                        <i class="fas fa-paw"></i> Animaux OK
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Contenu -->
                            <div class="listing-content p-4 bg-white flex flex-col flex-1">
                                <div class="flex-1">
                                    <h3 class="listing-title text-lg font-semibold text-gray-800 line-clamp-1">
                                        <?= htmlspecialchars($bien['nom_bien']) ?>
                                    </h3>
                                    <p class="listing-location text-sm text-gray-600 flex items-center gap-1 mt-1">
                                        <i class="fas fa-map-marker-alt text-gray-400"></i>
                                        <?= htmlspecialchars($bien['nom_commune']) ?>
                                        <?php if (!empty($bien['cp_commune'])): ?>
                                            <span class="text-gray-500">(<?= htmlspecialchars($bien['cp_commune']) ?>)</span>
                                        <?php endif; ?>
                                    </p>
                                    
                                    <!-- Date d'ajout aux favoris -->
                                    <p class="text-xs text-gray-400 mt-2">
                                        <i class="fas fa-clock"></i>
                                        Ajouté le <?= date('d/m/Y', strtotime($bien['date_favori'])) ?>
                                    </p>
                                </div>

                                <!-- Footer -->
                                <div class="mt-auto">
                                    <div class="listing-footer flex justify-between items-center mt-3">
                                        <div class="rating flex items-center text-sm text-gray-600">
                                            <span class="stars text-yellow-400 mr-1">★★★★★</span>
                                            <span class="text-xs">(<?= $nbAvis ?> avis)</span>
                                        </div>
                                        <?php if ($prixNuit !== null): ?>
                                            <div class="price text-lg font-bold text-blue-600">
                                                €<?= number_format($prixNuit, 0) ?>
                                                <span class="text-sm font-normal text-gray-600">/nuit</span>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-sm text-gray-500 italic">Prix sur demande</div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Infos rapides -->
                                    <div class="flex gap-3 text-xs text-gray-500 mt-2">
                                        <span><i class="fas fa-ruler-combined"></i> <?= $bien['superficie_bien'] ?> m²</span>
                                        <span><i class="fas fa-bed"></i> <?= $bien['nb_couchage'] ?> couch.</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- ÉTAT VIDE -->
            <div class="empty-state text-center py-20">
                <div class="inline-block p-8 bg-gradient-to-br from-pink-100 to-purple-100 rounded-full mb-6">
                    <i class="fas fa-heart-broken text-6xl text-pink-400"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-800 mb-3">Aucun favori pour le moment</h2>
                <p class="text-gray-600 mb-8 max-w-md mx-auto">
                    Commencez à explorer nos locations et ajoutez vos coups de cœur en cliquant sur l'icône cœur
                </p>
                <a href="../index.php" 
                   class="inline-flex items-center gap-2 bg-gradient-to-r from-pink-600 to-purple-700 text-white px-8 py-3 rounded-lg font-semibold hover:from-pink-700 hover:to-purple-800 transition shadow-lg">
                    <i class="fas fa-search"></i>
                    Découvrir les locations
                </a>
            </div>
        <?php endif; ?>

    </div>

    <!-- FOOTER -->
    <?php include '../footer.php'; ?>

    <script>
        function retirerFavori(idBien) {
            if (confirm('Retirer ce bien de vos favoris ?')) {
                fetch('favoris_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=retirer&id_bien=' + idBien
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur lors de la suppression');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la suppression');
                });
            }
        }
    </script>

</body>
</html>