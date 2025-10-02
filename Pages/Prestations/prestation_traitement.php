<?php

include "prestation_class.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $libelle_prestation = $_POST['libelle_prestation'];

    $prestation = new Prestation($id, $libelle_prestation);

    echo "ID: " . $prestation->getId() . "<br>";
    echo "Libellé de la prestation: " . $prestation->getLibelle() . "<br>";
} else {
    echo "Méthode de requête invalide.";
}