<?php
session_start();

// Sécurité : seul un utilisateur connecté peut accéder à cette page
if (!isset($_SESSION['utilisateur_id'])) {
    $_SESSION['error_message'] = "Vous devez être connecté pour accéder à vos messages.";
    header('Location: ../../Formulaires/connexion.php');
    exit;
}

// Chemins corrects depuis Pages/Contact/
require_once '../../include/db.php';

$utilisateur_id = $_SESSION['utilisateur_id'];

// Récupération des conversations de l'utilisateur (non supprimées)
$stmt = $pdo->prepare("
    SELECT c.id_conversation, c.sujet, c.date_creation, c.lu, c.statut,
           m_last.date_envoi AS date_dernier_message,
           m_last.role_expediteur AS derniere_expediteur
    FROM conversations c 
    LEFT JOIN messages m_last ON m_last.id_message = (
        SELECT id_message 
        FROM messages 
        WHERE id_conversation = c.id_conversation 
        ORDER BY date_envoi DESC 
        LIMIT 1
    )
    WHERE c.id_utilisateur = ? AND c.supprime = 0
    ORDER BY c.date_creation DESC
");
$stmt->execute([$utilisateur_id]);
$conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Holidaze</title>
    <meta name="description" content="Consultez vos messages et conversations avec l'equipe Holidaze.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?php
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        echo $scheme . '://' . $_SERVER['HTTP_HOST'] . '/Pages/Contact/mes_messages.php';
    ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Styles pour les modales Tailwind */
        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.5);
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

<?php include '../header.php'; // Chemin à ajuster si nécessaire ?>

<main class="max-w-4xl mx-auto mt-10 bg-white shadow-md rounded-lg p-6">
    <h1 class="text-3xl font-bold mb-6 flex items-center">
        <i class="fas fa-envelope mr-3 text-indigo-600"></i> Messages
    </h1>

    <?php if (empty($conversations)): ?>
        <div class="text-center py-16 border-2 border-dashed border-gray-300 rounded-lg">
            <i class="fas fa-inbox text-6xl text-gray-400 mb-4"></i>
            <p class="text-xl text-gray-600">Vous n'avez envoyé aucun message pour le moment.</p>
            <a href="../Contact/contact.php" class="mt-4 inline-block text-indigo-600 hover:text-indigo-800 font-semibold">
                Envoyer un nouveau message
            </a>
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($conversations as $conv): ?>
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 hover:shadow-md transition duration-150 ease-in-out">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-1">
                                <?= htmlspecialchars($conv['sujet']) ?>
                            </h2>
                            <p class="text-sm text-gray-500">
                                Créé le <?= date('d/m/Y à H:i', strtotime($conv['date_creation'])) ?>
                                <?php if (!empty($conv['date_dernier_message'])): ?>
                                    • Dernier message: <?= date('d/m/Y à H:i', strtotime($conv['date_dernier_message'])) ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="flex flex-col items-end space-y-2">
                            <?php 
                                $status_text = 'Statut inconnu';
                                $status_class = 'bg-gray-100 text-gray-800';
                                
                                if ($conv['lu'] == 0) {
                                    $status_text = 'Non lu par l\'admin';
                                    $status_class = 'bg-red-100 text-red-800';
                                } elseif ($conv['derniere_expediteur'] == 'utilisateur') {
                                    $status_text = 'En attente de réponse';
                                    $status_class = 'bg-yellow-100 text-yellow-800';
                                } elseif ($conv['derniere_expediteur'] == 'admin') {
                                    $status_text = 'Répondu (attente vôtre)';
                                    $status_class = 'bg-blue-100 text-blue-800';
                                }
                            ?>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $status_class ?>">
                                <?= $status_text ?>
                            </span>
                            
                            <button onclick="openModal(<?= $conv['id_conversation'] ?>)"
                                    class="text-indigo-600 hover:text-indigo-900 focus:outline-none text-sm font-medium">
                                <i class="fas fa-eye mr-1"></i> Voir la conversation
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Modales pour chaque conversation -->
    <?php foreach ($conversations as $conv): 
        // Récupérer tous les messages de la conversation
        $stmt_messages = $pdo->prepare("
            SELECT id_message, role_expediteur, contenu, date_envoi 
            FROM messages 
            WHERE id_conversation = ? 
            ORDER BY date_envoi ASC
        ");
        $stmt_messages->execute([$conv['id_conversation']]);
        $messages = $stmt_messages->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div id="messageModal<?= $conv['id_conversation'] ?>" class="fixed inset-0 modal-overlay h-full w-full hidden z-50 overflow-y-auto" aria-labelledby="modal-title-<?= $conv['id_conversation'] ?>" role="dialog" aria-modal="true">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <!-- Modal Header -->
            <div class="flex justify-between items-center p-4 border-b rounded-t bg-indigo-50 text-indigo-800">
                <h3 class="text-xl font-semibold" id="modal-title-<?= $conv['id_conversation'] ?>">
                    <i class="fas fa-comment-dots mr-2"></i> <?= htmlspecialchars($conv['sujet']) ?>
                </h3>
                <button type="button" onclick="closeModal(<?= $conv['id_conversation'] ?>)" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <!-- Modal Body -->
            <div class="p-6 max-h-96 overflow-y-auto">
                <!-- Conversation style fil de discussion -->
                <div class="space-y-4">
                    <?php foreach ($messages as $msg): ?>
                        <?php 
                            $is_user = ($msg['role_expediteur'] === 'utilisateur');
                            $bg_class = $is_user ? 'bg-indigo-50 border-l-4 border-indigo-500' : 'bg-blue-50 border-l-4 border-blue-500';
                            $icon = $is_user ? 'fa-user-circle' : 'fa-user-shield';
                            $icon_color = $is_user ? 'text-indigo-600' : 'text-blue-600';
                            $sender_name = $is_user ? 'Vous' : 'Administrateur';
                        ?>
                        <div class="p-4 rounded-lg <?= $bg_class ?>">
                            <div class="flex items-center mb-2">
                                <i class="fas <?= $icon ?> <?= $icon_color ?> mr-2"></i>
                                <span class="font-semibold text-gray-700"><?= $sender_name ?></span>
                                <span class="text-xs text-gray-500 ml-auto">
                                    <?= date('d/m/Y à H:i', strtotime($msg['date_envoi'])) ?>
                                </span>
                            </div>
                            <div class="text-gray-800 whitespace-pre-wrap break-words">
                                <?= nl2br(htmlspecialchars($msg['contenu'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Formulaire pour répondre si le dernier message est de l'admin -->
                    <?php 
                        $last_message = end($messages);
                        if ($last_message && $last_message['role_expediteur'] === 'admin'): 
                    ?>
                        <div class="bg-white p-4 rounded-lg border-2 border-dashed border-gray-300 mt-4">
                            <h4 class="text-lg font-semibold mb-3 text-gray-700 flex items-center">
                                <i class="fas fa-reply mr-2 text-indigo-600"></i>
                                Répondre à l'administrateur
                            </h4>
                            <form onsubmit="sendFollowUp(event, <?= $conv['id_conversation'] ?>)" class="space-y-4">
                                <textarea id="follow-up-<?= $conv['id_conversation'] ?>" rows="3" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Écrivez votre réponse ici..."></textarea>
                                <button type="submit" class="text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                                    <i class="fas fa-paper-plane mr-1"></i> Envoyer la réponse
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Modal Footer -->
            <div class="flex items-center p-4 border-t rounded-b justify-end">
                <button type="button" onclick="closeModal(<?= $conv['id_conversation'] ?>)" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:ring-gray-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Fermer</button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

</main>

<script>
// Fonctions pour gérer l'ouverture et la fermeture des modales (Tailwind CSS)
function openModal(id_conversation) {
    const modal = document.getElementById(`messageModal${id_conversation}`);
    if (modal) {
        modal.classList.remove('hidden');
        // Ajoute un écouteur pour fermer la modale en cliquant en dehors
        modal.onclick = function(event) {
            if (event.target === modal) {
                closeModal(id_conversation);
            }
        };
    }
}

function closeModal(id_conversation) {
    const modal = document.getElementById(`messageModal${id_conversation}`);
    if (modal) {
        modal.classList.add('hidden');
        modal.onclick = null; // Supprime l'écouteur
    }
}

function sendFollowUp(event, id_conversation) {
    event.preventDefault();

    const followUpTextarea = document.getElementById(`follow-up-${id_conversation}`);
    const followUp = followUpTextarea.value.trim();

    if (followUp.length < 5) {
        alert("Veuillez écrire une réponse d'au moins 5 caractères.");
        return;
    }

    if (!confirm("Confirmez-vous l'envoi de cette réponse ?")) {
        return;
    }

    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Envoi en cours...';

    fetch('contact_reply.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id_conversation=' + encodeURIComponent(id_conversation) + '&message=' + encodeURIComponent(followUp)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Votre réponse a été envoyée avec succès !');
            location.reload();
        } else {
            alert('Erreur d\'envoi : ' + (data.message || 'Une erreur inconnue est survenue.'));
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    })
    .catch(err => {
        console.error('Erreur AJAX :', err);
        alert('Erreur de connexion. Réessayez plus tard.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
}

</script>

<?php include '../footer.php'; // Chemin à ajuster si nécessaire ?>

</body>
</html>