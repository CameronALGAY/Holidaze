<?php
session_start();
require_once '../../include/db.php';

// Récupérer tous les biens avec leurs coordonnées
$stmt = $pdo->query("
    SELECT 
        b.id_bien,
        b.nom_bien,
        b.description_bien,
        b.rue_bien,
        b.com_bien,
        b.superficie_bien,
        b.animaux_bien,
        b.nb_couchage,
        c.nom_commune,
        c.cp_commune,
        c.commune_latitude_deg,
        c.commune_longitude_deg,
        tb.des_typebien,
        (SELECT p.lien_photo 
         FROM photo p 
         WHERE p.id_bien = b.id_bien 
         LIMIT 1) as photo
    FROM bien b
    INNER JOIN commune c ON b.id_commune = c.id_commune
    LEFT JOIN type_bien tb ON b.id_typebien = tb.id_typebien
    WHERE c.commune_latitude_deg IS NOT NULL 
    AND c.commune_longitude_deg IS NOT NULL
    ORDER BY b.id_bien DESC
");
$biens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convertir en JSON pour JavaScript
$biensJSON = json_encode($biens);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte des biens - Holidaze</title>
    <meta name="description" content="Carte interactive des biens Holidaze avec zones approximatives et acces rapide aux annonces.">
    <link rel="canonical" href="<?php
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        echo $scheme . '://' . $_SERVER['HTTP_HOST'] . '/Pages/Carte/carte.php';
    ?>">
    <meta property="og:title" content="Carte des biens - Holidaze">
    <meta property="og:description" content="Explorez les biens disponibles sur une carte interactive Holidaze.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        echo $scheme . '://' . $_SERVER['HTTP_HOST'] . '/Pages/Carte/carte.php';
    ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        body {
            overflow: hidden;
        }
        
        #map {
            height: calc(100vh - 70px);
            width: 100%;
        }
        
        .leaflet-popup-content {
            margin: 0;
            padding: 0;
            width: 280px !important;
        }
        
        .popup-card {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .popup-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .popup-body {
            padding: 15px;
        }
        
        .popup-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .popup-location {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .popup-details {
            display: flex;
            gap: 15px;
            font-size: 12px;
            color: #888;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        
        .popup-detail-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .popup-type {
            font-size: 12px;
            color: #667eea;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .popup-btn {
            display: block;
            width: 100%;
            padding: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff !important;
            text-align: center;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .popup-btn:hover {
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.4);
        }
        
        .no-image {
            width: 100%;
            height: 150px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }
        
        .map-controls {
            position: absolute;
            bottom: 20px;
            left: 20px;
            z-index: 1000;
            background: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .map-stats {
            font-size: 14px;
            color: #666;
        }
        
        .map-stats strong {
            color: #2563eb;
            font-size: 18px;
        }

        .map-legend {
            position: absolute;
            top: 90px;
            right: 20px;
            z-index: 1000;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            font-size: 13px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }

        .legend-circle {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: rgba(37, 99, 235, 0.3);
            border: 2px solid #2563eb;
        }

        /* Style pour les cercles de zone */
        .zone-circle {
            cursor: pointer;
        }
        
        .zone-circle:hover {
            opacity: 0.8;
        }

        .privacy-note {
            font-size: 11px;
            color: #888;
            font-style: italic;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>

    <h1 class="visually-hidden">Carte des biens Holidaze</h1>

    <!-- Info sur le nombre de biens -->
    <div class="map-controls">
        <div class="map-stats">
            <i class="bi bi-geo-alt-fill text-primary"></i>
            <strong><?= count($biens) ?></strong> bien<?= count($biens) > 1 ? 's' : '' ?> disponible<?= count($biens) > 1 ? 's' : '' ?>
        </div>
    </div>

    <!-- Légende -->
    <div class="map-legend">
        <div class="legend-item">
            <div class="legend-circle"></div>
            <span>Zone approximative</span>
        </div>
        <div class="privacy-note">
            <i class="bi bi-shield-check"></i> L'adresse exacte n'est pas affichée par mesure de confidentialité
        </div>
    </div>

    <!-- Carte -->
    <div id="map"></div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // Données des biens depuis PHP
        const biens = <?= $biensJSON ?>;
        
        // Initialiser la carte centrée sur la France
        const map = L.map('map').setView([46.603354, 1.888334], 6);
        
        // Ajouter le fond de carte OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);
        
        // Ajouter un cercle pour chaque bien
        biens.forEach(bien => {
            // Coordonnées approximatives (décalées aléatoirement)
            const lat = parseFloat(bien.commune_latitude_deg);
            const lng = parseFloat(bien.commune_longitude_deg);
            
            // Créer un cercle représentant la zone approximative
            const circle = L.circle([lat, lng], {
                color: '#2563eb',
                fillColor: '#2563eb',
                fillOpacity: 0.3,
                radius: 800, // Rayon de 800 mètres
                className: 'zone-circle'
            }).addTo(map);
            
            // Créer le contenu du popup
            const imageHtml = bien.photo 
                ? `<img src="/../../${bien.photo}" alt="${bien.nom_bien}" loading="lazy" class="popup-image" onerror="this.parentElement.innerHTML='<div class=\\'no-image\\'><i class=\\'bi bi-house-door\\'></i></div>'">`
                : `<div class="no-image"><i class="bi bi-house-door"></i></div>`;
            
            const typeHtml = bien.des_typebien 
                ? `<div class="popup-type"><i class="bi bi-tag"></i> ${bien.des_typebien}</div>` 
                : '';
            
            const animauxIcon = bien.animaux_bien === 'oui' || bien.animaux_bien === '1' 
                ? '<i class="bi bi-check-circle-fill text-success" title="Animaux acceptés"></i>' 
                : '<i class="bi bi-x-circle-fill text-danger" title="Animaux non acceptés"></i>';
            
            const popupContent = `
                <div class="popup-card">
                    ${imageHtml}
                    <div class="popup-body">
                        <h3 class="popup-title">${bien.nom_bien}</h3>
                        ${typeHtml}
                        <div class="popup-location">
                            <i class="bi bi-geo-alt"></i>
                            ${bien.nom_commune}${bien.cp_commune ? ', ' + bien.cp_commune : ''}
                        </div>
                        <div class="popup-details">
                            <span class="popup-detail-item">
                                <i class="bi bi-rulers"></i>
                                ${bien.superficie_bien} m²
                            </span>
                            <span class="popup-detail-item">
                                <i class="bi bi-moon-stars"></i>
                                ${bien.nb_couchage} couchage${parseInt(bien.nb_couchage) > 1 ? 's' : ''}
                            </span>
                            <span class="popup-detail-item" title="Animaux">
                                ${animauxIcon}
                            </span>
                        </div>
                        <div style="font-size: 11px; color: #888; margin-bottom: 10px; font-style: italic;">
                            <i class="bi bi-info-circle"></i> Zone approximative - L'adresse exacte sera communiquée lors de la réservation
                        </div>
                        <a href="/Pages/Bien/bien_detail.php?id=${bien.id_bien}" class="popup-btn">
                            <i class="bi bi-eye me-2"></i>Voir le bien
                        </a>
                    </div>
                </div>
            `;
            
            // Associer le popup au cercle
            circle.bindPopup(popupContent, {
                maxWidth: 280,
                className: 'custom-popup'
            });
            
            // Ouvrir le popup au clic et centrer
            circle.on('click', function() {
                map.setView([lat, lng], 13);
            });
        });
        
        // Si on a des biens, ajuster la vue pour tous les afficher
        if (biens.length > 0) {
            const bounds = L.latLngBounds(
                biens.map(bien => [parseFloat(bien.commune_latitude_deg), parseFloat(bien.commune_longitude_deg)])
            );
            map.fitBounds(bounds.pad(0.1));
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>