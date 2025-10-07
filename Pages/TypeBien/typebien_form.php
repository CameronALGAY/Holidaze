<?php
require_once '../../include/db.php';
require_once '../TypeBien/typebien_class.php';
<<<<<<< HEAD
require_once '../TypeBien/typebien_traitement.php'; // Contient TypeBienController et gestion POST
=======
require_once '../TypeBien/typebien_traitement.php';
>>>>>>> b8f0dd7b630595d9fee60f5e9d6425adcbb52a9f

$controller = new TypeBienController($pdo);
$types = $controller->getAllTypeBien();

<<<<<<< HEAD
// Messages de feedback
$successMsg = $_GET['success'] ?? null;
$errorMsg = $_GET['error'] ?? null;
?>

<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gestion des Types de Bien</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<div class="container mx-auto mt-10">

  <!-- Messages -->
  <?php if ($successMsg): ?>
    <div class="mb-4 p-4 bg-green-100 text-green-800 rounded shadow">Action effectuée avec succès !</div>
  <?php elseif ($errorMsg): ?>
    <div class="mb-4 p-4 bg-red-100 text-red-800 rounded shadow">
      <?php
      if ($errorMsg == 1) echo "Erreur lors de l'insertion.";
      elseif ($errorMsg == 2) echo "Le champ Description ne peut pas être vide.";
      else echo "Erreur inconnue.";
      ?>
    </div>
  <?php endif; ?>

  <!-- Formulaire -->
  <div class="bg-white rounded shadow mb-8">
    <div class="bg-blue-600 text-white px-6 py-4 rounded-t">
      <h4 class="text-lg font-semibold">Ajouter un Type de Bien</h4>
    </div>
    <div class="p-6">
      <form action="typebien_traitement.php" method="POST" class="flex flex-col md:flex-row gap-4">
        <input type="text" name="des_typebien" class="flex-1 px-4 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Description du type" required>
        <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">Enregistrer</button>
      </form>
    </div>
  </div>

  <!-- Tableau -->
  <div class="bg-white rounded shadow">
    <div class="bg-gray-700 text-white px-6 py-4 rounded-t">
      <h4 class="text-lg font-semibold">Liste des Types de Bien</h4>
    </div>
    <div class="p-6 overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-800 text-white">
          <tr>
            <th class="px-4 py-2">ID</th>
            <th class="px-4 py-2">Description</th>
            <th class="px-4 py-2 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <?php if ($types): ?>
            <?php foreach ($types as $type): ?>
              <tr>
                <td class="px-4 py-2"><?= htmlspecialchars($type['id_typebien']) ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($type['des_typebien']) ?></td>
                <td class="px-4 py-2 text-center flex gap-2 justify-center">
                  <a href="typebien_traitement.php?action=update&id=<?= $type['id_typebien'] ?>"
                     class="bg-yellow-400 hover:bg-yellow-500 text-white px-3 py-1 rounded transition">Modifier</a>
                  <a href="typebien_traitement.php?action=delete&id=<?= $type['id_typebien'] ?>"
                     class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded transition"
                     onclick="return confirm('Voulez-vous vraiment supprimer ce type de bien ?');">Supprimer</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="3" class="px-4 py-2 text-center text-gray-500">Aucun type de bien trouvé</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
=======
$successMsg = $_GET['success'] ?? null;
$errorMsg = $_GET['error'] ?? null;

// Pour savoir si on est en mode modification
$editId = $_GET['edit'] ?? null;
$editType = $editId ? $controller->getTypeBienById($editId) : null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gestion des types de bien</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

  <div class="max-w-2xl mx-auto bg-white shadow-md rounded-lg p-6">
    <h1 class="text-2xl font-bold mb-4">Gestion des types de bien</h1>

    <!-- Message -->
    <?php if ($successMsg): ?>
      <p class="text-green-600 font-semibold mb-4">✅ Action effectuée avec succès !</p>
    <?php elseif ($errorMsg): ?>
      <p class="text-red-600 font-semibold mb-4">
        <?php
        if ($errorMsg == 1) echo "❌ Erreur lors de l'insertion.";
        elseif ($errorMsg == 2) echo "❌ Le champ Description ne peut pas être vide.";
        else echo "❌ Erreur inconnue.";
        ?>
      </p>
    <?php endif; ?>

    <!-- Formulaire d'ajout -->
    <form action="typebien_traitement.php" method="POST" class="mb-6">
      <label class="block text-gray-700 mb-2">Description du type :</label>
      <input type="text" name="des_typebien" required
             class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300"
             placeholder="Ex: Appartement">
      <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
        ➕ Ajouter un type de bien
      </button>
    </form>

    <!-- Liste -->
    <h2 class="text-xl font-semibold mb-2">📋 Liste des types de bien</h2>
    <div class="border rounded-lg p-4 bg-gray-50">
      <?php if ($types): ?>
        <?php foreach ($types as $type): ?>
          <div class="flex justify-between items-center border-b py-2">
            <?php if ($editId == $type['id_typebien']): ?>
              <!-- Formulaire de modification inline -->
              <form action="typebien_traitement.php?action=update&id=<?= $type['id_typebien'] ?>" method="POST" class="flex flex-1 items-center gap-2">
                <input type="text" name="des_typebien"
                  value="<?= htmlspecialchars($editType['des_typebien']) ?>"
                  class="w-full border rounded-lg p-2 focus:ring focus:ring-yellow-300"
                  required>
                <button type="submit" class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">💾</button>
                <a href="typebien_form.php" class="bg-gray-300 text-gray-700 px-3 py-1 rounded hover:bg-gray-400">Annuler</a>
              </form>
            <?php else: ?>
              <span><?= htmlspecialchars($type['des_typebien']) ?></span>
              <div>
                <a href="typebien_form.php?edit=<?= $type['id_typebien'] ?>"
                   class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">✏️ Modifier</a>
                <a href="typebien_traitement.php?action=delete&id=<?= $type['id_typebien'] ?>"
                   class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600"
                   onclick="return confirm('Voulez-vous vraiment supprimer ce type de bien ?');">🗑️ Supprimer</a>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-gray-500">Aucun type de bien trouvé.</p>
      <?php endif; ?>
    </div>
  </div>

>>>>>>> b8f0dd7b630595d9fee60f5e9d6425adcbb52a9f
</body>
</html>
