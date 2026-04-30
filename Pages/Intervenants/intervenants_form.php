<?php
require_once '../../include/db.php';
include '../header.php';
require_once 'intervenants_class.php';
require_once 'intervenants_traitement.php';

$manager = new IntervenantManager($pdo);

$message = '';
$message_type = '';

$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : null;

// POST (create / update / delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'create') {
        $nom = trim($_POST['nom_intervenant'] ?? '');
        $prenom = trim($_POST['prenom_intervenant'] ?? '');

        if ($nom !== '' && $prenom !== '') {
            $i = new Intervenant(null, $nom, $prenom);
            $ok = $manager->add($i);
            $message = $ok ? "✅ Intervenant ajouté avec succès." : "❌ Erreur lors de l'ajout.";
            $message_type = $ok ? 'success' : 'error';
        } else {
            $message = "❌ Les champs nom et prénom sont obligatoires.";
            $message_type = 'error';
        }

    } elseif ($action === 'update') {
        $id = (int)($_POST['id_intervenant'] ?? 0);
        $nom = trim($_POST['nom_intervenant'] ?? '');
        $prenom = trim($_POST['prenom_intervenant'] ?? '');

        if ($id > 0 && $nom !== '' && $prenom !== '') {
            $i = $manager->getById($id);
            if ($i !== null) {
                $i->setNomIntervenant($nom);
                $i->setPrenomIntervenant($prenom);
                $ok = $manager->update($i);
                $message = $ok ? "✅ Intervenant modifié avec succès." : "❌ Erreur lors de la modification.";
                $message_type = $ok ? 'success' : 'error';
                $edit_id = null;
            } else {
                $message = "❌ Intervenant introuvable.";
                $message_type = 'error';
            }
        } else {
            $message = "❌ Champs invalides.";
            $message_type = 'error';
        }

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id_intervenant'] ?? 0);
        if ($id > 0) {
            $ok = $manager->delete($id);
            $message = $ok ? "✅ Intervenant supprimé avec succès." : "❌ Erreur lors de la suppression.";
            $message_type = $ok ? 'success' : 'error';
        } else {
            $message = "❌ ID invalide.";
            $message_type = 'error';
        }
    }
}

// Liste
$intervenants = $manager->getAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des intervenants</title>
    <meta name="description" content="Interface de gestion des intervenants pour l'administration Holidaze.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?php
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        echo $scheme . '://' . $_SERVER['HTTP_HOST'] . '/Pages/Intervenants/intervenants_form.php';
    ?>">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
<main class="max-w-4xl mx-auto mt-10 space-y-8">
    <h1 class="text-3xl font-bold text-slate-800">Gestion des intervenants</h1>

    <?php if ($message): ?>
        <div class="border-l-4 px-4 py-3 rounded <?php echo $message_type === 'success'
            ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
            : 'border-rose-500 bg-rose-50 text-rose-700'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Formulaire ajout -->
    <section class="bg-white shadow rounded-xl p-6">
        <h2 class="text-xl font-semibold text-slate-800 mb-4">➕ Ajouter un intervenant</h2>

        <form method="post" class="space-y-4">
            <input type="hidden" name="action" value="create">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nom</label>
                    <input type="text" name="nom_intervenant" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           placeholder="Ex: Dupont">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Prénom</label>
                    <input type="text" name="prenom_intervenant" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           placeholder="Ex: Jean">
                </div>
            </div>

            <button type="submit"
                    class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                ➕ Ajouter l'intervenant
            </button>
        </form>
    </section>

    <!-- Liste -->
    <section class="bg-white shadow rounded-xl p-6">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">📋 Liste des intervenants</h2>

        <?php if (empty($intervenants)): ?>
            <p class="text-sm text-slate-600">Aucun intervenant trouvé.</p>
        <?php else: ?>
            <div class="divide-y divide-slate-200">
                <?php foreach ($intervenants as $i): ?>
                    <?php if ($edit_id === $i->getIdIntervenant()): ?>
                        <!-- Édition -->
                        <form method="post" class="py-3 space-y-3">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id_intervenant" value="<?php echo $i->getIdIntervenant(); ?>">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Nom</label>
                                    <input type="text" name="nom_intervenant"
                                           value="<?php echo htmlspecialchars($i->getNomIntervenant()); ?>"
                                           class="w-full rounded-lg border border-slate-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Prénom</label>
                                    <input type="text" name="prenom_intervenant"
                                           value="<?php echo htmlspecialchars($i->getPrenomIntervenant()); ?>"
                                           class="w-full rounded-lg border border-slate-300 px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500" required>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <button type="submit"
                                        class="inline-flex items-center bg-yellow-500 text-white px-3 py-1 rounded-lg text-xs font-medium hover:bg-yellow-600">
                                    💾 Enregistrer
                                </button>
                                <a href="intervenants_form.php"
                                   class="inline-flex items-center bg-slate-200 text-slate-700 px-3 py-1 rounded-lg text-xs font-medium hover:bg-slate-300">
                                    Annuler
                                </a>
                            </div>
                        </form>
                    <?php else: ?>
                        <!-- Affichage -->
                        <div class="py-3 flex justify-between items-center">
                            <div>
                                <div class="text-sm font-semibold text-slate-800">
                                    <?php echo htmlspecialchars($i->getNomIntervenant() . ' ' . $i->getPrenomIntervenant()); ?>
                                </div>
                                <div class="text-xs text-slate-500">
                                    ID : <?php echo $i->getIdIntervenant(); ?>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <a href="intervenants_form.php?edit_id=<?php echo $i->getIdIntervenant(); ?>"
                                   class="inline-flex items-center bg-yellow-500 text-white px-3 py-1 rounded-lg text-xs font-medium hover:bg-yellow-600">
                                    ✏️ Modifier
                                </a>
                                <form method="post" class="inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id_intervenant" value="<?php echo $i->getIdIntervenant(); ?>">
                                    <button type="submit"
                                            onclick="return confirm('Supprimer cet intervenant ?')"
                                            class="inline-flex items-center bg-red-500 text-white px-3 py-1 rounded-lg text-xs font-medium hover:bg-red-600">
                                        🗑️ Supprimer
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
</body>
</html>