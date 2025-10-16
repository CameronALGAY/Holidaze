<?php
require_once '../../include/db.php';
require_once 'bien_class.php';
require_once '../Communes/communes_class.php';
require_once '../TypeBien/typebien_class.php';
require_once '../Tarifs/tarifs_class.php';

$controller = new BiensController($pdo);

// Vérifier si édition
$editId = $_GET['edit'] ?? null;
$editBien = $editId ? $controller->getBienById($editId) : null;

// Tous les biens
$biens = $controller->getAllBiens();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gestion des biens</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-5xl mx-auto bg-white shadow-md rounded-lg p-6">
    <h1 class="text-2xl font-bold mb-4">Gestion des biens</h1>

    <form method="POST" action="bien_traitement.php" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="action" value="<?= $editId ? 'update' : 'create' ?>">
        <input type="hidden" name="id_bien" value="<?= $editBien['id_bien'] ?? '' ?>">

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
        <div>
            <label class="block text-gray-700 mb-2">Rue :</label>
            <input type="text" name="rueBien" required class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300"
                   value="<?= htmlspecialchars($editBien['rue_bien'] ?? '') ?>">
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

        <!-- Auto-complétion commune -->
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

        <!-- Bouton pour afficher le sous-formulaire PHOTO -->
        <div class="md:col-span-2">
            <button type="button" id="togglePhoto" class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 w-full mb-2">
                📷 Ajouter une photo pour ce bien
            </button>
        </div>

        <!-- Sous-formulaire PHOTO -->
        <div id="photoForm" class="hidden md:col-span-2 border p-4 rounded-lg bg-purple-50">
            <h3 class="text-lg font-semibold mb-2">📸 Photo (optionnel)</h3>
            <input type="file" name="photo" accept="image/*" class="w-full border rounded-lg p-2 mb-2">
            <small class="text-gray-600">Formats acceptés : JPG, PNG, GIF (max 5 Mo)</small>
            <?php if ($editId): ?>
                <div class="mt-2">
                    <strong>Photos actuelles :</strong>
                    <?php $photos = $controller->getPhotosByBienId($editId); ?>
                    <?php if ($photos): ?>
                        <?php foreach ($photos as $photo): ?>
                            <img src="/<?= htmlspecialchars($photo['lien_photo']) ?>" alt="<?= htmlspecialchars($photo['nom_photo']) ?>" class="w-16 h-16 object-cover inline-block m-1 rounded">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500 text-sm">Aucune photo</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Bouton pour afficher le sous-formulaire TARIF -->
        <div class="md:col-span-2">
            <button type="button" id="toggleTarif" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 w-full mb-2">
                💰 Ajouter un tarif pour ce bien
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
                <?= $editId ? '💾 Modifier le bien' : '➕ Ajouter un bien' ?>
            </button>
        </div>
    </form>

    <!-- Liste des biens -->
    <h2 class="text-xl font-semibold mt-6 mb-2">📋 Liste des biens</h2>
    <div class="border rounded-lg p-4 bg-gray-50">
        <?php if ($biens): ?>
            <?php foreach ($biens as $b): ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 border-b py-2 items-center">
                    <div>
                        <span class="font-semibold"><?= htmlspecialchars($b['nom_bien']) ?></span>
                        <div class="text-gray-600 text-sm"><?= htmlspecialchars($b['description_bien']) ?></div>
                        <div class="text-gray-500 text-xs"><?= htmlspecialchars($b['rue_bien'] . ($b['com_bien'] ? ' - '.$b['com_bien'] : '')) ?></div>
                        <?php if (!empty($b['tarif'])): ?>
                            <div class="text-green-600 font-semibold">💰 <?= htmlspecialchars($b['tarif']) ?> €</div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php $photos = $controller->getPhotosByBienId($b['id_bien']); ?>
                        <?php if ($photos && count($photos) > 0): ?>
                            <img src="/<?= htmlspecialchars($photos[0]['lien_photo']) ?>" alt="Photo" class="w-24 h-24 object-cover rounded">
                            <?php if (count($photos) > 1): ?>
                                <small class="text-blue-600">+<?= count($photos)-1 ?> photo(s)</small>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="w-24 h-24 bg-gray-200 flex items-center justify-center rounded text-gray-500 text-sm">📷</div>
                        <?php endif; ?>
                    </div>
                    <div class="flex justify-between items-center">
                        <div>
                            <div>📏 <?= htmlspecialchars($b['superficie_bien']) ?> m²</div>
                            <div>🐕 <?= $b['animaux_bien'] ? 'Oui' : 'Non' ?></div>
                            <div>🛏️ <?= htmlspecialchars($b['nb_couchage']) ?></div>
                            <div>🏘️ <?= htmlspecialchars($b['nom_commune']) ?></div>
                            <div>🏠 <?= htmlspecialchars($b['des_typebien']) ?></div>
                        </div>
                        <div class="flex gap-2">
                            <a href="bien_form.php?edit=<?= $b['id_bien'] ?>" class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">✏️</a>
                            <a href="#" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600"
                               onclick="if(confirm('Voulez-vous vraiment supprimer ce bien ?')) {
                                   fetch('bien_traitement.php', {
                                       method: 'POST',
                                       headers: {'Content-Type':'application/x-www-form-urlencoded'},
                                       body: 'action=delete&id_bien=<?= $b['id_bien'] ?>'
                                   }).then(()=> window.location='bien_form.php');
                               }">🗑️</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-gray-500">Aucun bien trouvé.</p>
        <?php endif; ?>
    </div>
</div>

<script>
// Toggle sous-formulaire photo
document.getElementById('togglePhoto').addEventListener('click', () => {
    document.getElementById('photoForm').classList.toggle('hidden');
});

// Toggle sous-formulaire tarif
document.getElementById('toggleTarif').addEventListener('click', () => {
    document.getElementById('tarifForm').classList.toggle('hidden');
});

// === COMMUNE AUTOCOMPLETE ===
const communeSearch = document.getElementById('communeSearch');
const communeIdInput = document.getElementById('communeIdInput');
const communesResults = document.getElementById('communesResults');

communeSearch.addEventListener('input', function() {
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
                            communeSearch.value = c.nom_commune;
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
</script>
</body>
</html>