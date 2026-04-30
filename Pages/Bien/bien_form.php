<?php
include '../header.php';
require_once '../../include/db.php';
require_once '../../include/csrf.php';
require_once 'bien_class.php';
require_once '../Communes/communes_class.php';
require_once '../TypeBien/typebien_class.php';
require_once '../Tarifs/tarifs_class.php';

$controller = new BiensController($pdo);

// Vérifier si édition
$editId = $_GET['edit'] ?? null;
$editBien = $editId ? $controller->getBienById($editId) : null;

// Tous les biens (uniquement pour admin)
$biens = [];
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $biens = $controller->getAllBiens();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gestion des biens</title>
<meta name="description" content="Interface de gestion des biens pour ajouter, modifier et administrer les locations.">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
<link rel="canonical" href="<?php
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    echo $scheme . '://' . $_SERVER['HTTP_HOST'] . '/Pages/Bien/bien_form.php';
?>">
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    .photo-upload-section {
        text-align: center;
        padding: 20px;
        border: 2px dashed #ddd;
        border-radius: 10px;
        background-color: #f9f9f9;
    }
    .photo-preview-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }
    .photo-preview-item {
        position: relative;
        aspect-ratio: 1;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .photo-preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .photo-delete-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(239, 68, 68, 0.9);
        color: white;
        border: none;
        border-radius: 50%;
        width: 30px;
        height: 30px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s;
    }
    .photo-preview-item:hover .photo-delete-btn {
        opacity: 1;
    }
    #photo_input {
        display: none;
    }

    /* Style pour l'autocomplétion d'adresse */
    .adresse-results {
        position: absolute;
        z-index: 20;
        background: white;
        border: 1px solid #ddd;
        border-radius: 0.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        max-height: 240px;
        overflow-y: auto;
        width: 100%;
        margin-top: 4px;
    }
    .adresse-item {
        padding: 0.75rem;
        cursor: pointer;
        border-bottom: 1px solid #eee;
    }
    .adresse-item:hover {
        background: #f0f9ff;
    }
    .adresse-item:last-child {
        border-bottom: none;
    }
</style>
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-5xl mx-auto bg-white shadow-md rounded-lg p-6">
    <h1 class="text-2xl font-bold mb-4">Gestion des biens</h1>

    <form method="POST" action="bien_traitement.php" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $editId ? 'update' : 'create' ?>">
        <input type="hidden" name="id_bien" value="<?= $editBien['id_bien'] ?? '' ?>">

        <!-- Champs cachés pour latitude et longitude -->
        <input type="hidden" name="latitudeBien" id="latitudeBien" value="<?= htmlspecialchars($editBien['latitude_bien'] ?? '') ?>">
        <input type="hidden" name="longitudeBien" id="longitudeBien" value="<?= htmlspecialchars($editBien['longitude_bien'] ?? '') ?>">

        <!-- Champs principaux -->
        <div>
            <label class="block text-gray-700 mb-2">Nom du bien :</label>
            <input type="text" name="nomBien" required class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300"
                   value="<?= htmlspecialchars($editBien['nom_bien'] ?? '') ?>">
        </div>
        <div>
            <label class="block text-gray-700 mb-2">Description :</label>
            <input type="text" name="descriptionBien" required class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300"
                   value="<?= htmlspecialchars($editBien['description_bien'] ?? '') ?>">
        </div>

        <!-- Rue avec autocomplétion adresse -->
        <div class="relative">
            <label class="block text-gray-700 mb-2">Rue (adresse) :</label>
            <input type="text" name="rueBien" id="rueBien" required autocomplete="off"
                   class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300"
                   value="<?= htmlspecialchars($editBien['rue_bien'] ?? '') ?>">
            <div id="adresseResults" class="adresse-results hidden"></div>
        </div>

        <!-- Nouveau champ Code Postal (auto-rempli) -->
        <div>
            <label class="block text-gray-700 mb-2">Code postal :</label>
            <input type="text" name="cpBien" id="cpBien" required
                   class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300"
                   value="<?= htmlspecialchars($editBien['cp_bien'] ?? '') ?>">
        </div>

        <div>
            <label class="block text-gray-700 mb-2">Complément :</label>
            <input type="text" name="compBien" class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300"
                   value="<?= htmlspecialchars($editBien['com_bien'] ?? '') ?>">
        </div>
        <div>
            <label class="block text-gray-700 mb-2">Superficie :</label>
            <input type="number" name="superficieBien" required class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300"
                   value="<?= htmlspecialchars($editBien['superficie_bien'] ?? '') ?>">
        </div>
        <div>
            <label class="block text-gray-700 mb-2">Animaux acceptés :</label>
            <select name="animauxBien" class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300">
                <option value="1" <?= isset($editBien['animaux_bien']) && $editBien['animaux_bien'] ? 'selected' : '' ?>>Oui</option>
                <option value="0" <?= isset($editBien['animaux_bien']) && !$editBien['animaux_bien'] ? 'selected' : '' ?>>Non</option>
            </select>
        </div>
        <div>
            <label class="block text-gray-700 mb-2">Nombre de couchages :</label>
            <input type="number" name="nbCouchagesBien" required class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300"
                   value="<?= htmlspecialchars($editBien['nb_couchage'] ?? '') ?>">
        </div>

        <!-- Auto-complétion commune (sera remplie automatiquement par l'adresse) -->
        <div class="relative">
            <label class="block text-gray-700 mb-2">Commune :</label>
            <input type="text" id="communeSearch" autocomplete="off" placeholder="Nom de la commune"
                   class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300"
                   value="<?= htmlspecialchars($editBien['nom_commune'] ?? '') ?>">
            <input type="hidden" name="communeIdInput" id="communeIdInput" value="<?= htmlspecialchars($editBien['id_commune'] ?? '') ?>">
            <div id="communesResults" class="absolute z-10 bg-white border rounded-lg shadow-lg mt-1 w-full hidden"></div>
        </div>

        <!-- Auto-complétion type bien -->
        <div class="relative">
            <label class="block text-gray-700 mb-2">Type de bien :</label>
            <input type="text" id="typebienSearch" autocomplete="off" placeholder="Nom du type de bien"
                   class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300"
                   value="<?= htmlspecialchars($editBien['des_typebien'] ?? '') ?>">
            <input type="hidden" name="typebienIdInput" id="typebienIdInput" value="<?= htmlspecialchars($editBien['id_typebien'] ?? '') ?>">
            <div id="typebienResults" class="absolute z-10 bg-white border rounded-lg shadow-lg mt-1 w-full hidden"></div>
        </div>

        <!-- Bouton pour afficher le sous-formulaire PRESTATIONS -->
        <div class="md:col-span-2">
            <button type="button" id="togglePrestations" class="bg-indigo-500 text-white px-4 py-2 rounded-lg hover:bg-indigo-600 w-full mb-2">
                <i class="fas fa-tools"></i> Gérer les prestations du bien
            </button>
        </div>

        <!-- Sous-formulaire PRESTATIONS -->
        <div id="prestationsForm" class="hidden md:col-span-2 border p-4 rounded-lg bg-indigo-50">
            <h3 class="text-lg font-semibold mb-4">
                <i class="fas fa-cog"></i> Prestations incluses
            </h3>
            
            <!-- Recherche de prestations -->
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Ajouter une prestation :</label>
                <div class="relative">
                    <input type="text" id="prestationSearch" autocomplete="off" 
                           placeholder="Rechercher une prestation..."
                           class="w-full border rounded-lg p-2 focus:ring focus:ring-indigo-300">
                    <div id="prestationResults" class="absolute z-10 bg-white border rounded-lg shadow-lg mt-1 w-full hidden max-h-48 overflow-y-auto"></div>
                </div>
            </div>

            <!-- Liste des prestations sélectionnées -->
            <div id="selectedPrestations" class="space-y-2">
                <?php if ($editId): ?>
                    <?php
                    $stmt = $pdo->prepare("SELECT p.id_prestation, p.libelle_prestation, sc.quantite 
                                           FROM prestation p
                                           INNER JOIN secompose sc ON p.id_prestation = sc.id_prestation
                                           WHERE sc.id_bien = ?");
                    $stmt->execute([$editId]);
                    $prestationsExistantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    foreach ($prestationsExistantes as $pe):
                    ?>
                        <div class="flex items-center gap-2 bg-white p-3 rounded-lg border" data-prestation-id="<?= $pe['id_prestation'] ?>">
                            <span class="flex-1 font-semibold"><?= htmlspecialchars($pe['libelle_prestation']) ?></span>
                            <input type="number" name="prestations[<?= $pe['id_prestation'] ?>]" 
                                   value="<?= $pe['quantite'] ?>" min="1" 
                                   class="w-20 border rounded p-1 text-center"
                                   placeholder="Qté">
                            <button type="button" onclick="removePrestation(this)" 
                                    class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <p class="text-sm text-gray-600 mt-4 italic">
                <i class="bi bi-info-circle"></i> 
                La quantité représente le nombre d'unités de cette prestation pour ce bien
            </p>
        </div>

        <!-- Bouton pour afficher le sous-formulaire PHOTO -->
        <div class="md:col-span-2">
            <button type="button" id="togglePhoto" class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 w-full mb-2">
                <i class="fas fa-camera"></i> Gérer les photos du bien
            </button>
        </div>

        <!-- Sous-formulaire PHOTO amélioré -->
        <div id="photoForm" class="hidden md:col-span-2 border p-4 rounded-lg bg-purple-50">
            <h3 class="text-lg font-semibold mb-4">
                <i class="fas fa-images"></i> Photos du bien
            </h3>
            
            <div class="photo-upload-section">
                <i class="fas fa-cloud-upload-alt" style="font-size: 48px; color: #a855f7;"></i>
                <p class="text-gray-600 mt-2 mb-3">Cliquez pour ajouter des photos</p>
                
                <label for="photo_input" class="inline-block bg-purple-500 text-white px-6 py-2 rounded-lg cursor-pointer hover:bg-purple-600 transition">
                    <i class="fas fa-upload me-2"></i>Choisir des photos
                </label>
                <input type="file" id="photo_input" name="photos[]" accept="image/*" multiple onchange="previewImages(this)">
                <p class="text-sm text-gray-500 mt-2">JPG, PNG ou GIF. Max 5 Mo par fichier</p>
            </div>

            <!-- Prévisualisation des nouvelles photos -->
            <div id="new_photos_preview" class="photo-preview-grid" style="display: none;"></div>

            <!-- Photos existantes (mode édition) -->
            <?php if ($editId): ?>
                <div class="mt-4">
                    <h4 class="font-semibold text-gray-700 mb-3">Photos actuelles :</h4>
                    <div class="photo-preview-grid">
                        <?php $photos = $controller->getPhotosByBienId($editId); ?>
                        <?php if ($photos && count($photos) > 0): ?>
                            <?php foreach ($photos as $p): ?>
                                <div class="photo-preview-item">
                                    <img src="/<?= htmlspecialchars($p['lien_photo']) ?>" alt="<?= htmlspecialchars($p['nom_photo']) ?>" loading="lazy">
                                    <button type="button" class="photo-delete-btn" 
                                            onclick="deleteExistingPhoto(<?= $p['id_photo'] ?>, this)"
                                            title="Supprimer cette photo">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-gray-500 col-span-full text-center py-4">Aucune photo pour ce bien</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Bouton pour afficher le sous-formulaire TARIF -->
        <div class="md:col-span-2">
            <button type="button" id="toggleTarif" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 w-full mb-2">
                <i class="fas fa-dollar-sign"></i> Ajouter un tarif pour ce bien
            </button>
        </div>

        <!-- Sous-formulaire tarif -->
        <div id="tarifForm" class="hidden md:col-span-2 border p-4 rounded-lg bg-gray-50">
            <h3 class="text-lg font-semibold mb-2">Tarif (optionnel)</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label>Semaine :</label>
                    <input type="number" name="semaine_tarif" class="w-full border rounded-lg p-2">
                </div>
                <div>
                    <label>Année :</label>
                    <input type="number" name="annee_tarif" class="w-full border rounded-lg p-2">
                </div>
                <div>
                    <label>Tarif (€) :</label>
                    <input type="number" step="0.01" name="tarif" class="w-full border rounded-lg p-2">
                </div>
                <div class="relative md:col-span-2">
                    <label>Saison :</label>
                    <input type="text" name="saisonLibelle" id="saisonSearch" autocomplete="off" placeholder="Rechercher une saison..."
                           class="w-full border rounded-lg p-2">
                    <input type="hidden" name="id_saison" id="id_saison">
                    <div id="saisonResults" class="absolute z-10 bg-white border rounded-lg shadow-lg mt-1 w-full hidden"></div>
                </div>
            </div>
        </div>

        <div class="md:col-span-2">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 w-full">
                <?= $editId ? '<i class="fas fa-save"></i> Modifier le bien' : '<i class="fas fa-plus"></i> Ajouter un bien' ?>
            </button>
        </div>
    </form>

    <!-- Liste des biens (ADMIN UNIQUEMENT) -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <h2 class="text-xl font-semibold mt-6 mb-2"><i class="fas fa-list"></i> Liste de tous les biens (Admin)</h2>
    <div class="border rounded-lg p-4 bg-gray-50">
        <?php if ($biens && count($biens) > 0): ?>
            <?php foreach ($biens as $b): ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 border-b py-2 items-center">
                    <!-- Colonne 1: Informations de base -->
                    <div>
                        <div><span class="text-gray-600">Nom :</span> <span class="font-semibold"><?= htmlspecialchars($b['nom_bien']) ?></span></div>
                        <div><span class="text-gray-600">Description :</span> <span class="font-semibold"><?= htmlspecialchars($b['description_bien']) ?></span></div>
                        <div><span class="text-gray-600">Commune :</span> <span class="font-semibold"><?= htmlspecialchars($b['nom_commune']) ?></span></div>
                        <div><span class="text-gray-500">Rue :</span> <span class="font-semibold"><?= htmlspecialchars($b['rue_bien']) ?></span></div>
                        <div><span class="text-gray-500">Complément :</span> <span class="font-semibold"><?= htmlspecialchars($b['com_bien']) ?></span></div>
                    </div>
                    
                    <!-- Colonne 2: Photo agrandie avec bords arrondis -->
                    <div class="flex justify-center">
                        <?php $photos = $controller->getPhotosByBienId($b['id_bien']); ?>
                        <?php if ($photos && count($photos) > 0): ?>
                            <img src="/<?= htmlspecialchars($photos[0]['lien_photo']) ?>" alt="<?= htmlspecialchars($photos[0]['nom_photo']) ?>" loading="lazy" class="w-48 h-48 object-cover rounded-xl shadow-md">
                        <?php else: ?>
                            <div class="w-48 h-48 bg-gray-200 rounded-xl flex items-center justify-center shadow-md">
                                <span class="text-gray-500">Aucune photo</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Colonne 3: Infos détails, tarifs et actions -->
                    <div class="flex flex-col justify-between h-full">
                        <div class="space-y-1">
                            <div><span class="text-gray-600">Superficie :</span> <span class="font-semibold"><?= htmlspecialchars($b['superficie_bien']) ?> m²</span></div>
                            <div><span class="text-gray-600">Animaux :</span> <span class="font-semibold"><?= $b['animaux_bien'] ? 'Oui' : 'Non' ?></span></div>
                            <div><span class="text-gray-600">Couchages :</span> <span class="font-semibold"><?= htmlspecialchars($b['nb_couchage']) ?></span></div>
                            <div><span class="text-gray-600">Type :</span> <span class="font-semibold"><?= htmlspecialchars($b['des_typebien']) ?></span></div>
                            
                            <!-- Affichage des tarifs -->
                            <?php $tarifs = $controller->getTarifsByBienId($b['id_bien']); ?>
                            <?php if ($tarifs && count($tarifs) > 0): ?>
                                <div class="mt-2 p-2 bg-green-50 rounded border border-green-200">
                                    <div class="font-semibold text-green-700 mb-1">💰 Tarifs :</div>
                                    <?php foreach ($tarifs as $tarif): ?>
                                        <div class="text-sm">
                                            <span class="text-gray-600">S<?= htmlspecialchars($tarif['semaine_tarif']) ?></span>
                                            <span class="text-gray-600">/ <?= htmlspecialchars($tarif['annee_tarif']) ?></span>
                                            <?php if (!empty($tarif['libelle_saison'])): ?>
                                                <span class="text-gray-600">- <?= htmlspecialchars($tarif['libelle_saison']) ?></span>
                                            <?php endif; ?>
                                            : <span class="font-semibold text-green-600"><?= number_format($tarif['tarif'], 2, ',', ' ') ?> €</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="mt-2 text-sm text-gray-500">Aucun tarif défini</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex gap-2 mt-2">
                            <a href="bien_form.php?edit=<?= $b['id_bien'] ?>" class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600"><i class="fas fa-edit"></i></a>
                            <a href="#" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600"
                               onclick="if(confirm('Voulez-vous vraiment supprimer ce bien ?')) {
                                   fetch('bien_traitement.php', {
                                       method: 'POST',
                                       headers: {'Content-Type':'application/x-www-form-urlencoded'},
                                       body: 'action=delete&id_bien=<?= $b['id_bien'] ?>&_csrf_token=' + encodeURIComponent(csrfToken)
                                   }).then(()=> window.location='bien_form.php');
                               }"><i class="fas fa-trash"></i></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-gray-500">Aucun bien trouvé.</p>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <!-- Message pour les utilisateurs non-admin -->
    <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg text-center">
        <a href="../index.php" class="inline-block mt-3 bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition">
            Voir mes biens
        </a>
    </div>
    <?php endif; ?>
</div>

<script>
// Variables globales pour stocker les fichiers
let selectedFiles = [];
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

// Prévisualisation des images
function previewImages(input) {
    const preview = document.getElementById('new_photos_preview');
    preview.innerHTML = '';
    selectedFiles = Array.from(input.files);
    
    if (selectedFiles.length > 0) {
        preview.style.display = 'grid';
        
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'photo-preview-item';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Nouvelle photo" loading="lazy">
                    <button type="button" class="photo-delete-btn" onclick="removeNewPhoto(${index})" title="Retirer cette photo">
                        <i class="bi bi-x-lg"></i>
                    </button>
                `;
                preview.appendChild(div);
            }
            
            reader.readAsDataURL(file);
        });
    } else {
        preview.style.display = 'none';
    }
}

// Retirer une nouvelle photo avant upload
function removeNewPhoto(index) {
    selectedFiles.splice(index, 1);
    
    const dt = new DataTransfer();
    selectedFiles.forEach(file => dt.items.add(file));
    document.getElementById('photo_input').files = dt.files;
    
    previewImages(document.getElementById('photo_input'));
}

// Supprimer une photo existante
function deleteExistingPhoto(photoId, button) {
    if (!confirm('Voulez-vous vraiment supprimer cette photo ?')) {
        return;
    }
    
    fetch('bien_traitement.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=delete_photo&id_photo=${photoId}&_csrf_token=${encodeURIComponent(csrfToken)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.closest('.photo-preview-item').remove();
            alert('Photo supprimée avec succès');
        } else {
            alert('Erreur lors de la suppression: ' + (data.message || 'Erreur inconnue'));
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur lors de la suppression de la photo');
    });
}

// Toggle sous-formulaire photo
document.getElementById('togglePhoto').addEventListener('click', () => {
    document.getElementById('photoForm').classList.toggle('hidden');
});

// Toggle sous-formulaire tarif
document.getElementById('toggleTarif').addEventListener('click', () => {
    document.getElementById('tarifForm').classList.toggle('hidden');
});

// === AUTOCOMPLÉTION ADRESSE (API Adresse data.gouv.fr) ===
const rueBien = document.getElementById('rueBien');
const adresseResults = document.getElementById('adresseResults');
const cpBien = document.getElementById('cpBien');
const communeSearch = document.getElementById('communeSearch');
const communeIdInput = document.getElementById('communeIdInput');
const latitudeBien = document.getElementById('latitudeBien');
const longitudeBien = document.getElementById('longitudeBien');

rueBien.addEventListener('input', function() {
    const query = this.value.trim();
    if (query.length >= 4) {
        fetch(`https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(query)}&limit=8`)
            .then(res => res.json())
            .then(data => {
                adresseResults.innerHTML = '';
                if (data.features && data.features.length > 0) {
                    data.features.forEach(feature => {
                        const props = feature.properties;
                        const item = document.createElement('div');
                        item.className = 'adresse-item';
                        item.innerHTML = `
                            <div class="font-semibold">${props.label}</div>
                            <div class="text-sm text-gray-600">${props.postcode} ${props.city}</div>
                        `;
                        item.addEventListener('click', () => {
                            rueBien.value = props.name || (props.housenumber ? `${props.housenumber} ${props.street}` : props.street);
                            cpBien.value = props.postcode;
                            communeSearch.value = props.city;

                            // Remplir latitude et longitude
                            if (feature.geometry && feature.geometry.coordinates) {
                                longitudeBien.value = feature.geometry.coordinates[0];
                                latitudeBien.value = feature.geometry.coordinates[1];
                            }

                            fetch(`../../ajax/ajax_bien.php?q=${encodeURIComponent(props.city)}`)
                                .then(res => res.json())
                                .then(communeData => {
                                    if (communeData.success && communeData.communes.length > 0) {
                                        communeIdInput.value = communeData.communes[0].id_commune;
                                    } else {
                                        communeIdInput.value = '';
                                    }
                                });

                            adresseResults.classList.add('hidden');
                        });
                        adresseResults.appendChild(item);
                    });
                    adresseResults.classList.remove('hidden');
                } else {
                    adresseResults.innerHTML = '<div class="adresse-item text-gray-600">Aucune adresse trouvée</div>';
                    adresseResults.classList.remove('hidden');
                }
            })
            .catch(err => {
                console.error('Erreur API Adresse :', err);
                adresseResults.innerHTML = '<div class="adresse-item text-red-600">Erreur de recherche</div>';
                adresseResults.classList.remove('hidden');
            });
    } else {
        adresseResults.classList.add('hidden');
    }
});

// Fermer les résultats si clic ailleurs
document.addEventListener('click', function(e) {
    if (!rueBien.contains(e.target) && !adresseResults.contains(e.target)) {
        adresseResults.classList.add('hidden');
    }
});

// === COMMUNE AUTOCOMPLETE (inchangé) ===
const communeSearchEl = document.getElementById('communeSearch');
const communesResults = document.getElementById('communesResults');

communeSearchEl.addEventListener('input', function() {
    const query = this.value.trim();
    if(query.length >= 2) {
        fetch(`../../ajax/ajax_bien.php?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                communesResults.innerHTML = '';
                if(data.success && data.communes.length > 0){
                    data.communes.forEach(c => {
                        const item = document.createElement('div');
                        item.className = 'p-2 cursor-pointer hover:bg-blue-100';
                        item.innerHTML = `<div class="font-semibold">${c.nom_commune}</div><div class="text-sm text-gray-600">${c.cp_commune ? 'CP: '+c.cp_commune : ''} ${c.commune_departement ? ' - Dép: '+c.commune_departement : ''}</div>`;
                        item.addEventListener('click', () => {
                            communeSearchEl.value = c.nom_commune;
                            communeIdInput.value = c.id_commune;
                            communesResults.classList.add('hidden');
                        });
                        communesResults.appendChild(item);
                    });
                    communesResults.classList.remove('hidden');
                } else communesResults.innerHTML = '<div class="p-3 text-center text-gray-600">Aucune commune trouvée</div>';
            });
    } else communesResults.classList.add('hidden');
});

// === TYPE BIEN AUTOCOMPLETE ===
const typeSearch = document.getElementById('typebienSearch');
const typeIdInput = document.getElementById('typebienIdInput');
const typeResults = document.getElementById('typebienResults');

typeSearch.addEventListener('input', function() {
    const query = this.value.trim();
    if(query.length >= 1) {
        fetch(`../../ajax/ajax_typebiens.php?action=search&search=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                typeResults.innerHTML = '';
                if(data.success && data.data.length > 0){
                    data.data.forEach(t => {
                        const item = document.createElement('div');
                        item.className = 'p-2 cursor-pointer hover:bg-blue-100';
                        item.textContent = t.des_typebien;
                        item.addEventListener('click', () => {
                            typeSearch.value = t.des_typebien;
                            typeIdInput.value = t.id_typebien;
                            typeResults.classList.add('hidden');
                        });
                        typeResults.appendChild(item);
                    });
                    typeResults.classList.remove('hidden');
                } else typeResults.innerHTML = '<div class="p-3 text-center text-gray-600">Aucun type trouvé</div>';
            });
    } else typeResults.classList.add('hidden');
});

// === SAISON AUTOCOMPLETE ===
const saisonSearch = document.getElementById('saisonSearch');
const saisonResults = document.getElementById('saisonResults');
const id_saison = document.getElementById('id_saison');

saisonSearch.addEventListener('input', function() {
    const query = this.value.trim();
    if(query.length >= 1){
        fetch(`../../Pages/Tarifs/tarifs_traitement.php?autocomplete=saison&q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                saisonResults.innerHTML = '';
                if(data.success && data.data.length > 0){
                    data.data.forEach(s => {
                        const item = document.createElement('div');
                        item.className = 'p-2 cursor-pointer hover:bg-blue-100';
                        item.textContent = s.libelle_saison;
                        item.addEventListener('click', () => {
                            saisonSearch.value = s.libelle_saison;
                            id_saison.value = s.id_saison;
                            saisonResults.classList.add('hidden');
                        });
                        saisonResults.appendChild(item);
                    });
                    saisonResults.classList.remove('hidden');
                } else saisonResults.innerHTML = '<div class="p-3 text-center text-gray-600">Aucune saison trouvée</div>';
            });
    } else saisonResults.classList.add('hidden');
});

// === PRESTATIONS AUTOCOMPLETE ===
const prestationSearch = document.getElementById('prestationSearch');
const prestationResults = document.getElementById('prestationResults');
const selectedPrestations = document.getElementById('selectedPrestations');

document.getElementById('togglePrestations').addEventListener('click', () => {
    document.getElementById('prestationsForm').classList.toggle('hidden');
});

prestationSearch.addEventListener('input', function() {
    const query = this.value.trim();
    if(query.length >= 1) {
        fetch(`../../ajax/ajax_prestations.php?action=search&search=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                prestationResults.innerHTML = '';
                if(data.success && data.data.length > 0){
                    data.data.forEach(p => {
                        if (!document.querySelector(`[data-prestation-id="${p.id_prestation}"]`)) {
                            const item = document.createElement('div');
                            item.className = 'p-2 cursor-pointer hover:bg-indigo-100';
                            item.textContent = p.libelle_prestation;
                            item.addEventListener('click', () => {
                                addPrestation(p.id_prestation, p.libelle_prestation);
                                prestationSearch.value = '';
                                prestationResults.classList.add('hidden');
                            });
                            prestationResults.appendChild(item);
                        }
                    });
                    prestationResults.classList.remove('hidden');
                } else {
                    prestationResults.innerHTML = '<div class="p-3 text-center text-gray-600">Aucune prestation trouvée</div>';
                    prestationResults.classList.remove('hidden');
                }
            })
            .catch(err => {
                console.error('Erreur recherche prestations:', err);
                prestationResults.innerHTML = '<div class="p-3 text-center text-red-600">Erreur de recherche</div>';
                prestationResults.classList.remove('hidden');
            });
    } else {
        prestationResults.classList.add('hidden');
    }
});

function addPrestation(id, libelle) {
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2 bg-white p-3 rounded-lg border';
    div.setAttribute('data-prestation-id', id);
    div.innerHTML = `
        <span class="flex-1 font-semibold">${libelle}</span>
        <input type="number" name="prestations[${id}]" value="1" min="1" 
               class="w-20 border rounded p-1 text-center" placeholder="Qté" required>
        <button type="button" onclick="removePrestation(this)" 
                class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">
            <i class="bi bi-trash"></i>
        </button>
    `;
    selectedPrestations.appendChild(div);
}

function removePrestation(button) {
    button.closest('[data-prestation-id]').remove();
}

// Fermer les résultats si clic ailleurs (pour prestations)
document.addEventListener('click', function(e) {
    if (!prestationSearch.contains(e.target) && !prestationResults.contains(e.target)) {
        prestationResults.classList.add('hidden');
    }
});
</script>
</body>
</html>