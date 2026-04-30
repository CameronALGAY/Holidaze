<?php
session_start();
require_once '../../include/csrf.php';
// =================================================================================
// FICHIER DE TRAITEMENT DU FORMULAIRE D'INSCRIPTION
// =================================================================================

// 1. Configuration de la base de données
$host = 'localhost';
$db   = 'holidaze';
$user = 'root';
$pass = ''; // Mot de passe vide comme spécifié par l'utilisateur
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // En cas d'échec de connexion, afficher une erreur et arrêter
     die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// 2. Vérification de la soumission du formulaire
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header('Location: /inscription.php');
    exit();
}

csrf_verify();

// 3. Récupération et validation des données communes
$errors = [];

// Fonction pour récupérer les données et les nettoyer
function get_post_data($key) {
    return trim(filter_input(INPUT_POST, $key, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
}

$type_entite = get_post_data('type_entite');
$nom = get_post_data('nom');
$prenom = get_post_data('prenom');
$email = get_post_data('email');
$mot_de_passe = $_POST['mot_de_passe'] ?? ''; // Ne pas nettoyer le mot de passe avant le hachage
$tel = get_post_data('tel');
$country_code = get_post_data('country_code');

// Validation de base
if (empty($nom)) $errors[] = "Le nom est requis.";
if (empty($prenom)) $errors[] = "Le prénom est requis.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'adresse email est invalide.";
if (strlen($mot_de_passe) < 8) $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";

// 4. Validation spécifique pour l'entreprise
if ($type_entite === 'entreprise') {
    $nom_entreprise = get_post_data('nom_entreprise');
    $numero_siret = get_post_data('numero_siret');
    $adresse_siege = get_post_data('adresse_siege');
    $ville_siege = get_post_data('ville_siege');
    $code_postal_siege = get_post_data('code_postal_siege');

    if (empty($nom_entreprise)) $errors[] = "Le nom de l'entreprise est requis.";
    if (strlen($numero_siret) !== 14) $errors[] = "Le numéro SIRET doit contenir 14 chiffres.";
    if (empty($adresse_siege)) $errors[] = "L'adresse du siège est requise.";
    // Note: On pourrait ajouter plus de validation ici (ex: vérifier l'existence de l'email dans la DB)
}

// 5. Gestion des erreurs de validation
if (!empty($errors)) {
    // En cas d'erreurs, vous devriez idéalement rediriger vers le formulaire
    // en conservant les données et en affichant les messages d'erreur.
    // Pour l'instant, nous affichons les erreurs pour le débogage.
    echo "<h1>Erreurs de validation:</h1>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
    exit();
}

// Si aucune erreur, continuer avec l'insertion en base de données

// 6. Hachage du mot de passe
$mot_de_passe_hache = password_hash($mot_de_passe, PASSWORD_DEFAULT);

// 7. Insertion dans la table utilisateurs
try {
    // Récupérer la date de naissance
    $date_naissance = get_post_data('date_naissance');
    if (empty($date_naissance)) $errors[] = "La date de naissance est requise.";

    // Si des erreurs ont été ajoutées, on arrête l'insertion ici
    if (!empty($errors)) throw new Exception("Validation failed before insertion.");

    // Vérifier si l'email existe déjà
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = "Cet email est déjà utilisé.";
        // Rediriger ou afficher l'erreur
        // ... (Logique de gestion des erreurs à la fin)
    } else {
        // MODIFICATION : Ajout de la colonne date_naissance
        $sql = "INSERT INTO utilisateurs (email, mot_de_passe, nom, prenom, date_naissance, tel, type_entite, photo_profil) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $email, 
            $mot_de_passe_hache, 
            $nom, 
            $prenom, 
            $date_naissance, // NOUVEAU PARAMÈTRE
            $country_code . ' ' . $tel, // Concaténer le code pays et le numéro
            $type_entite,
            '' // Valeur par défaut pour photo_profil
        ]);
        
        $id_utilisateur = $pdo->lastInsertId();
    }
} catch (\PDOException $e) {
    // Gérer l'erreur d'insertion
    $errors[] = "Erreur lors de l'enregistrement de l'utilisateur: " . $e->getMessage();
    // ... (Logique de gestion des erreurs à la fin)
} catch (Exception $e) {
    // Gérer l'erreur de validation
    // L'erreur est déjà dans $errors, on continue vers la gestion finale des erreurs
}

// 8. Insertion conditionnelle dans la table entreprises
if ($type_entite === 'entreprise' && empty($errors)) {
    // S'assurer que l'utilisateur a bien été inséré
    if (isset($id_utilisateur)) {
        try {
            $sql = "INSERT INTO entreprises (id_utilisateur, nom_entreprise, numero_siret, adresse_siege, ville_siege, code_postal_siege) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $id_utilisateur,
                $nom_entreprise,
                $numero_siret,
                $adresse_siege,
                $ville_siege,
                $code_postal_siege
            ]);
        } catch (\PDOException $e) {
            // Gérer l'erreur d'insertion de l'entreprise
            $errors[] = "Erreur lors de l'enregistrement de l'entreprise: " . $e->getMessage();
            // Optionnel: Supprimer l'utilisateur inséré si l'insertion de l'entreprise échoue
            // $pdo->prepare("DELETE FROM utilisateurs WHERE id_utilisateur = ?")->execute([$id_utilisateur]);
        }
    } else {
        // Ce cas se produit si l'email existait déjà. L'erreur a déjà été ajoutée au tableau $errors.
    }
}

// 9. Gestion finale des erreurs et redirection
if (!empty($errors)) {
    // Afficher les erreurs (pour le débogage)
    // Pour une application en production, vous devriez rediriger l'utilisateur
    // vers le formulaire avec des messages d'erreur clairs.
    echo "<h1>Erreur(s) lors de l'inscription :</h1>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul>";
    echo '<p><a href=\"inscription.php\">Retour au formulaire</a></p>';
    exit();
} else {
    // Redirection en cas de succès
    // L'utilisateur est redirigé vers la page de connexion avec un message de succès.
    header('Location: connexion.php?success=inscription');
    exit();
}

?>
