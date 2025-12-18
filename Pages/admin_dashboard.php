<?php
session_start();

// Sécurité admin
if (!isset($_SESSION['utilisateur_id']) || $_SESSION['role'] !== 'admin') {
    if (isset($_SESSION['utilisateur_id'])) {
        $_SESSION['error_message'] = "Vous n'avez pas les droits nécessaires pour accéder à cette page.";
    }
    header('Location: ../../Formulaires/connexion.php');
    exit;
}

// Chemins corrects depuis Pages/Admin/
require_once '../include/db.php';
require_once '..//Pages/Bien/bien_class.php';

$controller = new BiensController($pdo);
$pending = $controller->getPendingBiens();

$utilisateur_nom = $_SESSION['prenom'] . ' ' . ($_SESSION['nom'] ?? '') ?: 'Administrateur';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord administrateur - Holidaze</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        a {
            text-decoration: none !important;
        }
        
        a:hover,
        a:focus,
        a:active {
            text-decoration: none !important;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

<?php include '../Pages/header.php'; ?>

<main class="max-w-4xl mx-auto mt-10 bg-white shadow-md rounded-lg p-6">
        <h1 class="text-3xl font-bold mb-6">Tableau de bord administrateur</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Gestion des biens -->
            <a href="/Pages/Bien/bien_form.php" class="block p-6 bg-blue-100 rounded-lg shadow hover:bg-blue-200 transition">
                <h2 class="text-xl font-semibold text-blue-800">Gestion des biens</h2>
                <p class="mt-2 text-gray-600">Ajoutez, modifiez ou supprimez des biens immobiliers.</p>
            </a>

            <!-- Gestion des locataires -->
            <a href="/Pages/Locataire/locataire_form.php" class="block p-6 bg-yellow-100 rounded-lg shadow hover:bg-yellow-200 transition">
                <h2 class="text-xl font-semibold text-yellow-800">Gestion des locataires</h2>
                <p class="mt-2 text-gray-600">Gérez les informations des locataires.</p>
            </a>

            <!-- Gestion des prestations -->
            <a href="/Pages/Prestations/prestation_form.php" class="block p-6 bg-red-100 rounded-lg shadow hover:bg-red-200 transition">
                <h2 class="text-xl font-semibold text-red-800">Gestion des prestations</h2>
                <p class="mt-2 text-gray-600">Ajoutez ou modifiez les prestations proposées.</p>
            </a>

            <!-- Gestion des saisons -->
            <a href="/Pages/Saison/saison_form.php" class="block p-6 bg-purple-100 rounded-lg shadow hover:bg-purple-200 transition">
                <h2 class="text-xl font-semibold text-purple-800">Gestion des saisons</h2>
                <p class="mt-2 text-gray-600">Définissez et gérez les périodes de saison.</p>
            </a>

            <!-- Gestion des réservations -->
            <a href="/Pages/Réservations/reservation_form.php" class="block p-6 bg-green-100 rounded-lg shadow hover:bg-green-200 transition">
                <h2 class="text-xl font-semibold text-green-800">Gestion des réservations</h2>
                <p class="mt-2 text-gray-600">Visualisez et gérez les réservations des biens.</p>
            </a>

            <!-- Gestion des utilisateurs -->   
            <a href="/Pages/Utilisateur/utilisateur_form.php" class="block p-6 bg-indigo-100 rounded-lg shadow hover:bg-indigo-200 transition">
                <h2 class="text-xl font-semibold text-indigo-800">Gestion des utilisateurs</h2>
                <p class="mt-2 text-gray-600">Gérez les comptes utilisateurs et leurs rôles.</p>
            </a>
        </div>

    <!-- Validation des biens -->
    <div class="bg-white rounded-2xl shadow-xl p-10">
        <h2 class="text-3xl font-bold text-center mb-10 text-gray-800">Validation des biens en attente</h2>

        <?php if (empty($pending)): ?>
            <div class="text-center py-16">
                <i class="fas fa-check-circle text-8xl text-green-500 mb-6"></i>
                <p class="text-2xl text-gray-600">Aucun bien en attente de validation</p>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($pending as $b): ?>
                    <div class="border border-gray-200 rounded-xl p-6 flex justify-between items-center hover:bg-gray-50 transition">
                        <div>
                            <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($b['nom_bien']) ?></h3>
                            <p class="text-gray-600 mt-1">
                                <i class="fas fa-map-marker-alt mr-2"></i><?= htmlspecialchars($b['nom_commune']) ?>
                                • <?= htmlspecialchars($b['des_typebien']) ?>
                                • <?= $b['superficie_bien'] ?> m²
                                • <?= $b['nb_couchage'] ?> couchages
                            </p>
                        </div>
                        <div class="flex gap-4">
                            <a href="/Pages/Bien/bien_detail.php?id=<?= $b['id_bien'] ?>"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-bold transition">
                                 Voir le détail
                            </a>
                            <button onclick="validateBien(<?= $b['id_bien'] ?>)"
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-bold transition">
                                Valider
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<!-- Section Messages des utilisateurs -->
<div class="bg-white rounded-2xl shadow-xl p-6 mt-10">
    <!-- Card Header -->
    <div class="flex justify-between items-center pb-4 border-b border-gray-200">
        <h2 class="text-2xl font-bold text-gray-800 flex items-center">
            <i class="fas fa-envelope mr-3"></i> Messages des utilisateurs
        </h2>
        <span id="unread-count-badge" class="px-3 py-1 text-sm font-semibold rounded-full bg-red-500 text-white">
            <?php
            // Assuming $pdo is available from line 14
            $count = $pdo->query("SELECT COUNT(*) FROM messages_contact WHERE lu = 0")->fetchColumn();
            echo $count . ' non lu' . ($count > 1 ? 's' : '');
            ?>
        </span>
    </div>
    
    <!-- Card Body (Table) -->
    <div class="mt-4">
        <?php
        // Database query remains the same
        $stmt = $pdo->query("
            SELECT m.id_message, m.sujet, m.message, m.date_envoi, m.lu, m.reponse_admin, m.date_reponse, m.supprime,
                   u.prenom, u.nom, u.email 
            FROM messages_contact m 
            JOIN utilisateurs u ON m.id_utilisateur = u.id_utilisateur 
            WHERE m.supprime = 0
            ORDER BY m.lu ASC, m.date_envoi DESC
        ");
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($messages)): ?>
            <p class="text-gray-500 text-center py-4">Aucun message pour le moment.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expéditeur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sujet</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($messages as $msg): ?>
                        <tr id="message-row-<?= $msg['id_message'] ?>" class="<?= !$msg['lu'] ? 'bg-yellow-50 font-semibold hover:bg-yellow-100' : 'hover:bg-gray-50' ?> transition duration-150 ease-in-out">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d/m/Y à H:i', strtotime($msg['date_envoi'])) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($msg['prenom'] . ' ' . $msg['nom']) ?><br>
                                <small class="text-gray-500"><?= htmlspecialchars($msg['email']) ?></small>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                <a href="#" onclick="openModal(<?= $msg['id_message'] ?>)" class="text-blue-600 hover:text-blue-800">
                                    <?= htmlspecialchars($msg['sujet']) ?>
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php if (!empty($msg['reponse_admin'])): ?>
                                    <span id="status-badge-<?= $msg['id_message'] ?>" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        Répondu
                                    </span>
                                <?php elseif (!$msg['lu']): ?>
                                    <span id="status-badge-<?= $msg['id_message'] ?>" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Non lu
                                    </span>
                                <?php else: ?>
                                    <span id="status-badge-<?= $msg['id_message'] ?>" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Lu
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="openModal(<?= $msg['id_message'] ?>)"
                                        class="text-indigo-600 hover:text-indigo-900 focus:outline-none">
                                    <i class="fas fa-eye mr-1"></i> Voir
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modales pour chaque message (Utilisation de la structure de modale Tailwind/Vanilla JS) -->
<?php foreach ($messages as $msg): ?>
<div id="messageModal<?= $msg['id_message'] ?>" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50" aria-labelledby="modal-title-<?= $msg['id_message'] ?>" role="dialog" aria-modal="true">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <!-- Modal Header -->
        <div id="modal-header-<?= $msg['id_message'] ?>" class="flex justify-between items-center p-4 border-b rounded-t <?= !$msg['lu'] ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' ?>">
            <h3 class="text-xl font-semibold" id="modal-title-<?= $msg['id_message'] ?>">
                <i class="fas fa-envelope-open mr-2"></i> <?= htmlspecialchars($msg['sujet']) ?>
            </h3>
            <button type="button" onclick="closeModal(<?= $msg['id_message'] ?>)" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <!-- Modal Body -->
        <div class="p-6">
            <div class="flex justify-between mb-4 text-sm">
                <div>
                    <strong>De :</strong> <?= htmlspecialchars($msg['prenom'] . ' ' . $msg['nom']) ?> 
                    <span class="text-gray-500">(<?= htmlspecialchars($msg['email']) ?>)</span>
                </div>
                <div class="text-right">
                    <strong>Date :</strong> <?= date('d/m/Y à H:i', strtotime($msg['date_envoi'])) ?>
                </div>
            </div>
            <hr class="my-4">
            <!-- Zone du message de l'utilisateur avec retour à la ligne forcé -->
            <h4 class="text-lg font-semibold mb-2 text-gray-700">Message de l'utilisateur :</h4>
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6" style="max-height: 300px; overflow-y: auto; word-break: break-word; white-space: pre-wrap;">
                <?= nl2br(htmlspecialchars($msg['message'])) ?>
            </div>

            <!-- Section Réponse de l'administrateur -->
            <h4 class="text-lg font-semibold mb-2 text-gray-700">Réponse de l'administrateur :</h4>
            <?php if (!empty($msg['reponse_admin'])): ?>
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200 mb-6">
                    <p class="text-sm text-gray-600 mb-2">Répondu le <?= date('d/m/Y à H:i', strtotime($msg['date_reponse'])) ?></p>
                    <div class="text-gray-800 whitespace-pre-wrap"><?= nl2br(htmlspecialchars($msg['reponse_admin'])) ?></div>
                </div>
            <?php endif; ?>

            <!-- Formulaire de réponse -->
            <form id="reply-form-<?= $msg['id_message'] ?>" onsubmit="sendReply(event, <?= $msg['id_message'] ?>)" class="space-y-4 <?= !empty($msg['reponse_admin']) ? 'hidden' : '' ?>">
                <textarea id="reply-text-<?= $msg['id_message'] ?>" rows="4" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Écrivez votre réponse ici..."><?= htmlspecialchars($msg['reponse_admin'] ?? '') ?></textarea>
                <button type="submit" class="w-full text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    <i class="fas fa-paper-plane mr-1"></i> Envoyer la réponse
                </button>
            </form>
        </div>
        <!-- Modal Footer -->
        <div class="flex items-center p-4 border-t rounded-b justify-between space-x-3">
            <button type="button" onclick="deleteMessage(<?= $msg['id_message'] ?>)" class="text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                <i class="fas fa-trash-alt mr-1"></i> Supprimer
            </button>
            <div class="flex space-x-3">
                <?php if (!$msg['lu'] && empty($msg['reponse_admin'])): ?>
                <button id="mark-read-btn-<?= $msg['id_message'] ?>" type="button" class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center" onclick="marquerCommeLu(<?= $msg['id_message'] ?>)">
                    <i class="fas fa-check-circle mr-1"></i> Marquer comme lu
                </button>
                <?php endif; ?>
                <button type="button" onclick="closeModal(<?= $msg['id_message'] ?>)" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:ring-gray-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Fermer</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>





</main>

<script>
// Fonctions pour gérer l'ouverture et la fermeture des modales (Tailwind CSS)
function openModal(id_message) {
    const modal = document.getElementById(`messageModal${id_message}`);
    if (modal) {
        modal.classList.remove('hidden');
        // Ajoute un écouteur pour fermer la modale en cliquant en dehors
        modal.onclick = function(event) {
            if (event.target === modal) {
                closeModal(id_message);
            }
        };
    }
}

function closeModal(id_message) {
    const modal = document.getElementById(`messageModal${id_message}`);
    if (modal) {
        modal.classList.add('hidden');
        modal.onclick = null; // Supprime l'écouteur
    }
}

function marquerCommeLu(id_message) {
    fetch('/ajax/ajax_contact.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_message=' + encodeURIComponent(id_message) + '&action=mark_read'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 1. Mise à jour de la ligne du tableau
            const row = document.getElementById(`message-row-${id_message}`);
            if (row) {
                row.classList.remove('bg-yellow-50', 'font-semibold', 'hover:bg-yellow-100');
                row.classList.add('hover:bg-gray-50');
            }
            
            // 2. Mise à jour du badge de statut dans le tableau
            const statusBadge = document.getElementById(`status-badge-${id_message}`);
            if (statusBadge) {
                statusBadge.classList.remove('bg-red-100', 'text-red-800');
                statusBadge.classList.add('bg-green-100', 'text-green-800');
                statusBadge.textContent = 'Lu';
            }
            
            // 3. Mise à jour de l'en-tête de la modale
            const modalHeader = document.getElementById(`modal-header-${id_message}`);
            if (modalHeader) {
                modalHeader.classList.remove('bg-yellow-100', 'text-yellow-800');
                modalHeader.classList.add('bg-gray-100', 'text-gray-800');
            }
            
            // 4. Masquer le bouton "Marquer comme lu" dans la modale
            const button = document.getElementById(`mark-read-btn-${id_message}`);
            if (button) {
                button.style.display = 'none';
            }
            
            // 5. Mise à jour du compteur de messages non lus
            const compteur = document.getElementById('unread-count-badge');
            if (compteur) {
                let text = compteur.textContent;
                let countMatch = text.match(/(\d+)/);
                let count = countMatch ? parseInt(countMatch[1]) : 0;

                if (count > 0) {
                    count--;
                    compteur.textContent = count + ' non lu' + (count > 1 ? 's' : '');
                    if (count === 0) {
                        compteur.classList.remove('bg-red-500');
                        compteur.classList.add('bg-gray-400'); // Changement de couleur pour indiquer zéro
                    }
                }
            }
            
            // Message de confirmation discret
            alert('Message marqué comme lu !');
        } else {
            alert('Erreur : ' + (data.message || 'Impossible de marquer comme lu'));
        }
    })
    .catch(err => {
        console.error('Erreur AJAX :', err);
        alert('Erreur de connexion. Réessayez plus tard.');
    });
}

function deleteMessage(id_message) {
    if (!confirm("Êtes-vous sûr de vouloir supprimer ce message ? Cette action est irréversible (il sera masqué).")) {
        return;
    }

    fetch('/ajax/ajax_contact.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_message=' + encodeURIComponent(id_message) + '&action=delete'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Recharger la page pour mettre à jour la liste (le plus simple)
            location.reload();
        } else {
            alert('Erreur de suppression : ' + (data.message || 'Une erreur inconnue est survenue.'));
        }
    })
    .catch(err => {
        console.error('Erreur AJAX :', err);
        alert('Erreur de connexion. Réessayez plus tard.');
    });
}

function sendReply(event, id_message) {
    event.preventDefault(); // Empêche l'envoi du formulaire par défaut

    const replyTextarea = document.getElementById(`reply-text-${id_message}`);
    const reply = replyTextarea.value.trim();

    if (reply.length < 5) {
        alert("Veuillez écrire une réponse d'au moins 5 caractères.");
        return;
    }

    if (!confirm("Confirmez-vous l'envoi de cette réponse à l'utilisateur ?")) {
        return;
    }

    fetch('/ajax/ajax_contact.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_message=' + encodeURIComponent(id_message) + '&action=reply&reponse_admin=' + encodeURIComponent(reply)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Recharger la page pour afficher la réponse et mettre à jour le statut
            location.reload();
        } else {
            alert('Erreur d\'envoi de la réponse : ' + (data.message || 'Une erreur inconnue est survenue.'));
        }
    })
    .catch(err => {
        console.error('Erreur AJAX :', err);
        alert('Erreur de connexion. Réessayez plus tard.');
    });
}

function deleteMessage(id_message) {
    if (!confirm("Êtes-vous sûr de vouloir supprimer ce message ? Cette action est irréversible (il sera masqué).")) {
        return;
    }

    fetch('/ajax/ajax_contact.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_message=' + encodeURIComponent(id_message) + '&action=delete'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Recharger la page pour mettre à jour la liste (le plus simple)
            location.reload();
        } else {
            alert('Erreur de suppression : ' + (data.message || 'Une erreur inconnue est survenue.'));
        }
    })
    .catch(err => {
        console.error('Erreur AJAX :', err);
        alert('Erreur de connexion. Réessayez plus tard.');
    });
}

function sendReply(event, id_message) {
    event.preventDefault(); // Empêche l'envoi du formulaire par défaut

    const replyTextarea = document.getElementById(`reply-text-${id_message}`);
    const reply = replyTextarea.value.trim();

    if (reply.length < 5) {
        alert("Veuillez écrire une réponse d'au moins 5 caractères.");
        return;
    }

    if (!confirm("Confirmez-vous l'envoi de cette réponse à l'utilisateur ?")) {
        return;
    }

    fetch('/ajax/ajax_contact.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_message=' + encodeURIComponent(id_message) + '&action=reply&reponse_admin=' + encodeURIComponent(reply)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Recharger la page pour afficher la réponse et mettre à jour le statut
            location.reload();
        } else {
            alert('Erreur d\'envoi de la réponse : ' + (data.message || 'Une erreur inconnue est survenue.'));
        }
    })
    .catch(err => {
        console.error('Erreur AJAX :', err);
        alert('Erreur de connexion. Réessayez plus tard.');
    });
}
</script>

<?php include '../Pages/footer.php'; ?>

<script>
async function validateBien(id) {
    if (!confirm("Valider ce bien ? Il sera immédiatement visible par tous les utilisateurs.")) return;

    const form = new FormData();
    form.append('id_bien', id);

    try {
        // bien_validation.php est dans le même dossier → chemin relatif simple
        const res = await fetch('bien_validation.php', {
            method: 'POST',
            body: form
        });

        if (res.ok) {
            alert("Bien validé avec succès !");
            location.reload();
        } else {
            alert("Erreur lors de la validation");
        }
    } catch (e) {
        alert("Erreur réseau");
    }
}
</script>

</body>
</html>