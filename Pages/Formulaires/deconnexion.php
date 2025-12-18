<?php
session_start(); // Démarre la session
session_destroy(); // Détruit toutes les données de la session
header('Location: connexion.php'); // Redirige vers la page de connexion
exit;
?>