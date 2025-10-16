<?php
require_once '../include/db.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord administrateur</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <?php include 'header.php'; ?>

    <main class="max-w-4xl mx-auto mt-10 bg-white shadow-md rounded-lg p-6">
        <h1 class="text-3xl font-bold mb-6">Tableau de bord administrateur</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Gestion des biens -->
            <a href="/Pages/Bien/bien_form.php" class="block p-6 bg-blue-100 rounded-lg shadow hover:bg-blue-200 transition">
                <h2 class="text-xl font-semibold text-blue-800">Gestion des biens</h2>
                <p class="mt-2 text-gray-600">Ajoutez, modifiez ou supprimez des biens immobiliers.</p>
            </a>

            <!-- Gestion des photos -->
            <a href="/Pages/Photo/utiliser.php" class="block p-6 bg-green-100 rounded-lg shadow hover:bg-green-200 transition">
                <h2 class="text-xl font-semibold text-green-800">Gestion des photos</h2>
                <p class="mt-2 text-gray-600">Visualisez et gérez les photos associées aux biens.</p>
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
        </div>
    </main>
</body>
</html>