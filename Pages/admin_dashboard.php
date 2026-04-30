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
	<meta name="robots" content="noindex, nofollow">
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

			<!-- Gestion des ménages -->  
			<a href="/Pages/Menages/menages_form.php" class="block p-6 bg-indigo-100 rounded-lg shadow hover:bg-indigo-200 transition">
			    <h2 class="text-xl font-semibold text-indigo-800">Gestion des ménages</h2>
			    <p class="mt-2 text-gray-600">Planifiez les ménages par réservation et assignez-les aux intervenants.
			    </p>
			</a>

			<!-- Gestion des intervenants -->  
			<a href="/Pages/Intervenants/intervenants_form.php" class="block p-6 bg-indigo-100 rounded-lg shadow hover:bg-indigo-200 transition">
			    <h2 class="text-xl font-semibold text-indigo-800">Gestion des intervenants</h2>
			    <p class="mt-2 text-gray-600">Gérer les intervenants pour un ménage dans une location.
			    </p>
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
	            $count = $pdo->query("SELECT COUNT(*) FROM conversations WHERE lu = 0 AND supprime = 0")->fetchColumn();
            echo $count . ' non lu' . ($count > 1 ? 's' : '');
            ?>
        </span>
    </div>
    
    <!-- Card Body (Table) -->
    <div class="mt-4">
        <?php
	        // Requête refondue pour la conversation multi-messages
	        // Utilisation d'une sous-requête pour trouver l'ID du dernier message, compatible avec ONLY_FULL_GROUP_BY
	        $stmt = $pdo->query("
	            SELECT 
	                c.id_conversation, c.sujet, c.supprime, c.lu, c.date_creation,
	                u.prenom, u.nom, u.email,
	                m_last.date_envoi AS date_dernier_message,
	                m_last.role_expediteur,
	                m_first.contenu AS message_initial
	            FROM conversations c 
	            JOIN utilisateurs u ON c.id_utilisateur = u.id_utilisateur 
	            -- Jointure pour obtenir le dernier message de la conversation (pour le tri et le statut)
	            JOIN messages m_last ON m_last.id_message = (
	                SELECT id_message 
	                FROM messages 
	                WHERE id_conversation = c.id_conversation 
	                ORDER BY date_envoi DESC 
	                LIMIT 1
	            )
	            -- Jointure pour obtenir le message initial (pour l'affichage dans la modale si besoin)
	            LEFT JOIN messages m_first ON m_first.id_message = (
	                SELECT id_message 
	                FROM messages 
	                WHERE id_conversation = c.id_conversation AND role_expediteur = 'utilisateur'
	                ORDER BY date_envoi ASC 
	                LIMIT 1
	            )
	            WHERE c.supprime = 0
	            ORDER BY c.lu ASC, date_dernier_message DESC
	        ");
	        $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);
	        
	        // Pour minimiser les changements dans le code HTML, on renomme la variable
	        $messages = $conversations;
    	        if (empty($conversations)): ?>           <p class="text-gray-500 text-center py-4">Aucun message pour le moment.</p>
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
	                        <?php foreach ($conversations as $msg): ?>	                        <tr id="message-row-<?= $msg['id_conversation'] ?>" class="<?= !$msg['lu'] ? 'bg-yellow-50 font-semibold hover:bg-yellow-100' : 'hover:bg-gray-50' ?> transition duration-150 ease-in-out">
	                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= date('d/m/Y à H:i', strtotime($msg['date_dernier_message'])) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($msg['prenom'] . ' ' . $msg['nom']) ?><br>
                                <small class="text-gray-500"><?= htmlspecialchars($msg['email']) ?></small>
                            </td>
	                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
	                                <a href="#" onclick="openModal(<?= $msg['id_conversation'] ?>)" class="text-blue-600 hover:text-blue-800">
	                                    <?= htmlspecialchars($msg['sujet']) ?>
	                                </a>
	                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
	                                <?php 
	                                    $status_text = 'Statut inconnu';
	                                    $status_class = 'bg-gray-100 text-gray-800';
	                                    
	                                    if ($msg['lu'] == 0) {
	                                        $status_text = 'Non lu';
	                                        $status_class = 'bg-red-100 text-red-800';
	                                    } elseif ($msg['role_expediteur'] == 'utilisateur') {
	                                        $status_text = 'En attente de réponse';
	                                        $status_class = 'bg-yellow-100 text-yellow-800';
	                                    } elseif ($msg['role_expediteur'] == 'admin') {
	                                        $status_text = 'Répondu (attente utilisateur)';
	                                        $status_class = 'bg-blue-100 text-blue-800';
	                                    }
	                                ?>
	                                    <span id="status-badge-<?= $msg['id_conversation'] ?>" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $status_class ?>">
	                                        <?= $status_text ?>
	                                    </span>
                            </td>
	                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
	                                <button onclick="openModal(<?= $msg['id_conversation'] ?>)"
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

	<!-- Modales pour chaque conversation -->
	<?php foreach ($conversations as $conv): ?>
	<?php
	    // --- SIMULATION DE LA RÉCUPÉRATION DE TOUS LES MESSAGES DE LA CONVERSATION ---
	    // Dans un environnement réel, vous feriez une requête AJAX pour récupérer les messages
	    // ou une requête PHP ici si vous voulez charger tout d'un coup (moins performant).
	    // Pour l'exemple, nous allons simuler une requête PHP ici pour l'affichage initial.
	    
	    // Requête pour récupérer tous les messages de la conversation
	    $stmt_messages = $pdo->prepare("
	        SELECT id_message, role_expediteur, contenu, date_envoi 
	        FROM messages 
	        WHERE id_conversation = ? 
	        ORDER BY date_envoi ASC
	    ");
	    $stmt_messages->execute([$conv['id_conversation']]);
	    $messages_conv = $stmt_messages->fetchAll(PDO::FETCH_ASSOC);
	    
	    // Récupération du statut de la conversation pour l'affichage du formulaire
	    $statut_ferme = $pdo->prepare("SELECT statut FROM conversations WHERE id_conversation = ?");
	    $statut_ferme->execute([$conv['id_conversation']]);
	    $conv_statut = $statut_ferme->fetchColumn();
	    $is_closed = ($conv_statut === 'fermé');
	    
	    // Pour la compatibilité avec les fonctions JS existantes, on utilise id_conversation
	    $id_conv = $conv['id_conversation'];
	?>
	<div id="messageModal<?= $id_conv ?>" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50" aria-labelledby="modal-title-<?= $id_conv ?>" role="dialog" aria-modal="true">
	    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
	        <!-- Modal Header -->
	        <div id="modal-header-<?= $id_conv ?>" class="flex justify-between items-center p-4 border-b rounded-t <?= !$conv['lu'] ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' ?>">
	            <h3 class="text-xl font-semibold" id="modal-title-<?= $id_conv ?>">
	                <i class="fas fa-envelope-open mr-2"></i> <?= htmlspecialchars($conv['sujet']) ?>
	            </h3>
	            <button type="button" onclick="closeModal(<?= $id_conv ?>)" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center">
	                <i class="fas fa-times"></i>
	            </button>
	        </div>
	        <!-- Modal Body -->
	        <div class="p-6">
	            <div class="flex justify-between mb-4 text-sm">
	                <div>
	                    <strong>De :</strong> <?= htmlspecialchars($conv['prenom'] . ' ' . $conv['nom']) ?> 
	                    <span class="text-gray-500">(<?= htmlspecialchars($conv['email']) ?>)</span>
	                </div>
	                <div class="text-right">
	                    <strong>Date de création :</strong> <?= date('d/m/Y à H:i', strtotime($conv['date_creation'])) ?>
	                </div>
	            </div>
	            <hr class="my-4">
	            
	            <!-- Conversation style fil de discussion (affichage de tous les messages) -->
	            <div class="space-y-4 max-h-96 overflow-y-auto p-2">
	                
	                <?php foreach ($messages_conv as $message): ?>
	                    <?php 
	                        $is_admin = ($message['role_expediteur'] === 'admin');
	                        $bg_class = $is_admin ? 'bg-blue-50 border-blue-500' : 'bg-gray-50 border-indigo-500';
	                        $icon_class = $is_admin ? 'fas fa-user-shield text-blue-600' : 'fas fa-user-circle text-indigo-600';
	                        $sender_name = $is_admin ? 'Administrateur' : htmlspecialchars($conv['prenom'] . ' ' . $conv['nom']);
	                    ?>
	                    <div class="p-4 rounded-lg border-l-4 <?= $bg_class ?>">
	                        <div class="flex items-center mb-2">
	                            <i class="<?= $icon_class ?> mr-2"></i>
	                            <span class="font-semibold text-gray-700"><?= $sender_name ?></span>
	                            <span class="text-xs text-gray-500 ml-auto">
	                                <?= date('d/m/Y à H:i', strtotime($message['date_envoi'])) ?>
	                            </span>
	                        </div>
	                        <div class="text-gray-800 whitespace-pre-wrap break-words">
	                            <?= nl2br(htmlspecialchars($message['contenu'])) ?>
	                        </div>
	                    </div>
	                <?php endforeach; ?>
	
	            </div>
	            <hr class="my-4">
	            
	            <!-- Formulaire de réponse admin (visible si la conversation n'est pas fermée) -->
	            <?php if (!$is_closed): ?>
	                <div class="bg-white p-4 rounded-lg border-2 border-dashed border-gray-300">
	                    <h4 class="text-lg font-semibold mb-3 text-gray-700 flex items-center">
	                        <i class="fas fa-reply mr-2 text-blue-600"></i>
	                        Répondre à l'utilisateur
	                    </h4>
	                    <form id="reply-form-<?= $id_conv ?>" onsubmit="sendReply(event, <?= $id_conv ?>)" class="space-y-4">
	                        <textarea id="reply-text-<?= $id_conv ?>" rows="4" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Écrivez votre réponse ici..."></textarea>
	                        <button type="submit" class="w-full text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
	                            <i class="fas fa-paper-plane mr-1"></i> Envoyer la réponse
	                        </button>
	                    </form>
	                </div>
	            <?php else: ?>
	                <div class="bg-red-50 p-4 rounded-lg border border-red-200 text-center">
	                    <i class="fas fa-lock text-red-600 mr-2"></i>
	                    <span class="text-gray-700">Cette conversation a été fermée par l'administrateur.</span>
	                </div>
	            <?php endif; ?>
	
	        </div>
	        <!-- Modal Footer -->
	        <div class="flex items-center p-4 border-t rounded-b justify-center gap-3 flex-wrap">
	            <button type="button" onclick="deleteMessage(<?= $id_conv ?>)" class="text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
	                <i class="fas fa-trash-alt mr-1"></i> Supprimer la conversation
	            </button>
	            <?php if (!$is_closed): ?>
	            <button type="button" onclick="closeConversation(<?= $id_conv ?>)" class="text-white bg-gray-600 hover:bg-gray-700 focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
	                <i class="fas fa-lock mr-1"></i> Fermer la conversation
	            </button>
	            <?php else: ?>
	            <button type="button" onclick="openConversation(<?= $id_conv ?>)" class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
	                <i class="fas fa-lock-open mr-1"></i> Rouvrir la conversation
	            </button>
	            <?php endif; ?>
	            <?php if (!$conv['lu']): ?>
	            <button id="mark-read-btn-<?= $id_conv ?>" type="button" class="text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center" onclick="marquerCommeLu(<?= $id_conv ?>)">
	                <i class="fas fa-check-circle mr-1"></i> Marquer comme lu
	            </button>
	            <?php endif; ?>
	            <button type="button" onclick="closeModal(<?= $id_conv ?>)" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:ring-gray-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Fermer</button>
	        </div>
	    </div>
	</div>
	<?php endforeach; ?>

</main>

<script>
// Fonctions pour gérer l'ouverture et la fermeture des modales
function openModal(id_message) {
    const modal = document.getElementById(`messageModal${id_message}`);
    if (modal) {
        modal.classList.remove('hidden');
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
        modal.onclick = null;
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
            const row = document.getElementById(`message-row-${id_message}`);
            if (row) {
                row.classList.remove('bg-yellow-50', 'font-semibold', 'hover:bg-yellow-100');
                row.classList.add('hover:bg-gray-50');
            }
            
            const statusBadge = document.getElementById(`status-badge-${id_message}`);
            if (statusBadge) {
                statusBadge.classList.remove('bg-red-100', 'text-red-800');
                statusBadge.classList.add('bg-green-100', 'text-green-800');
                statusBadge.textContent = 'Lu';
            }
            
            const modalHeader = document.getElementById(`modal-header-${id_message}`);
            if (modalHeader) {
                modalHeader.classList.remove('bg-yellow-100', 'text-yellow-800');
                modalHeader.classList.add('bg-gray-100', 'text-gray-800');
            }
            
            const button = document.getElementById(`mark-read-btn-${id_message}`);
            if (button) {
                button.style.display = 'none';
            }
            
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
                        compteur.classList.add('bg-gray-400');
                    }
                }
            }
            
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
	
	function closeConversation(id_message) {
	    if (!confirm("Êtes-vous sûr de vouloir FERMER cette conversation ? L'utilisateur ne pourra plus y répondre.")) {
	        return;
	    }
	
	    fetch('/ajax/ajax_contact.php', {
	        method: 'POST',
	        headers: {
	            'Content-Type': 'application/x-www-form-urlencoded',
	        },
	        body: 'id_message=' + encodeURIComponent(id_message) + '&action=close_conversation'
	    })
	    .then(response => response.json())
	    .then(data => {
	        if (data.success) {
	            alert(data.message);
	            location.reload();
	        } else {
	            alert('Erreur de fermeture : ' + (data.message || 'Une erreur inconnue est survenue.'));
	        }
	    })
	    .catch(err => {
	        console.error('Erreur AJAX :', err);
	        alert('Erreur de connexion. Réessayez plus tard.');
	    });
	}
	
	function openConversation(id_message) {
	    if (!confirm("Êtes-vous sûr de vouloir ROUVRIR cette conversation ? L'utilisateur pourra à nouveau y répondre.")) {
	        return;
	    }
	
	    fetch('/ajax/ajax_contact.php', {
	        method: 'POST',
	        headers: {
	            'Content-Type': 'application/x-www-form-urlencoded',
	        },
	        body: 'id_message=' + encodeURIComponent(id_message) + '&action=open_conversation'
	    })
	    .then(response => response.json())
	    .then(data => {
	        if (data.success) {
	            alert(data.message);
	            location.reload();
	        } else {
	            alert('Erreur de réouverture : ' + (data.message || 'Une erreur inconnue est survenue.'));
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

	function sendReply(event, id_message) {
	    event.preventDefault();
	
	    const replyTextarea = document.getElementById(`reply-text-${id_message}`);
	    const reply = replyTextarea.value.trim();
	
	    if (reply.length < 5) {
	        alert("Veuillez écrire une réponse d'au moins 5 caractères.");
	        return;
	    }
	
	    if (!confirm("Confirmez-vous l'envoi de cette réponse à l'utilisateur ?")) {
	        return;
	    }
	
	    // On change l'action pour indiquer que c'est une réponse continue
	    fetch('/ajax/ajax_contact.php', {
	        method: 'POST',
	        headers: {
	            'Content-Type': 'application/x-www-form-urlencoded',
	        },
	        // L'action 'continue_reply' devra gérer l'ajout du message dans la base de données
	        // et mettre à jour le statut 'lu' si nécessaire.
	        body: 'id_message=' + encodeURIComponent(id_message) + '&action=continue_reply&reponse_admin=' + encodeURIComponent(reply)
	    })
	    .then(response => response.json())
	    .then(data => {
	        if (data.success) {
	            alert(data.message);
	            // Au lieu de recharger la page, on pourrait mettre à jour la modale
	            // Mais pour simplifier, on recharge pour voir le nouveau message
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

async function validateBien(id) {
    if (!confirm("Valider ce bien ? Il sera immédiatement visible par tous les utilisateurs.")) return;

    const form = new FormData();
    form.append('id_bien', id);

    try {
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

<?php include '../Pages/footer.php'; ?>

</body>
</html>