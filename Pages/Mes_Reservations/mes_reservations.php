<?php
session_start();
require_once '../../include/db.php'; // Assurez-vous que le chemin vers db.php est correct

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: /Pages/connexion.php'); // Rediriger vers la page de connexion si non connecté
    exit;
}

$id_locataire = $_SESSION['utilisateur_id'];

// 1. Récupérer toutes les réservations de l'utilisateur
$sqlReservations = "SELECT r.date_debut, r.date_fin, b.nom_bien, b.id_bien
                    FROM reservation r
                    JOIN bien b ON r.id_bien = b.id_bien
                    WHERE r.id_locataire = ?";
$stmt = $pdo->prepare($sqlReservations);
$stmt->execute([$id_locataire]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Formater les réservations pour FullCalendar
$events = [];
foreach ($reservations as $r) {
    // FullCalendar utilise la date de fin de manière exclusive, donc on ajoute un jour pour inclure la dernière nuit
    $end_date_exclusive = date('Y-m-d', strtotime($r['date_fin'] . ' +1 day'));
    
    $events[] = [
        'title' => 'Réservé : ' . htmlspecialchars($r['nom_bien']),
        'start' => $r['date_debut'],
        'end'   => $end_date_exclusive,
        'url'   => '/Pages/Bien/bien_detail.php?id=' . $r['id_bien'], // Lien vers la page du bien
        'backgroundColor' => '#2563eb', // Couleur bleue pour les réservations
        'borderColor'     => '#1e40af',
        'textColor'       => '#fff',
        'allDay'          => true
    ];
}

$events_json = json_encode($events);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Réservations</title>
    <meta name="description" content="Consultez vos reservations Holidaze et accedez a vos sejours.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?php
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        echo $scheme . '://' . $_SERVER['HTTP_HOST'] . '/Pages/Mes_Reservations/mes_reservations.php';
    ?>">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- FullCalendar -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales/fr.global.min.js'></script>
    
    <style>
        /* S'assurer que le calendrier prend de la place */
        #calendar {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 15px;
        }
    </style>
</head>
<body class="bg-gray-50">

<?php include '../header.php'; ?>

<div class="main-content">
    <div class="container mx-auto px-4 py-10 max-w-7xl">
        <h1 class="text-4xl font-bold" style="color: #2563eb;" >Réservations</h1>
        
        <div id='calendar' class="bg-white p-6 rounded-lg shadow-xl"></div>
        
    </div>
</div>

<?php include '../footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function( ) {
        const calendarEl = document.getElementById('calendar');
        const events = <?= $events_json ?>;

        const calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'fr',
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek,close'
            },
            customButtons: {
                close: {
                    text: 'Fermer',
                    click: function() {
                        window.history.back();
                    }
                }
            },
            events: events,
            eventClick: function(info) {
                if (info.event.url) {
                    window.location.href = info.event.url;
                    return false;
                }
            }
        });

        calendar.render();
    });
</script>

</body>
</html>
