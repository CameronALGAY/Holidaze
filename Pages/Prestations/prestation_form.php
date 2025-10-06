<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gestion des prestations</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

  <div class="max-w-2xl mx-auto bg-white shadow-md rounded-lg p-6">
    <h1 class="text-2xl font-bold mb-4">Gestion des prestations</h1>

    <!-- Formulaire d'ajout -->
    <form id="form-create" class="mb-6">
      <label class="block text-gray-700 mb-2">Nom de la prestation :</label>
      <input type="text" id="libelle_prestation" required
             class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300">
      <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
        ➕ Ajouter une prestation
      </button>
    </form>

    <!-- Message -->
    <p id="message" class="text-green-600 font-semibold mb-4"></p>

    <!-- Recherche -->
    <form id="form-search" class="mb-6">
      <label class="block text-gray-700 mb-2">Rechercher une prestation :</label>
      <input type="text" id="search" placeholder="Ex: Wifi"
             class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-green-300">
      <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
        🔍 Rechercher
      </button>
    </form>

    <!-- Liste -->
    <h2 class="text-xl font-semibold mb-2">📋 Liste des prestations</h2>
    <div id="prestations-list" class="border rounded-lg p-4 bg-gray-50">
      Chargement...
    </div>
  </div>

  <script>
    async function loadPrestations(search = "") {
      try {
        let url = "prestation_traitement.php?action=" + (search ? "search&search=" + search : "getAll");
        const res = await fetch(url);
        const data = await res.json();

        // Vérifier si data est un tableau
        if (!Array.isArray(data)) {
          console.error("Réponse non valide:", data);
          document.getElementById("prestations-list").innerHTML = "<p class='text-red-600'>Erreur: " + (data.message || "Format de réponse invalide") + "</p>";
          return;
        }

        let html = "";
        data.forEach(p => {
          html += `
            <div class="flex justify-between items-center border-b py-2">
              <span>${p.libelle_prestation}</span>
              <div>
                <button onclick="editPrestation(${p.id}, '${p.libelle_prestation.replace(/'/g, "\\'")}')" 
                  class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">✏️ Modifier</button>
                <button onclick="deletePrestation(${p.id})"
                  class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">🗑️ Supprimer</button>
              </div>
            </div>`;
        });
        document.getElementById("prestations-list").innerHTML = html || "<p>Aucune prestation trouvée.</p>";
      } catch (error) {
        console.error("Erreur lors du chargement:", error);
        document.getElementById("prestations-list").innerHTML = "<p class='text-red-600'>Erreur de chargement. Consultez la console.</p>";
      }
    }

    // Ajout
    document.getElementById("form-create").addEventListener("submit", async e => {
      e.preventDefault();
      const libelle = document.getElementById("libelle_prestation").value;

      const formData = new FormData();
      formData.append("libelle_prestation", libelle);

      try {
        const res = await fetch("prestation_traitement.php?action=create", { method: "POST", body: formData });
        const data = await res.json();

        if (data.success) {
          document.getElementById("message").innerText = "✅ Nouvelle prestation créée !";
          document.getElementById("libelle_prestation").value = "";
          loadPrestations();
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
      loadPrestations(search);
    });

    // Suppression
    async function deletePrestation(id) {
      if (!confirm("Supprimer cette prestation ?")) return;
      const formData = new FormData();
      formData.append("id", id);

      try {
        const res = await fetch("prestation_traitement.php?action=delete", { method: "POST", body: formData });
        const data = await res.json();

        if (data.success) {
          document.getElementById("message").innerText = "✅ Prestation supprimée.";
          loadPrestations();
        } else {
          alert("❌ Erreur: " + (data.message || "Erreur suppression"));
        }
      } catch (error) {
        console.error("Erreur:", error);
        alert("❌ Erreur de connexion");
      }
    }

    // Edition (inline)
    async function editPrestation(id, libelle) {
      const nouveau = prompt("Modifier la prestation :", libelle);
      if (nouveau === null) return;

      const formData = new FormData();
      formData.append("id", id);
      formData.append("libelle_prestation", nouveau);

      try {
        const res = await fetch("prestation_traitement.php?action=update", { method: "POST", body: formData });
        const data = await res.json();

        if (data.success) {
          document.getElementById("message").innerText = "✅ Prestation modifiée.";
          loadPrestations();
        } else {
          alert("❌ Erreur: " + (data.message || "Erreur modification"));
        }
      } catch (error) {
        console.error("Erreur:", error);
        alert("❌ Erreur de connexion");
      }
    }

    // Charger au démarrage
    loadPrestations();
  </script>
</body>
</html>