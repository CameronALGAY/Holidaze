<?php
session_start();

// Sécurité : seul un utilisateur connecté peut accéder à cette page
if (!isset($_SESSION['utilisateur_id'])) {
    $_SESSION['error_message'] = "Vous devez être connecté pour accéder à vos messages.";
    header('Location: ../../Formulaires/connexion.php');
    exit;
}

// Chemins corrects depuis Pages/Utilisateur/
require_once '../../include/db.php';

$utilisateur_id = $_SESSION['utilisateur_id'];

// Récupération des messages de l'utilisateur (non supprimés par l'admin)
$stmt = $pdo->prepare("
    SELECT m.id_message, m.sujet, m.message, m.date_envoi, m.lu, m.reponse_admin, m.date_reponse
    FROM messages_contact m 
    WHERE m.id_utilisateur = ? AND m.supprime = 0
    ORDER BY m.date_envoi DESC
");
$stmt->execute([$utilisateur_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Messages - Holidaze</title>
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
        <i class="fas fa-envelope mr-3 text-indigo-600"></i> Mes Messages
    </h1>

    <?php if (empty($messages)): ?>
        <div class="text-center py-16 border-2 border-dashed border-gray-300 rounded-lg">
            <i class="fas fa-inbox text-6xl text-gray-400 mb-4"></i>
            <p class="text-xl text-gray-600">Vous n'avez envoyé aucun message pour le moment.</p>
            <a href="../Contact/contact.php" class="mt-4 inline-block text-indigo-600 hover:text-indigo-800 font-semibold">
                Envoyer un nouveau message
            </a>
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($messages as $msg): ?>
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 hover:shadow-md transition duration-150 ease-in-out">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-1">
                                <?= htmlspecialchars($msg['sujet']) ?>
                            </h2>
                            <p class="text-sm text-gray-500">
                                Envoyé le <?= date('d/m/Y à H:i', strtotime($msg['date_envoi'])) ?>
                            </p>
                        </div>
                        <div class="flex flex-col items-end space-y-2">
                            <?php if (!empty($msg['reponse_admin'])): ?>
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                    Réponse de l'Admin
                                </span>
                            <?php elseif (!$msg['lu']): ?>
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                    Non lu par l'Admin
                                </span>
                            <?php else: ?>
                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                    Lu par l'Admin
                                </span>
                            <?php endif; ?>
                            
                            <button onclick="openModal(<?= $msg['id_message'] ?>)"
                                    class="text-indigo-600 hover:text-indigo-900 focus:outline-none text-sm font-medium">
                                <i class="fas fa-eye mr-1"></i> Voir le détail
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Modales pour chaque message -->
    <?php foreach ($messages as $msg): ?>
    <div id="messageModal<?= $msg['id_message'] ?>" class="fixed inset-0 modal-overlay h-full w-full hidden z-50 overflow-y-auto" aria-labelledby="modal-title-<?= $msg['id_message'] ?>" role="dialog" aria-modal="true">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <!-- Modal Header -->
            <div class="flex justify-between items-center p-4 border-b rounded-t bg-indigo-50 text-indigo-800">
                <h3 class="text-xl font-semibold" id="modal-title-<?= $msg['id_message'] ?>">
                    <i class="fas fa-comment-dots mr-2"></i> <?= htmlspecialchars($msg['sujet']) ?>
                </h3>
                <button type="button" onclick="closeModal(<?= $msg['id_message'] ?>)" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <!-- Modal Body -->
            <div class="p-6">
                <p class="text-sm text-gray-500 mb-4">Envoyé le <?= date('d/m/Y à H:i', strtotime($msg['date_envoi'])) ?></p>

                <!-- Message de l'utilisateur -->
                <h4 class="text-lg font-semibold mb-2 text-gray-700">Votre message :</h4>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6 whitespace-pre-wrap">
                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                </div>

                <!-- Réponse de l'administrateur -->
                <h4 class="text-lg font-semibold mb-2 text-gray-700">Réponse de l'administrateur :</h4>
                <?php if (!empty($msg['reponse_admin'])): ?>
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200 mb-6">
                        <p class="text-sm text-gray-600 mb-2">Répondu le <?= date('d/m/Y à H:i', strtotime($msg['date_reponse'])) ?></p>
                        <div class="text-gray-800 whitespace-pre-wrap">
                            <?= nl2br(htmlspecialchars($msg['reponse_admin'])) ?>
                        </div>
                    </div>
                    
                    <!-- Formulaire de suivi (Réponse à la réponse de l'admin) -->
                    <h4 class="text-lg font-semibold mb-2 text-gray-700">Répondre à l'administrateur :</h4>
                    <form onsubmit="sendFollowUp(event, <?= $msg['id_message'] ?>)" class="space-y-4">
                        <textarea id="follow-up-<?= $msg['id_message'] ?>" rows="3" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Écrivez votre message de suivi ici..."></textarea>
                        <button type="submit" class="text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            <i class="fas fa-paper-plane mr-1"></i> Envoyer le suivi
                        </button>
                    </form>

                <?php else: ?>
                    <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200 mb-6 text-gray-700">
                        L'administrateur n'a pas encore répondu à ce message.
                    </div>
                <?php endif; ?>
            </div>
            <!-- Modal Footer -->
            <div class="flex items-center p-4 border-t rounded-b justify-end">
                <button type="button" onclick="closeModal(<?= $msg['id_message'] ?>)" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:ring-gray-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10">Fermer</button>
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

function sendFollowUp(event, id_message) {
    event.preventDefault(); // Empêche l'envoi du formulaire par défaut

    const followUpTextarea = document.getElementById(`follow-up-${id_message}`);
    const followUp = followUpTextarea.value.trim();

    if (followUp.length < 5) {
        alert("Veuillez écrire un message de suivi d'au moins 5 caractères.");
        return;
    }

    if (!confirm("Confirmez-vous l'envoi de ce message de suivi ?")) {
        return;
    }

    // Nous allons utiliser un nouveau script AJAX pour les messages de suivi
    // ou réutiliser le script de contact initial si vous en avez un.
    // Pour l'instant, je vais simuler l'envoi et vous demander de créer le script côté serveur.
    
    // NOTE: Vous devrez créer un script côté serveur (ex: ../Contact/contact_submit.php)
    // pour traiter ce message de suivi comme un NOUVEAU message de contact
    // (ou un message lié au précédent si vous voulez une vraie conversation).
    
    // Pour simplifier, nous allons le traiter comme un nouveau message de contact
    // en utilisant l'ID du message précédent dans le sujet pour le suivi.
    
    // Pour le moment, je vais juste vous donner la structure JS.
    
    alert("Fonctionnalité d'envoi de suivi non implémentée côté serveur. Veuillez créer le script de traitement.");
    
    // Exemple de ce à quoi ressemblerait l'appel AJAX pour un nouveau message :
    /*
    fetch('../Contact/contact_submit.php', { // Chemin à adapter
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'sujet=' + encodeURIComponent('SUIVI Message #' + id_message) + '&message=' + encodeURIComponent(followUp)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Message de suivi envoyé avec succès !');
            location.reload();
        } else {
            alert('Erreur d\'envoi : ' + (data.message || 'Une erreur inconnue est survenue.'));
        }
    })
    .catch(err => {
        console.error('Erreur AJAX :', err);
        alert('Erreur de connexion. Réessayez plus tard.');
    });
    */
}

</script>

<?php include '../footer.php'; // Chemin à ajuster si nécessaire ?>

</body>
</html>
