<?php
require_once '../../include/db.php';
require_once '../../include/csrf.php';
require_once 'prestation_class.php';
require_once 'prestation_traitement.php';

$controller = new PrestationController($pdo);
$message = '';
$message_type = ''; // Pour gérer la couleur du message (success/error)

// Gérer l'ID de la prestation en cours d'édition
$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $libelle = $_POST['libelle_prestation'] ?? '';
            if (!empty($libelle)) {
                $result = $controller->create($libelle);
                $message = $result ? "✅ Prestation ajoutée avec succès." : "❌ Erreur lors de l'ajout.";
                $message_type = $result ? 'success' : 'error';
            } else {
                $message = "❌ Le libellé est requis.";
                $message_type = 'error';
            }
        } elseif ($_POST['action'] === 'update') {
            $id = $_POST['id_prestation'] ?? 0;
            $libelle = $_POST['libelle_prestation'] ?? '';
            if ($id > 0 && !empty($libelle)) {
                $result = $controller->update($id, $libelle);
                $message = $result ? "✅ Prestation modifiée avec succès." : "❌ Erreur lors de la modification.";
                $message_type = $result ? 'success' : 'error';
                $edit_id = null; // Revenir à la liste après modification
            } else {
                $message = "❌ ID ou libellé invalide.";
                $message_type = 'error';
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id_prestation'] ?? 0;
            if ($id > 0) {
                $result = $controller->delete($id);
                $message = $result ? "✅ Prestation supprimée avec succès." : "❌ Erreur lors de la suppression.";
                $message_type = $result ? 'success' : 'error';
            } else {
                $message = "❌ ID invalide.";
                $message_type = 'error';
            }
        }
    }
}

// Gérer la recherche
$search = $_GET['search'] ?? '';
$prestations = $search ? $controller->searchPrestations($search) : $controller->getAllPrestations();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des prestations</title>
    <meta name="description" content="Interface de gestion des prestations pour l'administration Holidaze.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?php
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        echo $scheme . '://' . $_SERVER['HTTP_HOST'] . '/Pages/Prestations/prestation_form.php';
    ?>">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
<?php include '../header.php'; ?>

    <main class="max-w-2xl mx-auto mt-10 bg-white shadow-md rounded-lg p-6">
        <h1 class="text-2xl font-bold mb-4">Gestion des préstations</h1>
        <!-- Message -->
        <?php if ($message): ?>
            <p class="text-<?php echo $message_type === 'success' ? 'green-600' : 'red-600'; ?> font-semibold mb-4">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <!-- Formulaire d'ajout -->
        <form action="" method="POST" class="mb-6">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <label class="block text-gray-700 mb-2">Nom de la prestation :</label>
            <input type="text" name="libelle_prestation" required
                   class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300"
                   placeholder="Ex: Wifi, Parking...">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                ➕ Ajouter une prestation
            </button>
        </form>

        <!-- Formulaire de recherche -->
        <form action="" method="GET" class="mb-6">
            <label class="block text-gray-700 mb-2">Rechercher une prestation :</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                   class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-green-300"
                   placeholder="Ex: Wifi">
            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
                🔍 Rechercher
            </button>
        </form>

        <!-- Liste des prestations -->
        <h2 class="text-xl font-semibold mb-2">📋 Liste des prestations</h2>
        <div class="border rounded-lg p-4 bg-gray-50">
            <?php if (empty($prestations)): ?>
                <p>Aucune prestation trouvée.</p>
            <?php else: ?>
                <?php foreach ($prestations as $prestation): ?>
                    <?php if ($edit_id === $prestation['id_prestation']): ?>
                        <!-- Mode édition -->
                        <form action="" method="POST" class="flex justify-between items-center border-b py-2 gap-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id_prestation" value="<?php echo $prestation['id_prestation']; ?>">
                            <input type="text" name="libelle_prestation"
                                   value="<?php echo htmlspecialchars($prestation['libelle_prestation']); ?>"
                                   class="w-full border rounded-lg p-2 focus:ring focus:ring-yellow-300" required>
                            <button type="submit" class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">💾</button>
                            <a href="?search=<?php echo urlencode($search); ?>" 
                               class="bg-gray-300 text-gray-700 px-3 py-1 rounded hover:bg-gray-400">Annuler</a>
                        </form>
                    <?php else: ?>
                        <!-- Mode affichage -->
                        <div class="flex justify-between items-center border-b py-2">
                            <span><?php echo htmlspecialchars($prestation['libelle_prestation']); ?></span>
                            <div>
                                <a href="?edit_id=<?php echo $prestation['id_prestation']; ?>&search=<?php echo urlencode($search); ?>"
                                   class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">✏️ Modifier</a>
                                <form action="" method="POST" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id_prestation" value="<?php echo $prestation['id_prestation']; ?>">
                                    <button type="submit" onclick="return confirm('Supprimer cette prestation ?')"
                                            class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">🗑️ Supprimer</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>