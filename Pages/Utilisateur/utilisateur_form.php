<?php
session_start();

if (!isset($_SESSION['utilisateur_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
  header('Location: ../Formulaires/connexion.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestion des utilisateurs - Holidaze</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 text-gray-800">

<?php include '../header.php'; ?>

<main class="max-w-7xl mx-auto px-4 py-10 lg:py-14">
  <section class="bg-gradient-to-br from-indigo-600 to-purple-700 text-white rounded-2xl shadow-xl p-8 lg:p-10 mb-8">
    <h1 class="text-3xl lg:text-4xl font-extrabold">Gestion des utilisateurs</h1>
    <p class="mt-2 text-indigo-100">Crée, modifie et sécurise les comptes de la plateforme.</p>
  </section>

  <section class="bg-white rounded-2xl shadow-xl p-6 lg:p-8 mb-8">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
      <h2 class="text-2xl font-bold">Créer un utilisateur</h2>
      <span class="text-sm text-gray-500">Un mail d'invitation sera envoyé pour définir le mot de passe</span>
    </div>

    <form id="create-user-form" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <input name="prenom" type="text" placeholder="Prénom" required class="w-full px-4 py-3 border rounded-xl focus:ring-4 focus:ring-blue-200 focus:border-blue-500">
      <input name="nom" type="text" placeholder="Nom" required class="w-full px-4 py-3 border rounded-xl focus:ring-4 focus:ring-blue-200 focus:border-blue-500">
      <input name="email" type="email" placeholder="Email" required class="w-full px-4 py-3 border rounded-xl focus:ring-4 focus:ring-blue-200 focus:border-blue-500">
      <input name="tel" type="text" placeholder="Téléphone" class="w-full px-4 py-3 border rounded-xl focus:ring-4 focus:ring-blue-200 focus:border-blue-500">
      <select name="role" class="w-full px-4 py-3 border rounded-xl focus:ring-4 focus:ring-blue-200 focus:border-blue-500">
        <option value="user">Locataire</option>
        <option value="proprietaire">Propriétaire</option>
        <option value="admin">Administrateur</option>
      </select>

      <div class="md:col-span-2 lg:col-span-3 flex justify-end">
        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-bold transition">
          <i class="fas fa-paper-plane mr-2"></i>Créer le compte et envoyer l'invitation
        </button>
      </div>
    </form>
  </section>

  <section class="bg-white rounded-2xl shadow-xl p-6 lg:p-8">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
      <h2 class="text-2xl font-bold">Comptes existants</h2>
      <div class="w-full lg:w-96">
        <input type="text" id="search" placeholder="Rechercher par nom, email, téléphone..." class="w-full px-4 py-3 border rounded-xl focus:ring-4 focus:ring-blue-200 focus:border-blue-500">
      </div>
    </div>

    <div id="desktop-table" class="hidden lg:block overflow-x-auto rounded-xl border border-gray-200">
      <table class="min-w-full">
        <thead class="bg-gray-100 text-gray-700">
          <tr>
            <th class="px-4 py-3 text-left">Photo</th>
            <th class="px-4 py-3 text-left">Nom complet</th>
            <th class="px-4 py-3 text-left">Email</th>
            <th class="px-4 py-3 text-left">Téléphone</th>
            <th class="px-4 py-3 text-center">Rôle</th>
            <th class="px-4 py-3 text-center">Statut</th>
            <th class="px-4 py-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody id="tbody">
          <tr><td colspan="7" class="text-center py-12 text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Chargement...</td></tr>
        </tbody>
      </table>
    </div>

    <div id="mobile-cards" class="grid grid-cols-1 gap-4 lg:hidden"></div>
  </section>
</main>

<div id="toast" class="fixed top-4 right-4 hidden px-5 py-3 rounded-xl shadow-lg text-white z-50"></div>

<div id="reset-modal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50 p-4">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
    <h3 class="text-xl font-bold mb-4">Réinitialiser le mot de passe</h3>
    <p id="reset-target" class="text-sm text-gray-600 mb-4"></p>
    <form id="reset-form" class="space-y-3">
      <input type="hidden" id="reset-user-id">
      <input type="password" id="reset-password" placeholder="Nouveau mot de passe" minlength="12" required class="w-full px-4 py-3 border rounded-xl focus:ring-4 focus:ring-blue-200 focus:border-blue-500">
      <input type="password" id="reset-confirm" placeholder="Confirmer le mot de passe" minlength="12" required class="w-full px-4 py-3 border rounded-xl focus:ring-4 focus:ring-blue-200 focus:border-blue-500">
      <div class="flex justify-end gap-3 pt-2">
        <button type="button" id="close-reset" class="px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200">Annuler</button>
        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold">Valider</button>
      </div>
    </form>
  </div>
</div>

<script>
const tbody = document.getElementById('tbody');
const mobileCards = document.getElementById('mobile-cards');
const searchInput = document.getElementById('search');
const toast = document.getElementById('toast');

let searchTimer;

function showToast(message, type = 'success') {
  toast.textContent = message;
  toast.className = 'fixed top-4 right-4 px-5 py-3 rounded-xl shadow-lg text-white z-50';
  toast.classList.add(type === 'success' ? 'bg-green-600' : 'bg-red-600');
  toast.classList.remove('hidden');
  setTimeout(() => toast.classList.add('hidden'), 2800);
}

function esc(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function fullName(user) {
  return `${user.prenom || ''} ${user.nom || ''}`.trim() || 'Utilisateur';
}

function roleLabel(role) {
  if (role === 'admin') return 'Administrateur';
  if (role === 'proprietaire') return 'Propriétaire';
  return 'Locataire';
}

function statusBadge(user) {
  return Number(user.actif) === 1
    ? '<span class="inline-flex px-2 py-1 text-xs rounded-full bg-green-100 text-green-700 font-semibold">Actif</span>'
    : '<span class="inline-flex px-2 py-1 text-xs rounded-full bg-red-100 text-red-700 font-semibold">Inactif</span>';
}

async function loadUsers(search = '') {
  const url = `utilisateur_traitement.php?action=${search ? `search&q=${encodeURIComponent(search)}` : 'getAll'}`;

  try {
    const response = await fetch(url);
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    const users = await response.json();
    if (!Array.isArray(users) || users.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center py-10 text-gray-500">Aucun utilisateur trouvé</td></tr>';
      mobileCards.innerHTML = '<div class="text-center py-10 text-gray-500 bg-white rounded-xl border">Aucun utilisateur trouvé</div>';
      return;
    }

    tbody.innerHTML = users.map((u) => {
      const safeName = esc(fullName(u));
      const safeEmail = esc(u.email);
      const safeTel = esc(u.tel || '-');
      const safePhoto = esc(u.photo_profil || 'default-avatar.png');

      return `
        <tr class="border-t hover:bg-gray-50 transition">
          <td class="px-4 py-3">
            <img src="../../Photo/profil/${safePhoto}" alt="Photo profil" class="w-12 h-12 rounded-full object-cover border border-gray-300" onerror="this.src='../../Photo/profil/default-avatar.png'">
          </td>
          <td class="px-4 py-3 font-semibold text-gray-800">${safeName}</td>
          <td class="px-4 py-3">${safeEmail}</td>
          <td class="px-4 py-3">${safeTel}</td>
          <td class="px-4 py-3 text-center">
            <select class="px-3 py-2 rounded-lg border bg-white" onchange="updateRole(${Number(u.id_utilisateur)}, this.value)">
              <option value="user" ${u.role === 'user' ? 'selected' : ''}>Locataire</option>
              <option value="proprietaire" ${u.role === 'proprietaire' ? 'selected' : ''}>Propriétaire</option>
              <option value="admin" ${u.role === 'admin' ? 'selected' : ''}>Administrateur</option>
            </select>
          </td>
          <td class="px-4 py-3 text-center">${statusBadge(u)}</td>
          <td class="px-4 py-3 text-center">
            <div class="flex items-center justify-center gap-2">
              <button onclick="toggleStatus(${Number(u.id_utilisateur)})" class="px-3 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm">Activer/Désactiver</button>
              <button onclick="openResetModal(${Number(u.id_utilisateur)}, '${safeName}')" class="px-3 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-sm">Mdp</button>
              <button onclick="deleteUser(${Number(u.id_utilisateur)})" class="px-3 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm">Supprimer</button>
            </div>
          </td>
        </tr>
      `;
    }).join('');

    mobileCards.innerHTML = users.map((u) => {
      const safeName = esc(fullName(u));
      const safeEmail = esc(u.email);
      const safeTel = esc(u.tel || '-');
      const safePhoto = esc(u.photo_profil || 'default-avatar.png');

      return `
        <article class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <img src="../../Photo/profil/${safePhoto}" alt="Photo profil" class="w-12 h-12 rounded-full object-cover border border-gray-300" onerror="this.src='../../Photo/profil/default-avatar.png'">
            <div>
              <h3 class="font-bold text-gray-800">${safeName}</h3>
              <p class="text-sm text-gray-500">${safeEmail}</p>
              <p class="text-sm text-gray-500">${safeTel}</p>
            </div>
          </div>
          <div class="mt-3 flex items-center justify-between gap-2">
            ${statusBadge(u)}
            <select class="px-3 py-2 rounded-lg border bg-white text-sm" onchange="updateRole(${Number(u.id_utilisateur)}, this.value)">
              <option value="user" ${u.role === 'user' ? 'selected' : ''}>Locataire</option>
              <option value="proprietaire" ${u.role === 'proprietaire' ? 'selected' : ''}>Propriétaire</option>
              <option value="admin" ${u.role === 'admin' ? 'selected' : ''}>Administrateur</option>
            </select>
          </div>
          <div class="mt-3 grid grid-cols-3 gap-2">
            <button onclick="toggleStatus(${Number(u.id_utilisateur)})" class="px-2 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-xs">Statut</button>
            <button onclick="openResetModal(${Number(u.id_utilisateur)}, '${safeName}')" class="px-2 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white text-xs">Mdp</button>
            <button onclick="deleteUser(${Number(u.id_utilisateur)})" class="px-2 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs">Suppr.</button>
          </div>
        </article>
      `;
    }).join('');
  } catch (error) {
    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-10 text-red-600">Erreur: ${esc(error.message)}</td></tr>`;
    mobileCards.innerHTML = `<div class="text-center py-10 text-red-600 bg-white rounded-xl border">Erreur: ${esc(error.message)}</div>`;
  }
}

async function postAction(formData) {
  const response = await fetch('utilisateur_traitement.php', {
    method: 'POST',
    body: formData
  });
  if (!response.ok) {
    throw new Error(`HTTP ${response.status}`);
  }
  return response.json();
}

async function updateRole(id, role) {
  try {
    const fd = new FormData();
    fd.append('action', 'updateRole');
    fd.append('id', id);
    fd.append('role', role);
    const result = await postAction(fd);
    if (!result.success) throw new Error(result.message || 'Erreur de mise a jour');
    showToast('Role mis a jour');
    loadUsers(searchInput.value.trim());
  } catch (error) {
    showToast(error.message, 'error');
  }
}

async function toggleStatus(id) {
  try {
    const fd = new FormData();
    fd.append('action', 'toggleActif');
    fd.append('id', id);
    const result = await postAction(fd);
    if (!result.success) throw new Error(result.message || 'Erreur de statut');
    showToast('Statut modifie');
    loadUsers(searchInput.value.trim());
  } catch (error) {
    showToast(error.message, 'error');
  }
}

async function deleteUser(id) {
  if (!confirm('Supprimer cet utilisateur ?')) return;
  try {
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('id', id);
    const result = await postAction(fd);
    if (!result.success) throw new Error(result.message || 'Suppression impossible');
    showToast('Utilisateur supprime');
    loadUsers(searchInput.value.trim());
  } catch (error) {
    showToast(error.message, 'error');
  }
}

const createForm = document.getElementById('create-user-form');
createForm.addEventListener('submit', async (event) => {
  event.preventDefault();

  const formData = new FormData(createForm);

  formData.append('action', 'createUser');

  try {
    const result = await postAction(formData);
    if (!result.success) throw new Error(result.message || 'Creation impossible');
    showToast('Utilisateur cree avec succes');
    createForm.reset();
    loadUsers(searchInput.value.trim());
  } catch (error) {
    showToast(error.message, 'error');
  }
});

searchInput.addEventListener('input', () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    loadUsers(searchInput.value.trim());
  }, 250);
});

const resetModal = document.getElementById('reset-modal');
const resetTarget = document.getElementById('reset-target');
const resetUserId = document.getElementById('reset-user-id');
const resetPassword = document.getElementById('reset-password');
const resetConfirm = document.getElementById('reset-confirm');

function openResetModal(id, name) {
  resetUserId.value = id;
  resetTarget.textContent = `Compte: ${name}`;
  resetPassword.value = '';
  resetConfirm.value = '';
  resetModal.classList.remove('hidden');
  resetModal.classList.add('flex');
}

function closeResetModal() {
  resetModal.classList.add('hidden');
  resetModal.classList.remove('flex');
}

document.getElementById('close-reset').addEventListener('click', closeResetModal);
resetModal.addEventListener('click', (event) => {
  if (event.target === resetModal) {
    closeResetModal();
  }
});

document.getElementById('reset-form').addEventListener('submit', async (event) => {
  event.preventDefault();

  const password = resetPassword.value;
  const confirmPassword = resetConfirm.value;

  if (password.length < 12) {
    showToast('Le mot de passe doit contenir 12 caracteres minimum', 'error');
    return;
  }
  if (password !== confirmPassword) {
    showToast('La confirmation du mot de passe ne correspond pas', 'error');
    return;
  }

  try {
    const fd = new FormData();
    fd.append('action', 'resetPassword');
    fd.append('id', resetUserId.value);
    fd.append('password', password);
    fd.append('confirmPassword', confirmPassword);

    const result = await postAction(fd);
    if (!result.success) throw new Error(result.message || 'Reinitialisation impossible');

    closeResetModal();
    showToast('Mot de passe reinitialise');
  } catch (error) {
    showToast(error.message, 'error');
  }
});

loadUsers();
</script>

</body>
</html>