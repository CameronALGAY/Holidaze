<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gestion des saisons</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

  <div class="max-w-2xl mx-auto bg-white shadow-md rounded-lg p-6">
    <h1 class="text-2xl font-bold mb-4">Gestion des saisons</h1>

    <!-- Formulaire d'ajout -->
    <form id="form-create" class="mb-6">
      <label class="block text-gray-700 mb-2">Nom de la saison :</label>
      <input type="text" id="libelle_saison" required
             class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300">
      <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
        ➕ Ajouter une saison
      </button>
    </form>

    <!-- Message -->
    <p id="message" class="text-green-600 font-semibold mb-4"></p>

    <!-- Recherche -->
    <form id="form-search" class="mb-6">
      <label class="block text-gray-700 mb-2">Rechercher une saison :</label>
      <input type="text" id="search" placeholder="Ex: Été"
             class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-green-300">
      <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
        🔍 Rechercher
      </button>
    </form>

    <!-- Liste -->
    <h2 class="text-xl font-semibold mb-2">📋 Liste des saisons</h2>
    <div id="saisons-list" class="border rounded-lg p-4 bg-gray-50">
      Chargement...
    </div>
  </div>

  <script>
    let saisonEditId = null; // ID de la saison en édition

    async function loadSaisons(search = "") {
      try {
        let url = "saison_traitement.php?action=" + (search ? "search&search=" + encodeURIComponent(search) : "getAll");
        const res = await fetch(url);
        const data = await res.json();

        if (!Array.isArray(data)) {
          document.getElementById("saisons-list").innerHTML = "<p class='text-red-600'>Erreur: " + (data.message || "Format de réponse invalide") + "</p>";
          return;
        }

        let html = "";
        data.forEach(s => {
          if (saisonEditId === s.id_saison) {
            // Affichage du champ texte pour édition
            html += `
              <form onsubmit="return saveSaisonEdit(${s.id_saison});" class="flex justify-between items-center border-b py-2 gap-2">
                <input type="text" id="edit-libelle-${s.id_saison}" value="${s.libelle_saison.replace(/"/g, '&quot;')}"
                  class="w-full border rounded-lg p-2 focus:ring focus:ring-yellow-300" required>
                <button type="submit" class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">💾</button>
                <button type="button" onclick="cancelSaisonEdit()" class="bg-gray-300 text-gray-700 px-3 py-1 rounded hover:bg-gray-400">Annuler</button>
              </form>
            `;
          } else {
            html += `
              <div class="flex justify-between items-center border-b py-2">
                <span>${s.libelle_saison}</span>
                <div>
                  <button onclick="startSaisonEdit(${s.id_saison})"
                    class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">✏️ Modifier</button>
                  <button onclick="deleteSaison(${s.id_saison})"
                    class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">🗑️ Supprimer</button>
                </div>
              </div>
            `;
          }
        });
        document.getElementById("saisons-list").innerHTML = html || "<p>Aucune saison trouvée.</p>";
      } catch (error) {
        document.getElementById("saisons-list").innerHTML = "<p class='text-red-600'>Erreur de chargement. Consultez la console.</p>";
      }
    }

    function startSaisonEdit(id) {
      saisonEditId = id;
      loadSaisons();
    }

    function cancelSaisonEdit() {
      saisonEditId = null;
      loadSaisons();
    }

    async function saveSaisonEdit(id) {
      const libelle = document.getElementById("edit-libelle-" + id).value;
      const formData = new FormData();
      formData.append("id", id);
      formData.append("libelle_saison", libelle);

      try {
        const res = await fetch("saison_traitement.php?action=update", { method: "POST", body: formData });
        const data = await res.json();

        if (data.success) {
          document.getElementById("message").innerText = "✅ Saison modifiée.";
          saisonEditId = null;
          loadSaisons();
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
      const libelle = document.getElementById("libelle_saison").value;

      const formData = new FormData();
      formData.append("libelle_saison", libelle);

      try {
        const res = await fetch("saison_traitement.php?action=create", { method: "POST", body: formData });
        const data = await res.json();

        if (data.success) {
          document.getElementById("message").innerText = "✅ Nouvelle saison créée !";
          document.getElementById("libelle_saison").value = "";
          loadSaisons();
        } else {
          document.getElementById("message").innerText = "❌ Erreur: " + (data.message || "Erreur lors de la création.");
        }
      } catch (error) {
        console.error("Erreur:", error);
        document.getElementById("message").innerText = "❌ Erreur de connexion.";
      }
    });

    // Recherche
    document.getElementById("form-search").addEventListener("submit", e => {
      e.preventDefault();
      const search = document.getElementById("search").value;
      loadSaisons(search);
    });

    // Suppression
    async function deleteSaison(id) {
      if (!confirm("Supprimer cette saison ?")) return;
      const formData = new FormData();
      formData.append("id", id);

      try {
        const res = await fetch("saison_traitement.php?action=delete", { method: "POST", body: formData });
        const data = await res.json();

        if (data.success) {
          document.getElementById("message").innerText = "✅ Saison supprimée.";
          loadSaisons();
        } else {
          alert("❌ Erreur: " + (data.message || "Erreur suppression"));
        }
      } catch (error) {
        console.error("Erreur:", error);
        alert("❌ Erreur de connexion");
      }
    }

    // Charger au démarrage
    loadSaisons();
  </script>
</body>
</html>