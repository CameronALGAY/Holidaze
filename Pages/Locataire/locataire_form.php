<?php
session_start();
require_once '../../include/db.php';
require_once 'locataire_class.php';
require_once 'locataire_traitement.php';

$controller = new LocataireController($pdo);
$message = '';
$message_type = ''; // Pour gérer la couleur du message (success/error)

// Gérer l'ID du locataire en cours d'édition
$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $locataire = new Locataire(
                null,
                trim($_POST['nom_locataire'] ?? ''),
                trim($_POST['prenom_locataire'] ?? ''),
                $_POST['dna_locataire'] ?? '',
                trim($_POST['email_locataire'] ?? ''),
                trim($_POST['rue_locataire'] ?? ''),
                $_POST['pass_locataire'] ?? '',
                trim($_POST['tel_locataire'] ?? ''),
                trim($_POST['comp_locataire'] ?? ''),
                $_POST['id_commune'] ?? null,
                trim($_POST['raison_sociale'] ?? ''),
                trim($_POST['siret'] ?? '')
            );
            if (!empty($locataire->getNomLocataire()) && !empty($locataire->getEmailLocataire()) && !empty($locataire->getIdCommune())) {
                $result = $controller->createLocataire($locataire);
                $message = $result ? "✅ Locataire ajouté avec succès." : "❌ Erreur lors de l'ajout.";
                $message_type = $result ? 'success' : 'error';
            } else {
                $message = "❌ Les champs obligatoires (nom, email, commune) sont requis.";
                $message_type = 'error';
            }
        } elseif ($_POST['action'] === 'update') {
            $id = $_POST['id_locataire'] ?? 0;
            $locataire = new Locataire(
                $id,
                trim($_POST['nom_locataire'] ?? ''),
                trim($_POST['prenom_locataire'] ?? ''),
                $_POST['dna_locataire'] ?? '',
                trim($_POST['email_locataire'] ?? ''),
                trim($_POST['rue_locataire'] ?? ''),
                $_POST['pass_locataire'] ?? '',
                trim($_POST['tel_locataire'] ?? ''),
                trim($_POST['comp_locataire'] ?? ''),
                $_POST['id_commune'] ?? null,
                trim($_POST['raison_sociale'] ?? ''),
                trim($_POST['siret'] ?? '')
            );
            if ($id > 0 && !empty($locataire->getNomLocataire()) && !empty($locataire->getEmailLocataire()) && !empty($locataire->getIdCommune())) {
                $result = $controller->updateLocataire($id, $locataire);
                $message = $result ? "✅ Locataire modifié avec succès." : "❌ Erreur lors de la modification.";
                $message_type = $result ? 'success' : 'error';
                $edit_id = null; // Revenir à la liste après modification
            } else {
                $message = "❌ ID ou champs obligatoires (nom, email, commune) invalides.";
                $message_type = 'error';
            }
        } elseif ($_POST['action'] === 'delete') {
            $id = $_POST['id_locataire'] ?? 0;
            if ($id > 0) {
                $result = $controller->deleteLocataire($id);
                $message = $result ? "✅ Locataire supprimé avec succès." : "❌ Erreur lors de la suppression.";
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
$locataires = $search ? $controller->searchLocataires($search) : $controller->getAllLocataires();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des locataires</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .autocomplete-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #d1d5db;
            border-top: none;
            border-radius: 0 0 0.5rem 0.5rem;
            max-height: 250px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .autocomplete-item {
            padding: 0.75rem;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
        }
        .autocomplete-item:hover {
            background: #f9fafb;
        }
    </style>
</head>
<body class="bg-gray-100 p-6">
    <?php include '../header.php'; ?>

    <main class="max-w-2xl mx-auto mt-10 bg-white shadow-md rounded-lg p-6">
        <h1 class="text-2xl font-bold mb-4">Gestion des locataires</h1>

        <!-- Message -->
        <?php if ($message): ?>
            <p class="text-<?php echo $message_type === 'success' ? 'green-600' : 'red-600'; ?> font-semibold mb-4">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <!-- Formulaire d'ajout -->
        <form action="" method="POST" id="locataireForm" class="mb-6">
            <input type="hidden" name="action" value="create">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 mb-2">Nom :</label>
                    <input type="text" name="nom_locataire" required
                           class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300"
                           placeholder="Ex: Dupont">
                </div>
                <div>
                    <label class="block text-gray-700 mb-2">Prénom :</label>
                    <input type="text" name="prenom_locataire"
                           class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300"
                           placeholder="Ex: Jean">
                </div>
                <div>
                    <label class="block text-gray-700 mb-2">Date de naissance :</label>
                    <input type="date" name="dna_locataire" required
                           class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300">
                </div>
                <div>
                    <label class="block text-gray-700 mb-2">Email :</label>
                    <input type="email" name="email_locataire" required
                           class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300"
                           placeholder="Ex: jean.dupont@example.com">
                </div>
                <div>
                    <label class="block text-gray-700 mb-2">Rue :</label>
                    <input type="text" name="rue_locataire"
                           class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300"
                           placeholder="Ex: 123 rue des Lilas">
                </div>
                <div>
                    <label class="block text-gray-700 mb-2">Téléphone :</label>
                    <input type="text" name="tel_locataire"
                           class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300"
                           placeholder="Ex: 0601234567">
                </div>
                <div>
                    <label class="block text-gray-700 mb-2">Complément d'adresse :</label>
                    <input type="text" name="comp_locataire"
                           class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300"
                           placeholder="Ex: Appartement 12">
                </div>
                <div class="relative">
                    <label class="block text-gray-700 mb-2">Commune :</label>
                    <input type="text" id="commune_search" autocomplete="off" required
                           class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300"
                           placeholder="Ex: Paris">
                    <input type="hidden" name="id_commune" id="id_commune" required>
                    <div id="autocomplete_results" class="autocomplete-results hidden"></div>
                </div>
                <div>
                    <label class="block text-gray-700 mb-2">Mot de passe :</label>
                    <input type="password" name="pass_locataire" required
                           class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300">
                </div>
                <div>
            <label for="confirm_pass" class="block text-gray-700 mb-2">Confirmer le mot de passe :</label>
            <input type="password" id="confirm_pass" required
                   class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300">
        </div>

                <div class="flex items-center">
                    <input type="checkbox" id="isEntreprise" name="isEntreprise" value="1"
                           onclick="toggleEntrepriseFields()"
                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                    <label for="isEntreprise" class="ml-2 text-gray-700">C'est une entreprise</label>
                </div>
            </div>
            <div id="entrepriseFields" class="hidden bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
                <h3 class="text-lg font-semibold mb-3 text-gray-700">Informations entreprise</h3>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Raison sociale :</label>
                    <input type="text" name="raison_sociale"
                           class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300"
                           placeholder="Ex: SARL Dupont">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">SIRET :</label>
                    <input type="text" name="siret"
                           class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-blue-300"
                           placeholder="Ex: 12345678901234">
                </div>
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                ➕ Ajouter un locataire
            </button>
        </form>

        <!-- Formulaire de recherche -->
        <form action="" method="GET" class="mb-6">
            <label class="block text-gray-700 mb-2">Rechercher un locataire :</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                   class="w-full border rounded-lg p-2 mb-4 focus:ring focus:ring-green-300"
                   placeholder="Ex: Dupont">
            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
                🔍 Rechercher
            </button>
        </form>

        <!-- Liste des locataires -->
        <h2 class="text-xl font-semibold mb-2">📋 Liste des locataires</h2>
        <div class="border rounded-lg p-4 bg-gray-50">
            <?php if (empty($locataires)): ?>
                <p>Aucune locataire trouvé.</p>
            <?php else: ?>
                <?php foreach ($locataires as $locataire): ?>
                    <?php if ($edit_id === $locataire['id_locataire']): ?>
                        <!-- Mode édition -->
                        <form action="" method="POST" class="flex flex-col border-b py-2 gap-2">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id_locataire" value="<?php echo $locataire['id_locataire']; ?>">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700">Nom :</label>
                                    <input type="text" name="nom_locataire"
                                           value="<?php echo htmlspecialchars($locataire['nom_locataire']); ?>"
                                           class="w-full border rounded-lg p-2 focus:ring focus:ring-yellow-300" required>
                                </div>
                                <div>
                                    <label class="block text-gray-700">Prénom :</label>
                                    <input type="text" name="prenom_locataire"
                                           value="<?php echo htmlspecialchars($locataire['prenom_locataire']); ?>"
                                           class="w-full border rounded-lg p-2 focus:ring focus:ring-yellow-300">
                                </div>
                                <div>
                                    <label class="block text-gray-700">Date de naissance :</label>
                                    <input type="date" name="dna_locataire"
                                           value="<?php echo htmlspecialchars($locataire['dna_locataire']); ?>"
                                           class="w-full border rounded-lg p-2 focus:ring focus:ring-yellow-300" required>
                                </div>
                                <div>
                                    <label class="block text-gray-700">Email :</label>
                                    <input type="email" name="email_locataire"
                                           value="<?php echo htmlspecialchars($locataire['email_locataire']); ?>"
                                           class="w-full border rounded-lg p-2 focus:ring focus:ring-yellow-300" required>
                                </div>
                                <div>
                                    <label class="block text-gray-700">Rue :</label>
                                    <input type="text" name="rue_locataire"
                                           value="<?php echo htmlspecialchars($locataire['rue_locataire']); ?>"
                                           class="w-full border rounded-lg p-2 focus:ring focus:ring-yellow-300">
                                </div>
                                <div>
                                    <label class="block text-gray-700">Téléphone :</label>
                                    <input type="text" name="tel_locataire"
                                           value="<?php echo htmlspecialchars($locataire['tel_locataire']); ?>"
                                           class="w-full border rounded-lg p-2 focus:ring focus:ring-yellow-300">
                                </div>
                                <div>
                                    <label class="block text-gray-700">Complément d'adresse :</label>
                                    <input type="text" name="comp_locataire"
                                           value="<?php echo htmlspecialchars($locataire['comp_locataire']); ?>"
                                           class="w-full border rounded-lg p-2 focus:ring focus:ring-yellow-300">
                                </div>
                                <div class="relative">
                                    <label class="block text-gray-700">Commune :</label>
                                    <input type="text" id="commune_search_<?php echo $locataire['id_locataire']; ?>" autocomplete="off"
                                           value="<?php echo htmlspecialchars($locataire['nom_commune'] . ($locataire['cp_commune'] ? ' (' . $locataire['cp_commune'] . ')' : '')); ?>"
                                           class="w-full border rounded-lg p-2 focus:ring focus:ring-yellow-300" required>
                                    <input type="hidden" name="id_commune" id="id_commune_<?php echo $locataire['id_locataire']; ?>"
                                           value="<?php echo $locataire['id_commune']; ?>" required>
                                    <div id="autocomplete_results_<?php echo $locataire['id_locataire']; ?>" class="autocomplete-results hidden"></div>
                                </div>
                                <div>
                                    <label class="block text-gray-700">Mot de passe :</label>
                                    <input type="password" name="pass_locataire"
                                           class="w-full border rounded-lg p-2 focus:ring focus:ring-yellow-300">
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" id="isEntreprise_<?php echo $locataire['id_locataire']; ?>" name="isEntreprise" value="1"
                                           onclick="toggleEntrepriseFields('<?php echo $locataire['id_locataire']; ?>')"
                                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                           <?php echo $locataire['raison_sociale'] ? 'checked' : ''; ?>>
                                    <label for="isEntreprise_<?php echo $locataire['id_locataire']; ?>" class="ml-2 text-gray-700">C'est une entreprise</label>
                                </div>
                                <div id="entrepriseFields_<?php echo $locataire['id_locataire']; ?>" class="bg-gray-50 border border-gray-200 rounded-lg p-4 <?php echo $locataire['raison_sociale'] ? '' : 'hidden'; ?>">
                                    <h3 class="text-lg font-semibold mb-3 text-gray-700">Informations entreprise</h3>
                                    <div>
                                        <label class="block text-gray-700">Raison sociale :</label>
                                        <input type="text" name="raison_sociale"
                                               value="<?php echo htmlspecialchars($locataire['raison_sociale']); ?>"
                                               class="w-full border rounded-lg p-2 focus:ring focus:ring-yellow-300">
                                    </div>
                                    <div class="mt-4">
                                        <label class="block text-gray-700">SIRET :</label>
                                        <input type="text" name="siret"
                                               value="<?php echo htmlspecialchars($locataire['siret']); ?>"
                                               class="w-full border rounded-lg p-2 focus:ring focus:ring-yellow-300">
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-2 mt-4">
                                <button type="submit" class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">💾</button>
                                <a href="?search=<?php echo urlencode($search); ?>" 
                                   class="bg-gray-300 text-gray-700 px-3 py-1 rounded hover:bg-gray-400">Annuler</a>
                            </div>
                        </form>
                    <?php else: ?>
                        <!-- Mode affichage -->
                        <div class="flex justify-between items-center border-b py-2">
                            <span><?php echo htmlspecialchars($locataire['nom_locataire'] . ' ' . $locataire['prenom_locataire']); ?></span>
                            <div>
                                <a href="?edit_id=<?php echo $locataire['id_locataire']; ?>&search=<?php echo urlencode($search); ?>"
                                   class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">✏️ Modifier</a>
                                <form action="" method="POST" class="inline">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id_locataire" value="<?php echo $locataire['id_locataire']; ?>">
                                    <button type="submit" onclick="return confirm('Supprimer ce locataire ?')"
                                            class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">🗑️ Supprimer</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let searchTimeout;
        function setupAutocomplete(inputId, hiddenId, resultsId) {
            const communeSearch = document.getElementById(inputId);
            const communeIdInput = document.getElementById(hiddenId);
            const resultsContainer = document.getElementById(resultsId);

            communeSearch.addEventListener('input', function() {
                const query = this.value.trim();
                communeIdInput.value = '';
                clearTimeout(searchTimeout);

                if (query.length < 2) {
                    resultsContainer.classList.add('hidden');
                    return;
                }

                searchTimeout = setTimeout(() => searchCommunes(query, resultsContainer, communeSearch, communeIdInput), 300);
            });

            document.addEventListener('click', (e) => {
                if (!communeSearch.contains(e.target) && !resultsContainer.contains(e.target)) {
                    resultsContainer.classList.add('hidden');
                }
            });
        }

        function searchCommunes(query, resultsContainer, communeSearch, communeIdInput) {
            resultsContainer.innerHTML = '<div class="p-3 text-center text-gray-600">Recherche...</div>';
            resultsContainer.classList.remove('hidden');

            fetch(`../../ajax/ajax_locataire.php?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.communes.length > 0) {
                        displayResults(data.communes, resultsContainer, communeSearch, communeIdInput);
                    } else {
                        resultsContainer.innerHTML = '<div class="p-3 text-center text-gray-600">Aucune commune trouvée</div>';
                    }
                })
                .catch(() => {
                    resultsContainer.innerHTML = '<div class="p-3 text-center text-red-600">Erreur</div>';
                });
        }

        function displayResults(communes, resultsContainer, communeSearch, communeIdInput) {
            resultsContainer.innerHTML = '';
            communes.forEach(commune => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';
                item.innerHTML = `
                    <div class="font-semibold text-gray-800">${commune.nom_commune}</div>
                    <div class="text-sm text-gray-600">
                        ${commune.cp_commune ? 'CP: ' + commune.cp_commune : ''}
                        ${commune.commune_departement ? ' - Dép: ' + commune.commune_departement : ''}
                    </div>
                `;
                item.onclick = () => selectCommune(commune, communeSearch, communeIdInput, resultsContainer);
                resultsContainer.appendChild(item);
            });
            resultsContainer.classList.remove('hidden');
        }

        function selectCommune(commune, communeSearch, communeIdInput, resultsContainer) {
            communeSearch.value = commune.nom_commune + (commune.cp_commune ? ' (' + commune.cp_commune + ')' : '');
            communeIdInput.value = commune.id_commune;
            resultsContainer.classList.add('hidden');
        }

        function toggleEntrepriseFields(id = '') {
            const fields = document.getElementById(`entrepriseFields${id ? '_' + id : ''}`);
            const checkbox = document.getElementById(`isEntreprise${id ? '_' + id : ''}`);
            fields.classList.toggle('hidden', !checkbox.checked);
        }

        // Initialiser l'autocomplétion pour le formulaire d'ajout
        setupAutocomplete('commune_search', 'id_commune', 'autocomplete_results');

        // Initialiser l'autocomplétion pour chaque locataire en mode édition
        <?php foreach ($locataires as $locataire): ?>
            <?php if ($edit_id === $locataire['id_locataire']): ?>
                setupAutocomplete('commune_search_<?php echo $locataire['id_locataire']; ?>', 
                                'id_commune_<?php echo $locataire['id_locataire']; ?>', 
                                'autocomplete_results_<?php echo $locataire['id_locataire']; ?>');
            <?php endif; ?>
        <?php endforeach; ?>

        // Valider le formulaire d'ajout
        document.getElementById('locataireForm').addEventListener('submit', (e) => {
            const communeIdInput = document.getElementById('id_commune');
            if (!communeIdInput.value) {
                e.preventDefault();
                alert('Veuillez sélectionner une commune dans la liste');
                document.getElementById('commune_search').focus();
            }
        });
    </script>
</body>
</html>