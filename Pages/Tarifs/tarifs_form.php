<?php
require_once '../../include/db.php';
require_once 'tarifs_traitement.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des tarifs</title>
    <meta name="description" content="Interface de gestion des tarifs pour l'administration Holidaze.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?php
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        echo $scheme . '://' . $_SERVER['HTTP_HOST'] . '/Pages/Tarifs/tarifs_form.php';
    ?>">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-4xl mx-auto bg-white shadow-md rounded-lg p-6">
    <h1 class="text-2xl font-bold mb-4">💰 Gestion des tarifs</h1>

    <!-- Formulaire d'ajout -->
    <form id="form-create" class="mb-6 grid grid-cols-2 gap-4">
        <div>
            <label class="block text-gray-700 mb-2">Semaine :</label>
            <input type="text" id="semaine_tarif" required
                   class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300">
        </div>

        <div>
            <label class="block text-gray-700 mb-2">Année :</label>
            <input type="text" id="annee_tarif" required
                   class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300">
        </div>

        <div>
            <label class="block text-gray-700 mb-2">Tarif (€) :</label>
            <input type="number" id="tarif" required step="0.01"
                   class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300">
        </div>

        <div class="relative">
            <label class="block text-gray-700 mb-2">Bien :</label>
            <input type="text" id="bienSearch" placeholder="Rechercher un bien..." autocomplete="off"
                   class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300">
            <input type="hidden" id="idBien">
            <div id="bienResults" class="absolute bg-white border border-gray-300 w-full rounded-lg mt-1 hidden z-50"></div>
        </div>

        <div class="relative">
            <label class="block text-gray-700 mb-2">Saison :</label>
            <input type="text" id="saisonSearch" placeholder="Rechercher une saison..." autocomplete="off"
                   class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300">
            <input type="hidden" id="id_saison">
            <div id="saisonResults" class="absolute bg-white border border-gray-300 w-full rounded-lg mt-1 hidden z-50"></div>
        </div>

        <div class="flex items-end">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                ➕ Ajouter un tarif
            </button>
        </div>
    </form>

    <!-- Message -->
    <p id="message" class="text-green-600 font-semibold mb-4"></p>

    <!-- Recherche -->
    <form id="form-search" class="mb-6">
        <label class="block text-gray-700 mb-2">Rechercher par bien ou saison :</label>
        <input type="text" id="search" placeholder="Ex: Villa, Été..."
               class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-green-300">
        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
            🔍 Rechercher
        </button>
    </form>

    <!-- Liste -->
    <h2 class="text-xl font-semibold mb-2">📋 Liste des tarifs</h2>
    <div id="tarifs-list" class="border rounded-lg p-4 bg-gray-50">
        Chargement...
    </div>
</div>

<script>
let tarifEditId = null;

// --- Auto-complétion BIEN ---
const bienSearch = document.getElementById('bienSearch');
const bienResults = document.getElementById('bienResults');
const bienIdInput = document.getElementById('idBien');

bienSearch.addEventListener('input', function() {
    const query = this.value.trim();
    if (query.length >= 2) searchBien(query);
    else bienResults.classList.add('hidden');
});

function searchBien(query) {
    fetch(`tarifs_traitement.php?autocomplete=bien&q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data.length > 0) displayBienResults(data.data);
            else bienResults.innerHTML = '<div class="p-3 text-center text-gray-600">Aucun bien trouvé</div>';
        });
}

function displayBienResults(biens) {
    bienResults.innerHTML = '';
    biens.forEach(b => {
        const div = document.createElement('div');
        div.className = 'p-2 cursor-pointer hover:bg-blue-100';
        div.textContent = b.nomBien;
        div.onclick = () => {
            bienSearch.value = b.nomBien;
            bienIdInput.value = b.idBien;
            bienResults.classList.add('hidden');
        };
        bienResults.appendChild(div);
    });
    bienResults.classList.remove('hidden');
}

// --- Auto-complétion SAISON ---
const saisonSearch = document.getElementById('saisonSearch');
const saisonResults = document.getElementById('saisonResults');
const saisonIdInput = document.getElementById('id_saison');

saisonSearch.addEventListener('input', function() {
    const query = this.value.trim();
    if (query.length >= 1) searchSaison(query);
    else saisonResults.classList.add('hidden');
});

function searchSaison(query) {
    fetch(`tarifs_traitement.php?autocomplete=saison&q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.data.length > 0) displaySaisonResults(data.data);
            else saisonResults.innerHTML = '<div class="p-3 text-center text-gray-600">Aucune saison trouvée</div>';
        });
}

function displaySaisonResults(saisons) {
    saisonResults.innerHTML = '';
    saisons.forEach(s => {
        const div = document.createElement('div');
        div.className = 'p-2 cursor-pointer hover:bg-blue-100';
        div.textContent = s.libelle_saison;
        div.onclick = () => {
            saisonSearch.value = s.libelle_saison;
            saisonIdInput.value = s.id_saison;
            saisonResults.classList.add('hidden');
        };
        saisonResults.appendChild(div);
    });
    saisonResults.classList.remove('hidden');
}

// --- Chargement des tarifs ---
async function loadTarifs(search = '') {
    const url = search ? `tarifs_traitement.php?action=search&search=${encodeURIComponent(search)}` : 'tarifs_traitement.php?action=getAll';
    const res = await fetch(url);
    const data = await res.json();

    const list = document.getElementById('tarifs-list');
    if (!data.length) {
        list.innerHTML = '<p>Aucun tarif trouvé.</p>';
        return;
    }

    let html = '';
    data.forEach(t => {
        html += `
        <div class="grid grid-cols-6 gap-2 items-center border-b py-2">
            <span>${t.semaine_tarif}</span>
            <span>${t.annee_tarif}</span>
            <span>${t.tarif} €</span>
            <span>${t.nomBien || '-'}</span>
            <span>${t.libelle_saison || '-'}</span>
        </div>`;
    });
    list.innerHTML = html;
}

// --- Création ---
document.getElementById('form-create').addEventListener('submit', async e => {
    e.preventDefault();
    const formData = new FormData();
    formData.append('action', 'create');
    formData.append('semaine_tarif', document.getElementById('semaine_tarif').value);
    formData.append('annee_tarif', document.getElementById('annee_tarif').value);
    formData.append('tarif', document.getElementById('tarif').value);
    formData.append('idBien', document.getElementById('idBien').value);
    formData.append('id_saison', document.getElementById('id_saison').value);

    const res = await fetch('tarifs_traitement.php', { method: 'POST', body: formData });
    const data = await res.json();

    document.getElementById('message').innerText = data.success ? '✅ Tarif ajouté !' : '❌ Erreur lors de la création.';
    e.target.reset();
    loadTarifs();
});

// --- Recherche ---
document.getElementById('form-search').addEventListener('submit', e => {
    e.preventDefault();
    loadTarifs(document.getElementById('search').value);
});

// --- Initial load ---
loadTarifs();
</script>

</body>
<<<<<<< Updated upstream
</html>
=======
</html>
>>>>>>> Stashed changes
