<?php
require_once '../../include/db.php';
require_once '../TypeBien/typebien_class.php';
require_once '../TypeBien/typebien_traitement.php'; // Contient TypeBienController et gestion POST

$controller = new TypeBienController($pdo);
$types = $controller->getAllTypeBien();

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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>

</nav>

<div class="container mt-5">

  <!-- Messages -->
  <?php if ($successMsg): ?>
    <div class="alert alert-success">Action effectuée avec succès !</div>
  <?php elseif ($errorMsg): ?>
    <div class="alert alert-danger">
      <?php
      if ($errorMsg == 1) echo "Erreur lors de l'insertion.";
      elseif ($errorMsg == 2) echo "Le champ Description ne peut pas être vide.";
      else echo "Erreur inconnue.";
      ?>
    </div>
  <?php endif; ?>

  <!-- Formulaire -->
  <div class="card shadow-lg mb-4">
    <div class="card-header bg-primary text-white">
      <h4 class="mb-0">Ajouter un Type de Bien</h4>
    </div>
    <div class="card-body">
      <form action="typebien_traitement.php" method="POST" class="row g-3">
        <div class="col-md-8">
          <input type="text" name="des_typebien" class="form-control" placeholder="Description du type" required>
        </div>
        <div class="col-md-4">
          <button type="submit" class="btn btn-success w-100">Enregistrer</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tableau -->
  <div class="card shadow-lg">
    <div class="card-header bg-secondary text-white">
      <h4 class="mb-0">Liste des Types de Bien</h4>
    </div>
    <div class="card-body">
      <table class="table table-striped table-bordered align-middle">
        <thead class="table-dark">
          <tr>
            <th>ID</th>
            <th>Description</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($types): ?>
            <?php foreach ($types as $type): ?>
              <tr>
                <td><?= htmlspecialchars($type['id_typebien']) ?></td>
                <td><?= htmlspecialchars($type['des_typebien']) ?></td>
                <td class="text-center">
                  <a href="typebien_traitement.php?action=update&id=<?= $type['id_typebien'] ?>" 
                     class="btn btn-warning btn-sm">Modifier</a>
                  <a href="typebien_traitement.php?action=delete&id=<?= $type['id_typebien'] ?>" 
                     class="btn btn-danger btn-sm"
                     onclick="return confirm('Voulez-vous vraiment supprimer ce type de bien ?');">Supprimer</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="3" class="text-center">Aucun type de bien trouvé</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
