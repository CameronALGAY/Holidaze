<?php
session_start();
require_once '../../include/db.php';
require_once __DIR__ . '/../Favoris/favoris_class.php';

// Validation et récupération de l'ID du bien
$id_bien = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id_bien) {
    die("<p class='text-center text-red-600 text-xl mt-20'>Aucun bien sélectionné.</p>");
}

// Récupération des informations du bien avec jointures
$sqlBien = "SELECT b.*, 
                   c.nom_commune, 
                   c.commune_latitude_deg as latitude_commune, 
                   c.commune_longitude_deg as longitude_commune, 
                   t.des_typebien, 
                   b.id_utilisateur_proprietaire
            FROM bien b
            JOIN commune c ON b.id_commune = c.id_commune
            JOIN type_bien t ON b.id_typebien = t.id_typebien
            WHERE b.id_bien = ?";

$stmt = $pdo->prepare($sqlBien);
$stmt->execute([$id_bien]);
$bien = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bien) {
    die("<p class='text-center text-red-600 text-xl mt-20'>Bien introuvable.</p>");
}

// Vérification des droits de modification
$isOwner = isset($_SESSION['utilisateur_id']) && $_SESSION['utilisateur_id'] == $bien['id_utilisateur_proprietaire'];
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$canEdit = $isOwner || $isAdmin;

// Récupération des photos du bien
$stmtPhotos = $pdo->prepare("SELECT lien_photo FROM photo WHERE id_bien = ?");
$stmtPhotos->execute([$id_bien]);
$photos = $stmtPhotos->fetchAll(PDO::FETCH_ASSOC);

// Récupération des réservations pour le calendrier
$stmtReservations = $pdo->prepare("SELECT date_debut, date_fin FROM reservation WHERE id_bien = ?");
$stmtReservations->execute([$id_bien]);
$reservations = $stmtReservations->fetchAll(PDO::FETCH_ASSOC);

// Formatage des événements pour FullCalendar
$events = [];
foreach ($reservations as $r) {
    $events[] = [
        'start' => $r['date_debut'],
        'end'   => date('Y-m-d', strtotime($r['date_fin'] . ' +1 day')),
        'backgroundColor' => '#ef4444',
        'borderColor'     => '#dc2626',
        'textColor'       => '#fff',
        'display'         => 'background'
    ];
}

// Récupération des tarifs par saison et semaine
$tarifsStmt = $pdo->prepare("SELECT t.semaine_tarif, 
                                     t.annee_tarif, 
                                     t.tarif, 
                                     t.id_tarif, 
                                     s.libelle_saison
                              FROM tarif t
                              JOIN saison s ON t.id_saison = s.id_saison
                              WHERE t.id_bien = ?
                              ORDER BY t.annee_tarif, t.semaine_tarif");
$tarifsStmt->execute([$id_bien]);
$tarifsData = $tarifsStmt->fetchAll(PDO::FETCH_ASSOC);

// Création d'un tableau associatif pour accès rapide (clé: Année-Semaine)
$tarifsMap = [];
$tarifsBySaison = [];

foreach ($tarifsData as $t) {
    $key = $t['annee_tarif'] . '-' . str_pad($t['semaine_tarif'], 2, '0', STR_PAD_LEFT);
    $tarifsMap[$key] = [
        'tarif' => (float)$t['tarif'],
        'id_tarif' => (int)$t['id_tarif']
    ];
    
    // Regroupement par saison pour l'affichage
    if (!isset($tarifsBySaison[$t['libelle_saison']])) {
        $tarifsBySaison[$t['libelle_saison']] = [
            'libelle' => $t['libelle_saison'],
            'min_tarif' => (float)$t['tarif'],
            'max_tarif' => (float)$t['tarif']
        ];
    } else {
        $tarifsBySaison[$t['libelle_saison']]['min_tarif'] = min(
            $tarifsBySaison[$t['libelle_saison']]['min_tarif'], 
            (float)$t['tarif']
        );
        $tarifsBySaison[$t['libelle_saison']]['max_tarif'] = max(
            $tarifsBySaison[$t['libelle_saison']]['max_tarif'], 
            (float)$t['tarif']
        );
    }
}

// Vérification de la majorité de l'utilisateur
$is_minor = false;
$age_check_message = '';

if (isset($_SESSION['utilisateur_id'])) {
    if (isset($_SESSION['utilisateur']['date_naissance']) && !empty($_SESSION['utilisateur']['date_naissance'])) {
        try {
            $date_naissance = new DateTime($_SESSION['utilisateur']['date_naissance']);
            $aujourdhui = new DateTime();
            $age = $aujourdhui->diff($date_naissance)->y;

            if ($age < 18) {
                $is_minor = true;
                $age_check_message = 'Vous devez être majeur pour pouvoir réserver un logement.';
            }
        } catch (Exception $e) {
            // En cas d'erreur de date, on considère comme mineur par sécurité
            $is_minor = true;
            $age_check_message = 'Erreur lors de la vérification de votre âge.';
        }
    } else {
        // Date de naissance non renseignée
        $is_minor = true;
        $age_check_message = 'Veuillez mettre à jour votre profil pour confirmer votre majorité.';
    }
}

// Récupération des prestations
$stmtPrestations = $pdo->prepare("SELECT p.libelle_prestation, sc.quantite 
                                   FROM prestation p
                                   INNER JOIN secompose sc ON p.id_prestation = sc.id_prestation
                                   WHERE sc.id_bien = ?
                                   ORDER BY p.libelle_prestation");
$stmtPrestations->execute([$id_bien]);
$prestations = $stmtPrestations->fetchAll(PDO::FETCH_ASSOC);

$favorisController = new FavorisController($pdo);
$estEnFavori = false;
if (isset($_SESSION['id_user'])) {
    $estEnFavori = $favorisController->estEnFavori($_SESSION['id_user'], $id_bien);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($bien['nom_bien']) ?> - Location de vacances à <?= htmlspecialchars($bien['nom_commune']) ?>">
    <title><?= htmlspecialchars($bien['nom_bien']) ?> | Location vacances</title>
    
    <!-- Bootstrap CSS (pour le footer) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Tailwind CSS (pour le contenu) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- FullCalendar -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales/fr.global.min.js'></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Leaflet (OpenStreetMap) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <style>
        /* Layout avec footer en bas */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        
        body {
            display: flex;
            flex-direction: column;
        }
        
        .main-content {
            flex: 1 0 auto;
        }
        
        footer {
            flex-shrink: 0;
        }

        /* Style pour la carte */
        #map {
            height: 400px;
            width: 100%;
            border-radius: 1rem;
            z-index: 1;
        }

        /* Animation pour le marker */
        .leaflet-marker-icon {
            animation: bounce 1s ease-in-out;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* Style pour le calendrier FullCalendar */
        #calendar {
            min-height: 400px;
        }

        /* Amélioration de la lightbox */
        #lightbox {
            backdrop-filter: blur(5px);
        }

        /* Boutons de navigation lightbox */
        .lightbox-nav-btn {
            background: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .lightbox-nav-btn:hover {
            background: rgba(0, 0, 0, 0.8);
            transform: scale(1.1);
        }

        /* Animations des images */
        .photo-grid img {
            transition: all 0.3s ease;
        }

        .photo-grid img:hover {
            transform: scale(1.02);
            filter: brightness(0.95);
        }

        /* Style pour les jours réservés dans le calendrier */
        .fc-day-disabled {
            background-color: #fee2e2 !important;
        }

        /* Responsive pour les petits écrans */
        @media (max-width: 768px) {
            #map {
                height: 300px;
            }
            
            #calendar {
                min-height: 350px;
            }
        }

        /* Animation de chargement */
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #7c3aed;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .heart-btn {
            background: linear-gradient(135deg, #ec4899 0%, #8b5cf6 100%);
            color: white;
        }
        .heart-btn:hover {
            transform: translateY(-2px);
        }
        .heart-btn.actif {
            background: linear-gradient(135deg, #f43f5e 0%, #c026d3 100%);
        }
        .heart-btn.actif i {
            animation: heartBeat 0.3s ease;
        }
        @keyframes heartBeat {
            0%, 100% { transform: scale(1); }
            25% { transform: scale(1.3); }
            50% { transform: scale(1.1); }
        }
    </style>
</head>
<body class="bg-gray-50">

<?php include '../header.php'; ?>

<div class="main-content">
    <div class="container mx-auto px-4 py-10 max-w-7xl">
        <div class="grid lg:grid-cols-[1.5fr,1fr] gap-10">
            <!-- Colonne gauche : photos + infos -->
            <div>
                <!-- En-tête avec titre et boutons d'édition -->
                <div class="flex justify-between items-start mb-6 flex-wrap gap-4">
                    <h1 class="text-4xl font-bold text-purple-600">
                        <?= htmlspecialchars($bien['nom_bien']) ?>
                    </h1>
                    <?php if ($canEdit): ?>
                        <div class="flex gap-3">
                            <a href="bien_form.php?edit=<?= $id_bien ?>" 
                               class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition flex items-center gap-2">
                                <i class="fas fa-edit"></i>
                                <span>Modifier</span>
                            </a>
                            <button onclick="deleteBien(<?= $id_bien ?>)" 
                                    class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition flex items-center gap-2">
                                <i class="fas fa-trash"></i>
                                <span>Supprimer</span>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

            <div class="flex items-center gap-4 mb-4">
    <h1 class="text-3xl font-bold"><?= htmlspecialchars($bien['nom_bien']) ?></h1>
    
    <?php if (isset($_SESSION['id_user'])): ?>
        <button 
            id="btn-favori"
            onclick="toggleFavori(<?= $id_bien ?>)"
            class="heart-btn flex items-center gap-2 px-4 py-2 rounded-lg font-semibold transition-all shadow-md hover:shadow-lg">
            <i class="fas fa-heart text-xl"></i>
            <span id="favori-text"><?= $estEnFavori ? 'Retirer des favoris' : 'Ajouter aux favoris' ?></span>
        </button>
    <?php else: ?>
        <a href="../Formulaires/connexion.php" 
           class="flex items-center gap-2 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition">
            <i class="far fa-heart text-xl"></i>
            <span>Connectez-vous pour sauvegarder</span>
        </a>
    <?php endif; ?>
</div>

                <!-- Grille de photos style Airbnb -->
                <div class="relative rounded-2xl overflow-hidden shadow-xl mb-8 photo-grid">
                    <?php if (count($photos) === 0): ?>
                        <!-- Aucune photo -->
                        <img src="../../Photo/uploads/default.jpg" 
                             alt="Photo par défaut" 
                             class="w-full h-96 object-cover">
                    
                    <?php elseif (count($photos) === 1): ?>
                        <!-- Une seule photo -->
                        <img src="../../<?= htmlspecialchars($photos[0]['lien_photo']) ?>" 
                             alt="Photo du bien"
                             class="w-full h-96 object-cover cursor-pointer hover:brightness-95 transition"
                             onclick="openLightbox(0)">
                    
                    <?php elseif (count($photos) === 2): ?>
                        <!-- Deux photos -->
                        <div class="grid grid-cols-2 gap-2 h-96">
                            <?php foreach ($photos as $index => $p): ?>
                                <img src="../../<?= htmlspecialchars($p['lien_photo']) ?>" 
                                     alt="Photo du bien <?= $index + 1 ?>"
                                     class="w-full h-full object-cover cursor-pointer hover:brightness-95 transition"
                                     onclick="openLightbox(<?= $index ?>)">
                            <?php endforeach; ?>
                        </div>
                    
                    <?php elseif (count($photos) === 3): ?>
                        <!-- Trois photos -->
                        <div class="grid grid-cols-2 gap-2 h-96">
                            <img src="../../<?= htmlspecialchars($photos[0]['lien_photo']) ?>" 
                                 alt="Photo principale"
                                 class="w-full h-full object-cover cursor-pointer hover:brightness-95 transition row-span-2"
                                 onclick="openLightbox(0)">
                            <img src="../../<?= htmlspecialchars($photos[1]['lien_photo']) ?>" 
                                 alt="Photo du bien 2"
                                 class="w-full h-full object-cover cursor-pointer hover:brightness-95 transition"
                                 onclick="openLightbox(1)">
                            <img src="../../<?= htmlspecialchars($photos[2]['lien_photo']) ?>" 
                                 alt="Photo du bien 3"
                                 class="w-full h-full object-cover cursor-pointer hover:brightness-95 transition"
                                 onclick="openLightbox(2)">
                        </div>
                    
                    <?php else: ?>
                        <!-- Quatre photos ou plus (style Airbnb) -->
                        <div class="grid grid-cols-4 grid-rows-2 gap-2 h-96">
                            <!-- Grande photo à gauche -->
                            <img src="../../<?= htmlspecialchars($photos[0]['lien_photo']) ?>" 
                                 alt="Photo principale"
                                 class="col-span-2 row-span-2 w-full h-full object-cover cursor-pointer hover:brightness-95 transition"
                                 onclick="openLightbox(0)">
                            
                            <!-- Petites photos à droite -->
                            <?php for ($i = 1; $i < min(5, count($photos)); $i++): ?>
                                <img src="../../<?= htmlspecialchars($photos[$i]['lien_photo']) ?>" 
                                     alt="Photo du bien <?= $i + 1 ?>"
                                     class="w-full h-full object-cover cursor-pointer hover:brightness-95 transition"
                                     onclick="openLightbox(<?= $i ?>)">
                            <?php endfor; ?>
                        </div>
                        
                        <!-- Bouton "Afficher toutes les photos" si plus de 5 photos -->
                        <?php if (count($photos) > 5): ?>
                            <button onclick="openLightbox(0)" 
                                    class="absolute bottom-4 right-4 bg-white px-4 py-2 rounded-lg shadow-lg font-semibold hover:bg-gray-100 transition flex items-center gap-2">
                                <i class="fas fa-th"></i>
                                <span>Afficher toutes les photos (<?= count($photos) ?>)</span>
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Détails du bien -->
                <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
                    <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                        <i class="fas fa-info-circle text-purple-600"></i>
                        Détails du bien
                    </h2>
                    <div class="grid md:grid-cols-2 gap-4 text-lg">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-map-marker-alt text-purple-600"></i>
                            <div>
                                <strong>Commune :</strong> 
                                <?= htmlspecialchars($bien['nom_commune']) ?>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <i class="fas fa-home text-purple-600"></i>
                            <div>
                                <strong>Type :</strong> 
                                <?= htmlspecialchars($bien['des_typebien']) ?>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <i class="fas fa-ruler-combined text-purple-600"></i>
                            <div>
                                <strong>Superficie :</strong> 
                                <?= number_format($bien['superficie_bien'], 0, ',', ' ') ?> m²
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <i class="fas fa-users text-purple-600"></i>
                            <div>
                                <strong>Couchages :</strong> 
                                <?= $bien['nb_couchage'] ?> personne<?= $bien['nb_couchage'] > 1 ? 's' : '' ?>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <i class="fas fa-paw text-purple-600"></i>
                            <div>
                                <strong>Animaux :</strong> 
                                <?= $bien['animaux_bien'] == '1' ? 'Acceptés' : 'Refusés' ?>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($bien['description_bien'])): ?>
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h3 class="font-bold text-lg mb-3">Description</h3>
                            <p class="text-gray-700 italic leading-relaxed">
                                "<?= nl2br(htmlspecialchars($bien['description_bien'])) ?>"
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Prestations -->
                <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
                    <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                        <i class="fas fa-star text-purple-600"></i>
                        Prestations
                    </h2>
                    <?php if (count($prestations) > 0): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($prestations as $p): ?>
                                <div class="flex items-center space-x-3 text-gray-700 bg-gray-50 p-3 rounded-lg">
                                    <i class="fas fa-check-circle text-green-500"></i>
                                    <span>
                                        <?= htmlspecialchars($p['libelle_prestation']) ?>
                                        <?php if ($p['quantite'] > 1): ?>
                                            <span class="text-sm text-gray-500">(×<?= $p['quantite'] ?>)</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500">Aucune prestation listée pour ce bien.</p>
                    <?php endif; ?>
                </div>

                <!-- Carte de localisation -->
                <div class="bg-white rounded-2xl shadow-lg p-8 mb-8">
                    <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                        <i class="fas fa-map text-purple-600"></i>
                        Localisation approximative
                    </h2>
                    <div id="map" class="rounded-xl shadow-inner mb-4"></div>
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded">
                        <p class="text-sm text-blue-800 flex items-start gap-2">
                            <i class="fas fa-info-circle mt-1"></i>
                            <span>L'adresse exacte du bien vous sera communiquée par message après la confirmation de votre réservation.</span>
                        </p>
                    </div>
                </div>
            </div>
            <!-- Colonne droite : Calendrier et Réservation -->
            <div class="lg:sticky lg:top-10 h-fit">
                <div class="bg-white rounded-2xl shadow-lg p-8">
                    <h2 class="text-2xl font-bold mb-6 flex items-center gap-2">
                        <i class="fas fa-calendar-alt text-purple-600"></i>
                        Disponibilités et Réservation
                    </h2>
                    
                    <?php if (!isset($_SESSION['utilisateur_id'])): ?>
                        <!-- Message pour utilisateur non connecté -->
                        <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4 rounded" role="alert">
                            <p class="font-bold flex items-center gap-2">
                                <i class="fas fa-lock"></i>
                                Connectez-vous
                            </p>
                            <p class="mt-2">Vous devez être connecté pour voir les disponibilités et réserver.</p>
                        </div>
                        <a href="../connexion.php" 
                           class="w-full block text-center bg-purple-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-purple-700 transition flex items-center justify-center gap-2">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Se connecter</span>
                        </a>
                    <?php else: ?>
                        <!-- Calendrier pour utilisateur connecté -->
                        <div id="calendar" class="mb-4"></div>
                        <p class="text-sm text-gray-500 mt-4 flex items-start gap-2">
                            <i class="fas fa-info-circle mt-1"></i>
                            <span>Cliquez et glissez sur le calendrier pour sélectionner vos dates.</span>
                        </p>
                        
                        <!-- Légende du calendrier -->
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="flex items-center gap-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 bg-red-500 rounded"></div>
                                    <span>Réservé</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-4 h-4 bg-green-500 rounded"></div>
                                    <span>Disponible</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Section Tarifs par saison -->
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                                <i class="fas fa-euro-sign text-purple-600"></i>
                                Tarifs par Saison
                            </h3>
                            <?php if (count($tarifsBySaison) > 0): ?>
                                <div class="space-y-3">
                                    <?php foreach ($tarifsBySaison as $saison): ?>
                                        <div class="bg-gray-50 p-3 rounded-lg">
                                            <div class="flex justify-between items-center">
                                                <span class="font-semibold text-gray-700">
                                                    <?= htmlspecialchars($saison['libelle']) ?>
                                                </span>
                                                <?php if ($saison['min_tarif'] == $saison['max_tarif']): ?>
                                                    <span class="font-bold text-purple-600">
                                                        <?= number_format($saison['min_tarif'], 2, ',', ' ') ?> €
                                                    </span>
                                                <?php else: ?>
                                                    <span class="font-bold text-purple-600">
                                                        <?= number_format($saison['min_tarif'], 2, ',', ' ') ?> - 
                                                        <?= number_format($saison['max_tarif'], 2, ',', ' ') ?> €
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-3 bg-blue-50 border-l-4 border-blue-500 p-3 rounded">
                                    <p class="text-sm text-blue-800 flex items-start gap-2">
                                        <i class="fas fa-info-circle mt-1"></i>
                                        <span>Les tarifs sont affichés à la semaine. Le prix de votre séjour sera calculé au prorata en fonction de la durée.</span>
                                    </p>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-600 italic">Aucun tarif saisonnier disponible pour ce bien.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODALE DE RÉSERVATION -->
<div id="confirm-modal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-8 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-bold">Confirmer la réservation</h3>
            <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <i class="fas fa-calendar text-purple-600"></i>
                    <p class="text-lg">
                        Période : <span id="modal-dates" class="font-bold"></span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-moon text-purple-600"></i>
                    <p class="text-lg">
                        Durée : <span id="modal-nights" class="font-bold text-green-600"></span> nuit<span id="modal-nights-plural">s</span>
                    </p>
                </div>
            </div>
            
            <div class="mt-4 pt-4 border-t border-gray-200">
                <p class="text-xl font-bold text-purple-600 flex items-center gap-2">
                    <i class="fas fa-euro-sign"></i>
                    Prix total : <span id="modal-price">-</span> €
                </p>
                <p class="text-sm text-gray-600 mt-1">
                    Prix moyen par nuit : <span id="modal-price-per-night">-</span> €
                </p>
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-lg font-medium mb-2 flex items-center gap-2">
                <i class="fas fa-users text-purple-600"></i>
                Nombre de voyageurs
            </label>
            <input type="number" 
                   id="nb-personnes" 
                   min="1" 
                   max="<?= $bien['nb_couchage'] ?>" 
                   value="1"
                   class="w-full p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-600 focus:border-transparent">
            <p class="text-sm text-gray-500 mt-1">
                Maximum : <?= $bien['nb_couchage'] ?> personne<?= $bien['nb_couchage'] > 1 ? 's' : '' ?>
            </p>
        </div>

        <!-- Message d'erreur si pas de tarif disponible -->
        <div id="price-error" class="hidden bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-4">
            <p class="flex items-center gap-2">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Aucun tarif disponible pour cette période</span>
            </p>
        </div>

        <!-- Message d'erreur pour les mineurs -->
        <div id="age-error-container" class="hidden mb-4">
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded">
                <p class="flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i>
                    <span id="age-error-message"></span>
                </p>
            </div>
        </div>

        <div class="flex gap-4">
            <button id="btn-confirm" 
                    class="flex-1 bg-green-600 text-white py-4 rounded-lg font-bold hover:bg-green-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                <i class="fas fa-check"></i>
                <span>Confirmer la réservation</span>
            </button>
            <button onclick="closeModal()"
                    class="flex-1 bg-gray-300 py-4 rounded-lg font-bold hover:bg-gray-400 transition flex items-center justify-center gap-2">
                <i class="fas fa-times"></i>
                <span>Annuler</span>
            </button>
        </div>
    </div>
</div>
<!-- LIGHTBOX POUR AFFICHER LES PHOTOS EN GRAND -->
<?php if (count($photos) > 0): ?>
<div id="lightbox" class="hidden fixed inset-0 bg-black/90 flex items-center justify-center z-50">
    <!-- Bouton fermer -->
    <button onclick="closeLightbox()" 
            class="lightbox-nav-btn absolute top-4 right-4 text-white text-3xl w-12 h-12 flex items-center justify-center z-10"
            aria-label="Fermer la galerie">
        <i class="fas fa-times"></i>
    </button>
    
    <!-- Bouton précédent -->
    <button onclick="prevPhoto()" 
            class="lightbox-nav-btn absolute left-4 text-white text-4xl w-16 h-16 flex items-center justify-center z-10"
            aria-label="Photo précédente">
        <i class="fas fa-chevron-left"></i>
    </button>
    
    <!-- Bouton suivant -->
    <button onclick="nextPhoto()" 
            class="lightbox-nav-btn absolute right-4 text-white text-4xl w-16 h-16 flex items-center justify-center z-10"
            aria-label="Photo suivante">
        <i class="fas fa-chevron-right"></i>
    </button>
    
    <!-- Image principale -->
    <div class="flex items-center justify-center w-full h-full p-4">
        <img id="lightbox-img" 
             src="" 
             alt="Photo en grand" 
             class="max-w-full max-h-[90vh] object-contain">
    </div>
    
    <!-- Compteur de photos -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 bg-black/60 text-white px-6 py-3 rounded-full text-lg">
        <span id="lightbox-counter"></span>
    </div>
    
    <!-- Miniatures -->
    <div class="absolute bottom-20 left-1/2 transform -translate-x-1/2 flex gap-2 overflow-x-auto max-w-[90vw] p-2">
        <?php foreach ($photos as $index => $p): ?>
            <img src="../../<?= htmlspecialchars($p['lien_photo']) ?>" 
                 alt="Miniature <?= $index + 1 ?>"
                 class="thumbnail w-16 h-16 object-cover rounded cursor-pointer opacity-50 hover:opacity-100 transition"
                 data-index="<?= $index ?>"
                 onclick="openLightbox(<?= $index ?>)">
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php include '../footer.php'; ?>

<!-- Scripts JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // ==========================================
    // VARIABLES GLOBALES
    // ==========================================
    
    // Variables pour la sélection du calendrier
    let selectedStart = null;
    let selectedEnd = null;
    let calculatedPrice = 0;
    let selectedTarifId = null;
    let calendar = null;

    // Tarifs récupérés depuis PHP
    const tarifsMap = <?= json_encode($tarifsMap) ?>;

    // Vérification de l'âge
    const isMinor = <?= json_encode($is_minor) ?>;
    const ageCheckMessage = <?= json_encode($age_check_message) ?>;

    // Variables pour la lightbox
    <?php if (count($photos) > 0): ?>
    const photos = <?= json_encode(array_map(function($p) { 
        return '../../' . $p['lien_photo']; 
    }, $photos)) ?>;
    let currentPhotoIndex = 0;
    <?php endif; ?>

    // ==========================================
    // FONCTIONS UTILITAIRES
    // ==========================================
    
    /**
     * Obtenir le numéro de semaine ISO 8601
     */
    function getWeekNumber(date) {
        const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
        const dayNum = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() + 4 - dayNum);
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        const weekNo = Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
        return weekNo;
    }

    /**
     * Calculer le prix total du séjour
     */
    function calculateTotalPrice(startDate, endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        
        // Calculer le nombre total de jours
        const totalDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
        
        // Obtenir le tarif de base de la première nuit pour déterminer la saison
        const year = start.getFullYear();
        let week = getWeekNumber(start);
        
        // Ajuster l'année si nécessaire
        let adjustedYear = year;
        if (week === 1 && start.getMonth() === 11) {
            adjustedYear = year + 1;
        } else if (week >= 52 && start.getMonth() === 0) {
            adjustedYear = year - 1;
        }
        
        const weekStr = String(week).padStart(2, '0');
        const key = `${adjustedYear}-${weekStr}`;
        
        // Vérifier si le tarif existe pour cette période
        if (!tarifsMap[key]) {
            console.warn(`Aucun tarif trouvé pour la semaine ${weekStr} de l'année ${adjustedYear}`);
            return { totalPrice: 0, tarifFound: null };
        }
        
        // Récupérer le prix de base par saison
        const prixBaseSaison = parseFloat(tarifsMap[key].tarif);
        const tarifId = tarifsMap[key].id_tarif;
        
        // Calcul simple : Prix de base par saison × Nombre de jours
        const totalPrice = prixBaseSaison * totalDays;

        return { 
            totalPrice: Math.round(totalPrice * 100) / 100, 
            tarifFound: tarifId 
        };
    }

    /**
     * Fermer la modale de réservation
     */
    function closeModal() {
        document.getElementById('confirm-modal').classList.add('hidden');
        // Réinitialiser la sélection dans le calendrier
        if (calendar) {
            calendar.unselect();
        }
    }

    // ==========================================
    // FONCTIONS LIGHTBOX
    // ==========================================
    
    <?php if (count($photos) > 0): ?>
    /**
     * Ouvrir la lightbox à un index donné
     */
    function openLightbox(index) {
        currentPhotoIndex = index;
        document.getElementById('lightbox').classList.remove('hidden');
        updateLightboxImage();
    }

    /**
     * Fermer la lightbox
     */
    function closeLightbox() {
        document.getElementById('lightbox').classList.add('hidden');
    }

    /**
     * Photo précédente
     */
    function prevPhoto() {
        currentPhotoIndex = (currentPhotoIndex - 1 + photos.length) % photos.length;
        updateLightboxImage();
    }

    /**
     * Photo suivante
     */
    function nextPhoto() {
        currentPhotoIndex = (currentPhotoIndex + 1) % photos.length;
        updateLightboxImage();
    }

    /**
     * Mettre à jour l'image de la lightbox
     */
    function updateLightboxImage() {
        const imgElement = document.getElementById('lightbox-img');
        imgElement.src = photos[currentPhotoIndex];
        document.getElementById('lightbox-counter').textContent = 
            `${currentPhotoIndex + 1} / ${photos.length}`;
        
        // Mettre à jour l'opacité des miniatures
        document.querySelectorAll('.thumbnail').forEach((thumb, index) => {
            if (index === currentPhotoIndex) {
                thumb.classList.remove('opacity-50');
                thumb.classList.add('opacity-100', 'ring-2', 'ring-white');
            } else {
                thumb.classList.add('opacity-50');
                thumb.classList.remove('opacity-100', 'ring-2', 'ring-white');
            }
        });
    }

    /**
     * Gestion du clavier pour la lightbox
     */
    document.addEventListener('keydown', function(e) {
        const lightbox = document.getElementById('lightbox');
        if (!lightbox.classList.contains('hidden')) {
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') prevPhoto();
            if (e.key === 'ArrowRight') nextPhoto();
        }
    });
    <?php endif; ?>

    /**
     * Fonction pour supprimer un bien
     */
    function deleteBien(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce bien ? Cette action est irréversible.')) {
            window.location.href = 'bien_delete.php?id=' + id;
        }
    }
    // ==========================================
    // INITIALISATION AU CHARGEMENT DE LA PAGE
    // ==========================================
    
    document.addEventListener('DOMContentLoaded', function () {
        
        // ==========================================
        // INITIALISATION DE LA CARTE OPENSTREETMAP
        // ==========================================
        
        const latitude = <?= $bien['latitude_commune'] ?? 44.6372 ?>;
        const longitude = <?= $bien['longitude_commune'] ?? -1.0804 ?>;
        const communeName = "<?= htmlspecialchars($bien['nom_commune']) ?>";
        const bienName = "<?= htmlspecialchars($bien['nom_bien']) ?>";

        // Initialiser la carte
        const map = L.map('map').setView([latitude, longitude], 13);

        // Ajouter le layer OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(map);

        // Ajouter un cercle pour indiquer la zone approximative (confidentialité)
        L.circle([latitude, longitude], {
            color: '#7c3aed',
            fillColor: '#a78bfa',
            fillOpacity: 0.2,
            radius: 1500 // Rayon de 1.5km pour approximation
        }).addTo(map).bindPopup(`
            <div class="text-center">
                <strong>${bienName}</strong><br>
                <span class="text-sm text-gray-600">Zone approximative de ${communeName}</span>
            </div>
        `);

        // ==========================================
        // INITIALISATION DU CALENDRIER FULLCALENDAR
        // ==========================================
        
        <?php if (isset($_SESSION['utilisateur_id'])): ?>
        const calendarEl = document.getElementById('calendar');

        calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'fr',
            initialView: 'dayGridMonth',
            headerToolbar: { 
                left: 'prev,next today', 
                center: 'title', 
                right: 'dayGridMonth' 
            },
            selectable: true,
            selectMirror: true,
            selectOverlap: false,
            unselectAuto: true,
            
            // Empêcher la sélection sur des dates déjà réservées
            selectAllow: function(selectInfo) {
                const start = new Date(selectInfo.start);
                const end = new Date(selectInfo.end);
                
                // Vérifier si la période chevauche une réservation
                const reservations = <?= json_encode($reservations) ?>;
                for (let res of reservations) {
                    const resStart = new Date(res.date_debut);
                    const resEnd = new Date(res.date_fin);
                    
                    if ((start >= resStart && start <= resEnd) || 
                        (end > resStart && end <= resEnd) ||
                        (start <= resStart && end >= resEnd)) {
                        return false; // Empêcher la sélection
                    }
                }
                return true;
            },
            
            // Gestion de la sélection de dates
            select: function(info) {
                selectedStart = info.startStr;
                selectedEnd = info.endStr;

                const debut = new Date(info.start);
                const fin = new Date(info.end);
                
                // Le nombre de nuits est la différence entre les deux dates
                const nights = Math.ceil((fin - debut) / (1000 * 60 * 60 * 24));
                
                // La date de fin affichée est la veille du départ
                const dateFinAffichee = new Date(fin);
                dateFinAffichee.setDate(dateFinAffichee.getDate() - 1);

                // Calculer le prix
                const priceResult = calculateTotalPrice(selectedStart, info.endStr);
                calculatedPrice = priceResult.totalPrice;
                selectedTarifId = priceResult.tarifFound;

                // Mettre à jour la modale
                document.getElementById('modal-dates').textContent =
                    debut.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' }) + 
                    ' → ' + 
                    dateFinAffichee.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
                
                document.getElementById('modal-nights').textContent = nights;
                
                // Gérer le pluriel de "nuit(s)"
                const nightsPlural = document.getElementById('modal-nights-plural');
                if (nights <= 1) {
                    nightsPlural.textContent = '';
                } else {
                    nightsPlural.textContent = 's';
                }

                // Afficher le prix
                const priceElement = document.getElementById('modal-price');
                const pricePerNightElement = document.getElementById('modal-price-per-night');
                const priceError = document.getElementById('price-error');
                const btnConfirm = document.getElementById('btn-confirm');

                if (calculatedPrice > 0) {
                    priceElement.textContent = calculatedPrice.toFixed(2);
                    const avgPricePerNight = calculatedPrice / nights;
                    pricePerNightElement.textContent = avgPricePerNight.toFixed(2);
                    priceError.classList.add('hidden');
                    btnConfirm.disabled = false;
                } else {
                    priceElement.textContent = 'Non disponible';
                    pricePerNightElement.textContent = '-';
                    priceError.classList.remove('hidden');
                    btnConfirm.disabled = true;
                }

                // Afficher la modale
                document.getElementById('confirm-modal').classList.remove('hidden');
            },
            
            // Événements (réservations existantes)
            events: <?= json_encode($events) ?>,
            
            // Configuration visuelle
            dayMaxEvents: true,
            height: 'auto',
            
            // Style des jours passés
            dayCellDidMount: function(info) {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (info.date < today) {
                    info.el.classList.add('fc-day-disabled');
                    info.el.style.backgroundColor = '#f3f4f6';
                }
            }
        });
        
        calendar.render();
        
        // Forcer le redimensionnement après un court délai
        setTimeout(() => {
            calendar.updateSize();
        }, 100);
        <?php endif; ?>

        // ==========================================
        // GESTION DE LA VÉRIFICATION D'ÂGE
        // ==========================================
        
        const btnConfirm = document.getElementById('btn-confirm');
        const ageErrorContainer = document.getElementById('age-error-container');
        const ageErrorMessage = document.getElementById('age-error-message');

        if (isMinor) {
            // Afficher le message d'erreur pour les mineurs
            if (ageErrorContainer && ageErrorMessage) {
                ageErrorContainer.classList.remove('hidden');
                ageErrorMessage.textContent = ageCheckMessage;
            }
            
            // Désactiver le bouton de confirmation
            if (btnConfirm) {
                btnConfirm.disabled = true;
                btnConfirm.classList.add('cursor-not-allowed', 'opacity-50');
                btnConfirm.innerHTML = '<i class="fas fa-ban"></i><span>Réservation non autorisée</span>';
            }
        }

        // ==========================================
        // GESTION DE LA CONFIRMATION DE RÉSERVATION
        // ==========================================
        
        <?php if (isset($_SESSION['utilisateur_id'])): ?>
        if (btnConfirm) {
            btnConfirm.addEventListener('click', function() {
                // Si l'utilisateur est mineur, ne rien faire
                if (isMinor) {
                    alert(ageCheckMessage);
                    return;
                }

                const personnesInput = document.getElementById('nb-personnes');
                const personnes = parseInt(personnesInput.value);
                const maxPersonnes = parseInt(personnesInput.max);

                // Validations
                if (!selectedStart || !selectedEnd) {
                    alert('Aucune date sélectionnée. Veuillez sélectionner des dates sur le calendrier.');
                    return;
                }

                if (isNaN(personnes) || personnes < 1) {
                    alert('Le nombre de voyageurs doit être au moins 1.');
                    personnesInput.focus();
                    return;
                }

                if (personnes > maxPersonnes) {
                    alert(`Le nombre maximum de voyageurs pour ce bien est ${maxPersonnes}.`);
                    personnesInput.focus();
                    return;
                }

                if (calculatedPrice <= 0) {
                    alert('Aucun tarif disponible pour cette période. Veuillez sélectionner d\'autres dates.');
                    return;
                }

                if (!selectedTarifId) {
                    alert('Erreur: impossible de déterminer le tarif. Veuillez réessayer.');
                    return;
                }

                // Désactiver le bouton pendant l'envoi
                btnConfirm.disabled = true;
                btnConfirm.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Réservation en cours...</span>';

                // Envoi AJAX
                fetch('../../ajax/ajax_reserver_bien.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        id_bien: <?= $id_bien ?>,
                        date_debut: selectedStart,
                        date_fin: selectedEnd,
                        nb_personnes: personnes,
                        prix_total: calculatedPrice,
                        id_tarif: selectedTarifId
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur réseau');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('✓ Réservation confirmée pour un montant de ' + calculatedPrice.toFixed(2) + ' € !');
                        location.reload();
                    } else {
                        alert('✗ Erreur : ' + (data.message || 'Une erreur est survenue'));
                        btnConfirm.disabled = false;
                        btnConfirm.innerHTML = '<i class="fas fa-check"></i><span>Confirmer la réservation</span>';
                    }
                })
                .catch(err => {
                    console.error('Erreur:', err);
                    alert('✗ Erreur réseau : ' + err.message);
                    btnConfirm.disabled = false;
                    btnConfirm.innerHTML = '<i class="fas fa-check"></i><span>Confirmer la réservation</span>';
                });
            });
        }
        <?php endif; ?>
    });

    let estEnFavori = <?= json_encode($estEnFavori) ?>;

function toggleFavori(idBien) {
    const btn = document.getElementById('btn-favori');
    const text = document.getElementById('favori-text');
    const icon = btn.querySelector('i');
    
    // Animation immédiate
    icon.style.transform = 'scale(1.3)';
    setTimeout(() => icon.style.transform = 'scale(1)', 300);
    
    fetch('../Pages/favoris_action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=toggle&id_bien=' + idBien
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            estEnFavori = data.estEnFavori;
            
            if (estEnFavori) {
                btn.classList.add('actif');
                text.textContent = 'Retirer des favoris';
                icon.classList.remove('far');
                icon.classList.add('fas');
                
                // Notification succès
                showNotification('✓ Ajouté aux favoris !', 'success');
            } else {
                btn.classList.remove('actif');
                text.textContent = 'Ajouter aux favoris';
                icon.classList.remove('fas');
                icon.classList.add('far');
                
                showNotification('Retiré des favoris', 'info');
            }
        } else {
            showNotification('Erreur : ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showNotification('Une erreur est survenue', 'error');
    });
}

function showNotification(message, type) {
    const notif = document.createElement('div');
    notif.className = `fixed top-20 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transition-all transform translate-x-0 ${
        type === 'success' ? 'bg-green-500' :
        type === 'error' ? 'bg-red-500' : 'bg-blue-500'
    } text-white font-semibold`;
    notif.textContent = message;
    document.body.appendChild(notif);
    
    setTimeout(() => {
        notif.style.transform = 'translateX(400px)';
        notif.style.opacity = '0';
        setTimeout(() => notif.remove(), 300);
    }, 2500);
}

// Initialiser l'état du bouton au chargement
if (estEnFavori) {
    document.getElementById('btn-favori')?.classList.add('actif');
}
</script>

</body>
</html>