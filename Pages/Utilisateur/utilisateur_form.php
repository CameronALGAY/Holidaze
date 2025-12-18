<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestion des utilisateurs - Holidaze</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50">

<?php include '../header.php'; ?>

<div class="max-w-7xl mx-auto px-4 py-12">
  <h1 class="text-4xl font-bold text-center mb-10">Gestion des utilisateurs</h1>

  <div class="max-w-md mx-auto mb-10">
    <input type="text" id="search" placeholder="Rechercher par nom, email, tel..."
           class="w-full px-6 py-4 rounded-xl border shadow focus:ring-4 focus:ring-blue-300">
  </div>

  <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
    <table class="w-full">
      <thead class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
        <tr>
          <th class="px-6 py-4 text-left">Photo</th>
          <th class="px-6 py-4 text-left">Nom complet</th>
          <th class="px-6 py-4 text-left">Email</th>
          <th class="px-6 py-4 text-left">Téléphone</th>
          <th class="px-6 py-4 text-center">Rôle</th>
          <th class="px-6 py-4 text-center">Statut</th>
          <th class="px-6 py-4 text-center">Mot de passe</th>
          <th class="px-6 py-4 text-center">Actions</th>
        </tr>
      </thead>
      <tbody id="tbody">
        <tr><td colspan="8" class="text-center py-16"><i class="fas fa-spinner fa-spin text-4xl text-gray-400"></i></td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- MODALE MOT DE PASSE TEMPORAIRE -->
<div id="passwordModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
  <div class="bg-white rounded-2xl p-8 max-w-md w-full shadow-2xl">
    <h3 class="text-2xl font-bold mb-4 text-center">Mot de passe temporaire généré !</h3>
    <div class="bg-gray-100 p-6 rounded-xl text-center mb-6">
      <p class="text-gray-600 mb-2">L'utilisateur pourra se connecter avec :</p>
      <p id="tempPassword" class="text-3xl font-bold text-blue-600 select-all"></p>
    </div>
    <div class="text-center">
      <button onclick="closeModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-bold">
        Fermer
      </button>
    </div>
  </div>
</div>

<script>
async function load(search = "") {
  const url = `utilisateur_traitement.php?action=${search ? 'search&q=' + encodeURIComponent(search) : 'getAll'}`;
  try {
    const res = await fetch(url);
    if (!res.ok) throw new Error("HTTP " + res.status);
    const users = await res.json();

    const tbody = document.getElementById("tbody");
    if (!Array.isArray(users) || users.length === 0) {
      tbody.innerHTML = `<tr><td colspan="8" class="text-center py-16 text-gray-500">Aucun utilisateur trouvé</td></tr>`;
      return;
    }

    tbody.innerHTML = users.map(u => {
      const roleColor = {
        admin: 'bg-purple-600',
        proprietaire: 'bg-blue-600',
        user: 'bg-gray-600'
      }[u.role] || 'bg-gray-600';

      const statusColor = u.actif == 1 ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700';
      const statusText = u.actif == 1 ? 'Actif' : 'Inactif';

      return `
        <tr class="hover:bg-gray-50 transition">
          <td class="px-6 py-4">
            <img src="../../Photo/profil/${u.photo_profil || 'default-avatar.png'}" 
                 class="w-14 h-14 rounded-full object-cover border-2 border-gray-300">
          </td>
          <td class="px-6 py-4 font-medium">${u.prenom || ''} ${u.nom || ''}</td>
          <td class="px-6 py-4">${u.email}</td>
          <td class="px-6 py-4">${u.tel || '-'}</td>
          <td class="px-6 py-4 text-center">
            <select onchange="updateRole(${u.id_utilisateur}, this.value)" 
                    class="px-4 py-2 rounded-lg text-white font-medium ${roleColor}">
              <option value="user"         ${u.role==='user'?'selected':''}>Locataire</option>
              <option value="proprietaire" ${u.role==='proprietaire'?'selected':''}>Propriétaire</option>
              <option value="admin"        ${u.role==='admin'?'selected':''}>Administrateur</option>
            </select>
          </td>
          <td class="px-6 py-4 text-center">
            <button onclick="toggleStatus(${u.id_utilisateur})"
                    class="${statusColor} text-white px-4 py-2 rounded-lg transition">
              ${statusText}
            </button>
          </td>
          <td class="px-6 py-4 text-center">
            <button onclick="resetPassword(${u.id_utilisateur}, '${u.prenom || 'Utilisateur'}')"
                    class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-medium">
              Réinitialiser
            </button>
          </td>
          <td class="px-6 py-4 text-center">
            <button onclick="deleteUser(${u.id_utilisateur})"
                    class="text-red-600 hover:text-red-800 font-bold">Supprimer</button>
          </td>
        </tr>`;
    }).join('');

  } catch (e) {
    document.getElementById("tbody").innerHTML = `<tr><td colspan="8" class="text-center py-10 text-red-600">Erreur: ${e.message}</td></tr>`;
  }
}

// Réinitialisation du mot de passe
async function resetPassword(id, name) {
  if (!confirm(`Réinitialiser le mot de passe de ${name} ?\nUn mot de passe temporaire sera généré.`)) return;

  const tempPass = "Temp" + Math.random().toString(36).substring(2, 8) + "!";
  
  const data = new FormData();
  data.append('action', 'resetPassword');
  data.append('id', id);
  data.append('password', tempPass);

  try {
    const res = await fetch('utilisateur_traitement.php', { method: 'POST', body: data });
    const result = await res.json();

    if (result.success) {
      document.getElementById('tempPassword').textContent = tempPass;
      document.getElementById('passwordModal').classList.remove('hidden');
    } else {
      alert("Erreur : " + (result.message || "Impossible de réinitialiser"));
    }
  } catch (e) {
    alert("Erreur de connexion");
  }
}

function closeModal() {
  document.getElementById('passwordModal').classList.add('hidden');
}

// Les autres fonctions (updateRole, toggleStatus, deleteUser) restent identiques
async function updateRole(id, role) {
  const data = new URLSearchParams();
  data.append('action', 'updateRole');
  data.append('id', id);
  data.append('role', role);
  await fetch('utilisateur_traitement.php', { method: 'POST', body: data });
  load(document.getElementById('search').value);
}

async function toggleStatus(id) {
  const data = new URLSearchParams();
  data.append('action', 'toggleActif');
  data.append('id', id);
  await fetch('utilisateur_traitement.php', { method: 'POST', body: data });
  load(document.getElementById('search').value);
}

async function deleteUser(id) {
  if (!confirm("Supprimer cet utilisateur ?")) return;
  const data = new URLSearchParams();
  data.append('action', 'delete');
  data.append('id', id);
  await fetch('utilisateur_traitement.php', { method: 'POST', body: data });
  load(document.getElementById('search').value);
}

// Recherche
document.getElementById('search').addEventListener('input', e => load(e.target.value));

// Chargement
load();
</script>
</body>
</html>