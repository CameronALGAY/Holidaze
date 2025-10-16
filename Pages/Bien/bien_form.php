<?php
require_once '../../include/db.php';
require_once 'bien_class.php';
require_once '../Communes/communes_class.php';
require_once '../TypeBien/typebien_class.php';

$controller = new BiensController($pdo);

// Vérifier si on est en mode édition
$editId = $_GET['edit'] ?? null;
$editBien = $editId ? $controller->getBienById($editId) : null;

// Récupérer tous les biens pour la liste
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

<div class="max-w-4xl mx-auto bg-white shadow-md rounded-lg p-6">
    <h1 class="text-2xl font-bold mb-4">Gestion des biens</h1>

    <!-- Formulaire ajout/modif -->
    <form method="POST" action="bien_traitement.php" enctype="multipart/form-data" class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <input type="hidden" name="action" value="<?= $editId ? 'update' : 'create' ?>">
        <input type="hidden" name="id_bien" value="<?= $editBien['id_bien'] ?? '' ?>">

        <div>
            <label class="block text-gray-700 mb-2">Nom du bien :</label>
            <input type="text" name="nomBien" required class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300"
                   value="<?= htmlspecialchars($editBien['nom_bien'] ?? '') ?>">
        </div>

        <div>
            <label class="block text-gray-700 mb-2">Description :</label>
            <input type="text" name="descriptionBien" required class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300"
                   value="<?= htmlspecialchars($editBien['description_bien'] ?? '') ?>">
        </div>

        <div>
            <label class="block text-gray-700 mb-2">Rue :</label>
            <input type="text" name="rueBien" required class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300"
                   value="<?= htmlspecialchars($editBien['rue_bien'] ?? '') ?>">
        </div>

        <div>
            <label class="block text-gray-700 mb-2">Complément :</label>
            <input type="text" name="compBien" class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300"
                   value="<?= htmlspecialchars($editBien['com_bien'] ?? '') ?>">
        </div>

        <div>
            <label class="block text-gray-700 mb-2">Superficie (m²) :</label>
            <input type="number" name="superficieBien" required class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300"
                   value="<?= htmlspecialchars($editBien['superficie_bien'] ?? '') ?>">
        </div>

        <div>
            <label class="block text-gray-700 mb-2">Animaux acceptés :</label>
            <select name="animauxBien" class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300">
                <option value="1" <?= isset($editBien['animaux_bien']) && $editBien['animaux_bien'] ? 'selected' : '' ?>>Oui</option>
                <option value="0" <?= isset($editBien['animaux_bien']) && !$editBien['animaux_bien'] ? 'selected' : '' ?>>Non</option>
            </select>
        </div>

        <div>
            <label class="block text-gray-700 mb-2">Nombre de couchages :</label>
            <input type="number" name="nbCouchagesBien" required class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300"
                   value="<?= htmlspecialchars($editBien['nb_couchage'] ?? '') ?>">
        </div>

        <!-- Auto-complétion commune -->
        <div class="relative">
            <label class="block text-gray-700 mb-2">Commune :</label>
            <input type="text" id="communeSearch" autocomplete="off"
                   class="w-full border rounded-lg p-2 mb-2 focus:ring focus:ring-blue-300"
                   placeholder="Nom de la commune"
                   value="<?= htmlspecialchars($editBien['nom_commune'] ?? '') ?>">
            <input type="hidden" name="communeIdInput" id="communeIdInput"
                   value="<?= htmlspecialchars($editBien['id_commune'] ?? '') ?>">
            <div id="communesResults" class="absolute z-10 bg-white border rounded-lg shadow-lg mt-1 w-full hidden"></div>
        </div>

        <!-- Auto-complétion type bien -->
        <div class="relative">
            <label class="block text-gray-700 mb-2">Type de bien :</label>
            <input type="text" id="typebienSearch" autocomplete="off"
                   class="w-full border rounded-lg p-2 mb-2 focus:ring focus:ring-blue-300"
                   placeholder="Nom du type de bien"
                   value="<?= htmlspecialchars($editBien['des_typebien'] ?? '') ?>">
            <input type="hidden" name="typebienIdInput" id="typebienIdInput"
                   value="<?= htmlspecialchars($editBien['id_typebien'] ?? '') ?>">
            <div id="typebienResults" class="absolute z-10 bg-white border rounded-lg shadow-lg mt-1 w-full hidden"></div>
        </div>

        <!-- Bouton pour dérouler le sous-formulaire photo -->
        <div class="md:col-span-2">
            <button type="button" onclick="togglePhotoForm()" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
                📷 Ajouter une photo (cliquez pour dérouler)
            </button>
        </div>

        <!-- Sous-formulaire photo (caché par défaut) -->
        <div id="photoForm" class="md:col-span-2 hidden">
            <label class="block text-gray-700 mb-2">Choisir une photo :</label>
            <input type="file" name="photo" accept="image/*" class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300">
        </div>

        <div class="md:col-span-2">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 w-full">
                <?= $editId ? '💾 Modifier le bien' : '➕ Ajouter un bien' ?>
            </button>
        </div>
    </form>

<!-- Liste des biens -->
<h2 class="text-xl font-semibold mb-2">📋 Liste des biens</h2>
<div class="border rounded-lg p-4 bg-gray-50">
    <?php if ($biens): ?>
        <?php foreach ($biens as $b): ?>
            <div class="grid grid-cols-3 gap-2 border-b py-2 items-center">
                <div>
                    <span class="font-semibold"><?= htmlspecialchars($b['nom_bien']) ?></span>
                    <div class="text-gray-600 text-sm"><?= htmlspecialchars($b['description_bien']) ?></div>
                    <div class="text-gray-500 text-xs"><?= htmlspecialchars($b['rue_bien'] . ($b['com_bien'] ? ' - ' . $b['com_bien'] : '')) ?></div>
                </div>
                <div>
                    <!-- Affichage de la première photo si elle existe -->
                    <?php $photos = $controller->getPhotosByBienId($b['id_bien']); ?>
                    <?php if ($photos && count($photos) > 0): ?>
                        <img src="/<?= htmlspecialchars($photos[0]['lien_photo']) ?>" alt="<?= htmlspecialchars($photos[0]['nom_photo']) ?>" class="w-24 h-24 object-cover">
                    <?php else: ?>
                        <span class="text-gray-500">Aucune photo</span>
                    <?php endif; ?>
                </div>
                <div class="flex flex-col justify-between h-full">
                    <div>
                        <div>Superficie : <span class="font-semibold"><?= htmlspecialchars($b['superficie_bien']) ?> m²</span></div>
                        <div>Animaux : <span class="font-semibold"><?= $b['animaux_bien'] ? 'Oui' : 'Non' ?></span></div>
                        <div>Couchages : <span class="font-semibold"><?= htmlspecialchars($b['nb_couchage']) ?></span></div>
                        <div>Commune : <span class="font-semibold"><?= htmlspecialchars($b['nom_commune']) ?></span></div>
                        <div>Type : <span class="font-semibold"><?= htmlspecialchars($b['des_typebien']) ?></span></div>
                    </div>
                    <div class="flex gap-2 mt-2">
                        <a href="bien_form.php?edit=<?= $b['id_bien'] ?>"
                           class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">✏️ Modifier</a>
                        <a href="#"
                           class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600"
                           onclick="if(confirm('Voulez-vous vraiment supprimer ce bien ?')){ 
                               console.log('Tentative de suppression pour id_bien: <?= $b['id_bien'] ?>'); 
                               fetch('bien_traitement.php', {
                                   method: 'POST',
                                   headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                   body: new URLSearchParams({ action: 'delete', id_bien: '<?= $b['id_bien'] ?>' })
                               }).then(response => response.json()).then(data => {
                                   console.log('Réponse du serveur:', data);
                                   if (data.success) window.location='bien_form.php';
                                   else console.log('Échec:', data.message);
                               }).catch(error => console.log('Erreur fetch:', error));
                           }">🗑️ Supprimer</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-gray-500">Aucun bien trouvé.</p>
    <?php endif; ?>
</div>


<!-- Scripts auto-complétion -->
<script>
const communeSearch = document.getElementById('communeSearch');
const communeIdInput = document.getElementById('communeIdInput');
const communesResults = document.getElementById('communesResults');

communeSearch.addEventListener('input', function() {
    const query = this.value.trim();
    if(query.length >= 2) searchCommunes(query);
    else {
        communesResults.classList.add('hidden');
        communesResults.innerHTML = '';
    }
});

function searchCommunes(query){
    communesResults.innerHTML = '<div class="p-3 text-center text-gray-600">Recherche en cours...</div>';
    communesResults.classList.remove('hidden');
    fetch(`../../ajax/ajax_bien.php?q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            if(data.success && data.communes.length > 0) displayCommuneResults(data.communes);
            else communesResults.innerHTML = '<div class="p-3 text-center text-gray-600">Aucune commune trouvée</div>';
        })
        .catch(() => communesResults.innerHTML = '<div class="p-3 text-center text-red-600">Erreur de connexion</div>');
}

function displayCommuneResults(communes){
    communesResults.innerHTML = '';
    communes.forEach(c => {
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
}

// Type de bien auto-complétion
const typeSearch = document.getElementById('typebienSearch');
const typeIdInput = document.getElementById('typebienIdInput');
const typeResults = document.getElementById('typebienResults');

typeSearch.addEventListener('input', function(){
    const query = this.value.trim();
    if(query.length >= 1) searchTypeBien(query);
    else {
        typeResults.classList.add('hidden');
        typeResults.innerHTML = '';
    }
});

function searchTypeBien(query){
    typeResults.innerHTML = '<div class="p-3 text-center text-gray-600">Recherche en cours...</div>';
    typeResults.classList.remove('hidden');
    fetch(`../../ajax/ajax_typebiens.php?action=search&search=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            if(data.success && data.data.length > 0) displayTypeResults(data.data);
            else typeResults.innerHTML = '<div class="p-3 text-center text-gray-600">Aucun type trouvé</div>';
        })
        .catch(()=> typeResults.innerHTML = '<div class="p-3 text-center text-red-600">Erreur de connexion</div>');
}

function displayTypeResults(types){
    typeResults.innerHTML = '';
    types.forEach(t => {
        const item = document.createElement('div');
        item.className = 'p-2 cursor-pointer hover:bg-blue-100';
        item.textContent = t.des_typebien;
        item.addEventListener('click', ()=>{
            typeSearch.value = t.des_typebien;
            typeIdInput.value = t.id_typebien;
            typeResults.classList.add('hidden');
        });
        typeResults.appendChild(item);
    });
}

// Nouvelle fonction pour toggle le sous-formulaire photo
function togglePhotoForm() {
    var form = document.getElementById('photoForm');
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
    } else {
        form.style.display = 'none';
    }
}
</script>
</body>
</html>