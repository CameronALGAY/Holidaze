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
      <div>
        <label class="block text-gray-700 mb-2">Commune :</label>
        <input type="text" id="communeSearch" autocomplete="off"
               class="w-full border rounded-lg p-2 mb-2 focus:ring focus:ring-blue-300"
               placeholder="Nom de la commune">
        <input type="hidden" id="communeIdInput">
        <div id="resultsContainer" class="absolute z-10 bg-white border rounded-lg shadow-lg mt-1 w-full hidden"></div>
      </div>
      <div>
        <label class="block text-gray-700 mb-2">Type de bien :</label>
        <input type="number" id="id_typebien" required class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300" placeholder="ID type bien">
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
    let bienEditId = null; // ID du bien en édition

    async function loadBiens(search = "") {
      try {
        let url = "bien_traitement.php?action=" + (search ? "search&search=" + encodeURIComponent(search) : "getAll");
        const res = await fetch(url);
        const data = await res.json();

        if (!Array.isArray(data)) {
          document.getElementById("biens-list").innerHTML = "<p class='text-red-600'>Erreur: " + (data.message || "Format de réponse invalide") + "</p>";
          return;
        }

        let html = "";
        data.forEach(b => {
          if (bienEditId === b.idBien) {
            // Edition inline
            html += `
              <form onsubmit="return saveBienEdit(${b.idBien});" class="grid grid-cols-1 md:grid-cols-2 gap-2 border-b py-2">
                <input type="text" id="edit-nomBien-${b.idBien}" value="${b.nomBien.replace(/"/g, '&quot;')}" class="border rounded-lg p-2 mb-2" required>
                <input type="text" id="edit-descriptionBien-${b.idBien}" value="${b.descriptionBien.replace(/"/g, '&quot;')}" class="border rounded-lg p-2 mb-2" required>
                <input type="text" id="edit-rueBien-${b.idBien}" value="${b.rueBien.replace(/"/g, '&quot;')}" class="border rounded-lg p-2 mb-2" required>
                <input type="text" id="edit-compBien-${b.idBien}" value="${b.compBien ? b.compBien.replace(/"/g, '&quot;') : ''}" class="border rounded-lg p-2 mb-2">
                <input type="number" id="edit-superficieBien-${b.idBien}" value="${b.superficieBien}" class="border rounded-lg p-2 mb-2" required>
                <select id="edit-animauxBien-${b.idBien}" class="border rounded-lg p-2 mb-2">
                  <option value="1" ${b.animauxBien == 1 ? 'selected' : ''}>Oui</option>
                  <option value="0" ${b.animauxBien == 0 ? 'selected' : ''}>Non</option>
                </select>
                <input type="number" id="edit-nbCouchagesBien-${b.idBien}" value="${b.nbCouchagesBien}" class="border rounded-lg p-2 mb-2" required>
                <input type="number" id="edit-id_commune-${b.idBien}" value="${b.id_commune}" class="border rounded-lg p-2 mb-2" required>
                <input type="number" id="edit-id_typebien-${b.idBien}" value="${b.id_typebien}" class="border rounded-lg p-2 mb-2" required>
                <div class="md:col-span-2 flex gap-2">
                  <button type="submit" class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">💾 Enregistrer</button>
                  <button type="button" onclick="cancelBienEdit()" class="bg-gray-300 text-gray-700 px-3 py-1 rounded hover:bg-gray-400">Annuler</button>
                </div>
              </form>
            `;
          } else {
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
                <div class="md:col-span-2 flex gap-2 justify-end mt-2">
                  <button onclick="startBienEdit(${b.idBien})"
                    class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">✏️ Modifier</button>
                  <button onclick="deleteBien(${b.idBien})"
                    class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">🗑️ Supprimer</button>
                </div>
              </div>
            `;
          }
        });
        document.getElementById("biens-list").innerHTML = html || "<p>Aucun bien trouvé.</p>";
      } catch (error) {
        document.getElementById("biens-list").innerHTML = "<p class='text-red-600'>Erreur de chargement. Consultez la console.</p>";
      }
    }

    function startBienEdit(id) {
      bienEditId = id;
      loadBiens();
    }

    function cancelBienEdit() {
      bienEditId = null;
      loadBiens();
    }

    async function saveBienEdit(id) {
      const formData = new FormData();
      formData.append("action", "update");
      formData.append("idBien", id);
      formData.append("nomBien", document.getElementById("edit-nomBien-" + id).value);
      formData.append("descriptionBien", document.getElementById("edit-descriptionBien-" + id).value);
      formData.append("rueBien", document.getElementById("edit-rueBien-" + id).value);
      formData.append("compBien", document.getElementById("edit-compBien-" + id).value);
      formData.append("superficieBien", document.getElementById("edit-superficieBien-" + id).value);
      formData.append("animauxBien", document.getElementById("edit-animauxBien-" + id).value);
      formData.append("nbCouchagesBien", document.getElementById("edit-nbCouchagesBien-" + id).value);
      formData.append("id_commune", document.getElementById("edit-id_commune-" + id).value);
      formData.append("id_typebien", document.getElementById("edit-id_typebien-" + id).value);

      try {
        const res = await fetch("bien_traitement.php", { method: "POST", body: formData });
        const data = await res.json();

        if (data.success) {
          document.getElementById("message").innerText = "✅ Bien modifié.";
          bienEditId = null;
          loadBiens();
        } else {
          document.getElementById("message").innerText = "❌ Erreur: " + (data.message || "Erreur modification");
        }
      } catch (error) {
        document.getElementById("message").innerText = "❌ Erreur de connexion.";
      }
      return false; // Empêche la soumission classique du formulaire
    }

    // Ajout
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
      formData.append("id_commune", document.getElementById("id_commune").value);
      formData.append("id_typebien", document.getElementById("id_typebien").value);

      try {
        const res = await fetch("bien_traitement.php", { method: "POST", body: formData });
        const data = await res.json();

        if (data.success) {
          document.getElementById("message").innerText = "✅ Nouveau bien créé !";
          document.getElementById("form-create").reset();
          loadBiens();
        } else {
          document.getElementById("message").innerText = "❌ Erreur: " + (data.message || "Erreur lors de la création.");
        }
      } catch (error) {
        document.getElementById("message").innerText = "❌ Erreur de connexion.";
      }
    });

    // Recherche
    document.getElementById("form-search").addEventListener("submit", e => {
      e.preventDefault();
      const search = document.getElementById("search").value;
      loadBiens(search);
    });

    // Suppression
    async function deleteBien(id) {
      if (!confirm("Supprimer ce bien ?")) return;
      const formData = new FormData();
      formData.append("action", "delete");
      formData.append("idBien", id);

      try {
        const res = await fetch("bien_traitement.php", { method: "POST", body: formData });
        const data = await res.json();

        if (data.success) {
          document.getElementById("message").innerText = "✅ Bien supprimé.";
          loadBiens();
        } else {
          alert("❌ Erreur: " + (data.message || "Erreur suppression"));
        }
      } catch (error) {
        alert("❌ Erreur de connexion");
      }
    }

    // Charger au démarrage
    loadBiens();
  </script>
</body>
</html>