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

    <!-- Formulaire d'ajout -->
    <form id="form-create" class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-gray-700 mb-2">Nom du bien :</label>
        <input type="text" id="nomBien" required class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300">
      </div>
      <div>
        <label class="block text-gray-700 mb-2">Description :</label>
        <input type="text" id="descriptionBien" required class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300">
      </div>
      <div>
        <label class="block text-gray-700 mb-2">Rue :</label>
        <input type="text" id="rueBien" required class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300">
      </div>
      <div>
        <label class="block text-gray-700 mb-2">Complément :</label>
        <input type="text" id="compBien" class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300">
      </div>
      <div>
        <label class="block text-gray-700 mb-2">Superficie :</label>
        <input type="number" id="superficieBien" required class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300">
      </div>
      <div>
        <label class="block text-gray-700 mb-2">Animaux acceptés :</label>
        <select id="animauxBien" class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300">
          <option value="1">Oui</option>
          <option value="0">Non</option>
        </select>
      </div>
      <div>
        <label class="block text-gray-700 mb-2">Nombre de couchages :</label>
        <input type="number" id="nbCouchagesBien" required class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300">
      </div>
      
      <!-- Auto-complétion communes -->
      <div class="relative">
        <label class="block text-gray-700 mb-2">Commune :</label>
        <input type="text" id="communeSearch" autocomplete="off"
               class="w-full border rounded-lg p-2 mb-2 focus:ring focus:ring-blue-300"
               placeholder="Nom de la commune">
        <input type="hidden" id="communeIdInput">
        <div id="communesResults" class="absolute z-10 bg-white border rounded-lg shadow-lg mt-1 w-full hidden"></div>
      </div>

      <!-- Auto-complétion types de bien -->
      <div class="relative">
        <label class="block text-gray-700 mb-2">Type de bien :</label>
        <input type="text" id="typebienSearch" autocomplete="off"
               class="w-full border rounded-lg p-2 mb-2 focus:ring focus:ring-blue-300"
               placeholder="Nom du type de bien">
        <input type="hidden" id="typebienIdInput">
        <div id="typebienResults" class="absolute z-10 bg-white border rounded-lg shadow-lg mt-1 w-full hidden"></div>
      </div>

      <div class="md:col-span-2">
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 w-full">
          ➕ Ajouter un bien
        </button>
      </div>
    </form>

    <!-- Message -->
    <p id="message" class="text-green-600 font-semibold mb-4"></p>

    <!-- Recherche -->
    <form id="form-search" class="mb-6 flex flex-col md:flex-row gap-4">
      <div class="flex-1">
        <label class="block text-gray-700 mb-2">Rechercher un bien :</label>
        <input type="text" id="search" placeholder="Ex: Villa"
               class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-green-300">
      </div>
      <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 self-end">
        🔍 Rechercher
      </button>
    </form>

    <!-- Liste -->
    <h2 class="text-xl font-semibold mb-2">📋 Liste des biens</h2>
    <div id="biens-list" class="border rounded-lg p-4 bg-gray-50">
      Chargement...
    </div>
  </div>

  <script>
    let bienEditId = null;

    // --- Chargement des biens ---
    async function loadBiens(search = "") {
      try {
        const url = "bien_traitement.php?action=" + (search ? "search&search=" + encodeURIComponent(search) : "getAll");
        const res = await fetch(url);
        const data = await res.json();

        let html = "";
        data.forEach(b => {
          html += `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 border-b py-2">
              <div>
                <span class="font-semibold">${b.nomBien}</span>
                <div class="text-gray-600 text-sm">${b.descriptionBien}</div>
                <div class="text-gray-500 text-xs">${b.rueBien} ${b.compBien ? ' - ' + b.compBien : ''}</div>
              </div>
              <div>
                <div>Superficie : <span class="font-semibold">${b.superficieBien} m²</span></div>
                <div>Animaux : <span class="font-semibold">${b.animauxBien == 1 ? 'Oui' : 'Non'}</span></div>
                <div>Couchages : <span class="font-semibold">${b.nbCouchagesBien}</span></div>
                <div>Commune : <span class="font-semibold">${b.nom_commune ?? b.id_commune}</span></div>
                <div>Type : <span class="font-semibold">${b.des_typebien ?? b.id_typebien}</span></div>
              </div>
            </div>
          `;
        });

        document.getElementById("biens-list").innerHTML = html || "<p>Aucun bien trouvé.</p>";
      } catch (error) {
        document.getElementById("biens-list").innerHTML = "<p class='text-red-600'>Erreur de chargement.</p>";
      }
    }

    // --- Création de bien ---
    document.getElementById("form-create").addEventListener("submit", async e => {
      e.preventDefault();
      const formData = new FormData();
      formData.append("action", "create");
      formData.append("nomBien", document.getElementById("nomBien").value);
      formData.append("descriptionBien", document.getElementById("descriptionBien").value);
      formData.append("rueBien", document.getElementById("rueBien").value);
      formData.append("compBien", document.getElementById("compBien").value);
      formData.append("superficieBien", document.getElementById("superficieBien").value);
      formData.append("animauxBien", document.getElementById("animauxBien").value);
      formData.append("nbCouchagesBien", document.getElementById("nbCouchagesBien").value);
      formData.append("id_commune", document.getElementById("communeIdInput").value);
      formData.append("id_typebien", document.getElementById("typebienIdInput").value);

      try {
        const res = await fetch("bien_traitement.php", { method: "POST", body: formData });
        const data = await res.json();
        if (data.success) {
          document.getElementById("message").innerText = "✅ Nouveau bien créé !";
          document.getElementById("form-create").reset();
          document.getElementById("communeIdInput").value = "";
          document.getElementById("typebienIdInput").value = "";
          loadBiens();
        } else {
          document.getElementById("message").innerText = "❌ Erreur: " + (data.message || "Erreur lors de la création.");
        }
      } catch (error) {
        document.getElementById("message").innerText = "❌ Erreur de connexion.";
      }
    });

    // --- Recherche biens ---
    document.getElementById("form-search").addEventListener("submit", e => {
      e.preventDefault();
      const search = document.getElementById("search").value;
      loadBiens(search);
    });

    // --- Auto-complétion communes ---
    const communeSearch = document.getElementById('communeSearch');
    const communeIdInput = document.getElementById('communeIdInput');
    const communesResults = document.getElementById('communesResults');

    communeSearch.addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length >= 2) searchCommunes(query);
        else {
            communesResults.classList.add('hidden');
            communesResults.innerHTML = '';
        }
    });

    function searchCommunes(query) {
        communesResults.innerHTML = '<div class="p-3 text-center text-gray-600">Recherche en cours...</div>';
        communesResults.classList.remove('hidden');
        fetch(`../../ajax/ajax_bien.php?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.communes.length > 0) displayCommuneResults(data.communes);
                else communesResults.innerHTML = '<div class="p-3 text-center text-gray-600">Aucune commune trouvée</div>';
            })
            .catch(() => communesResults.innerHTML = '<div class="p-3 text-center text-red-600">Erreur de connexion</div>');
    }

    function displayCommuneResults(communes) {
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

    // --- Auto-complétion types de bien ---
    const typeSearch = document.getElementById('typebienSearch');
    const typeIdInput = document.getElementById('typebienIdInput');
    const typeResults = document.getElementById('typebienResults');

    typeSearch.addEventListener('input', function() {
        const query = this.value.trim();
        if (query.length >= 1) searchTypeBien(query);
        else {
            typeResults.classList.add('hidden');
            typeResults.innerHTML = '';
        }
    });

    function searchTypeBien(query) {
        typeResults.innerHTML = '<div class="p-3 text-center text-gray-600">Recherche en cours...</div>';
        typeResults.classList.remove('hidden');
        fetch(`../../ajax/ajax_typebiens.php?action=search&search=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.length > 0) displayTypeResults(data.data);
                else typeResults.innerHTML = '<div class="p-3 text-center text-gray-600">Aucun type trouvé</div>';
            })
            .catch(() => typeResults.innerHTML = '<div class="p-3 text-center text-red-600">Erreur de connexion</div>');
    }

    function displayTypeResults(types) {
        typeResults.innerHTML = '';
        types.forEach(t => {
            const item = document.createElement('div');
            item.className = 'p-2 cursor-pointer hover:bg-blue-100';
            item.innerHTML = `<div class="font-semibold">${t.des_typebien}</div>`;
            item.addEventListener('click', () => {
                typeSearch.value = t.des_typebien;
                typeIdInput.value = t.id_typebien;
                typeResults.classList.add('hidden');
            });
            typeResults.appendChild(item);
        });
    }

    // --- Initial load ---
    loadBiens();
  </script>
</body>
</html>
