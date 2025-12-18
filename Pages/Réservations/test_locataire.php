<?php
// Mettre ce fichier au même endroit que reservation_traitement.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../../include/db.php';
require_once 'reservation_class.php';

echo "<h1>Test recherche locataire</h1>";

// Test 1: Connexion DB
echo "<h2>1. Test connexion base de données</h2>";
try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connexion OK<br>";
    
    // Compter les locataires
    $count = $pdo->query("SELECT COUNT(*) FROM locataire")->fetchColumn();
    echo "📊 Nombre total de locataires: <strong>$count</strong><br>";
    
    if ($count == 0) {
        echo "⚠️ <span style='color:red'>PROBLÈME: Votre table locataire est vide!</span><br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur connexion: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Afficher quelques locataires
echo "<h2>2. Premiers locataires dans la table</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM locataire LIMIT 5");
    $locataires = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($locataires)) {
        echo "⚠️ Aucun locataire trouvé<br>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Email</th><th>Tel</th></tr>";
        foreach ($locataires as $l) {
            echo "<tr>";
            echo "<td>" . ($l['id_locataire'] ?? 'N/A') . "</td>";
            echo "<td>" . ($l['nom_locataire'] ?? 'N/A') . "</td>";
            echo "<td>" . ($l['prenom_locataire'] ?? 'N/A') . "</td>";
            echo "<td>" . ($l['mail_locataire'] ?? 'N/A') . "</td>";
            echo "<td>" . ($l['tel_locataire'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "<br>";
}

// Test 3: Test avec LIKE simple
echo "<h2>3. Test recherche avec LIKE '%a%'</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM locataire WHERE nom_locataire LIKE :q LIMIT 5");
    $stmt->execute(['q' => '%a%']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Résultats trouvés: <strong>" . count($results) . "</strong><br>";
    
    if (!empty($results)) {
        echo "<pre>";
        print_r($results);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "<br>";
}

// Test 4: Test via le controller
echo "<h2>4. Test via ReservationsController</h2>";
try {
    $controller = new ReservationsController($pdo);
    
    // Test avec 'a'
    $results = $controller->searchLocataires('a');
    echo "Recherche 'a': <strong>" . count($results) . "</strong> résultat(s)<br>";
    
    // Test avec une lettre commune
    $results2 = $controller->searchLocataires('e');
    echo "Recherche 'e': <strong>" . count($results2) . "</strong> résultat(s)<br>";
    
    // Test avec une chaîne vide
    $results3 = $controller->searchLocataires('');
    echo "Recherche '' (vide): <strong>" . count($results3) . "</strong> résultat(s)<br>";
    
    if (!empty($results)) {
        echo "<pre>";
        print_r($results);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur controller: " . $e->getMessage() . "<br>";
}

// Test 5: Simuler l'appel AJAX
echo "<h2>5. Simulation appel AJAX</h2>";
echo "<p>Testez cette URL dans votre navigateur:</p>";
echo "<a href='reservation_traitement.php?autocomplete=locataire&q=a' target='_blank'>";
echo "reservation_traitement.php?autocomplete=locataire&q=a</a><br>";

echo "<h2>6. Structure de la table</h2>";
try {
    $stmt = $pdo->query("DESCRIBE locataire");
    $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($structure as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "<br>";
}
?>