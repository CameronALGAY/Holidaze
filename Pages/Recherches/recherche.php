<?php
require_once '../../include/db.php'; // connexion à la BDD

$destination = isset($_GET['destination']) ? trim($_GET['destination']) : null;

if (!$destination) {
    echo "<p style='color:red; text-align:center; margin-top:50px;'>Aucune destination spécifiée.</p>";
    exit;
}

// 🔹 Requête pour récupérer les biens selon la commune
$sql = "SELECT b.id_bien, b.nom_bien, b.rue_bien, b.com_bien, b.superficie_bien, 
               b.description_bien, b.animaux_bien, b.nb_couchage,
               b.id_typebien, tb.des_typebien, c.nom_commune
        FROM bien b
        JOIN commune c ON b.id_commune = c.id_commune
        LEFT JOIN photo ph ON b.id_bien = ph.id_bien
        LEFT JOIN type_bien tb ON b.id_typebien = tb.id_typebien
        WHERE c.nom_commune = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$destination]);
$biens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🔹 Fonction pour récupérer les photos d’un bien
function getPhotosByBienId($pdo, $idBien) {
    $sql = "SELECT lien_photo FROM photo WHERE id_bien = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idBien]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Biens à <?= htmlspecialchars($destination) ?> - ImmoSite</title>
    <meta name="description" content="Locations disponibles a <?= htmlspecialchars($destination) ?> avec photos, details et disponibilites sur Holidaze.">
    <link rel="canonical" href="<?php
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        echo $scheme . '://' . $_SERVER['HTTP_HOST'] . '/Pages/Recherches/recherche.php?destination=' . urlencode($destination);
    ?>">
    <meta property="og:title" content="Biens a <?= htmlspecialchars($destination) ?> - Holidaze">
    <meta property="og:description" content="Consultez les locations disponibles a <?= htmlspecialchars($destination) ?> sur Holidaze.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        echo $scheme . '://' . $_SERVER['HTTP_HOST'] . '/Pages/Recherches/recherche.php?destination=' . urlencode($destination);
    ?>">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800">

<?php include '../header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6 text-center">
            Logements disponibles à <?= htmlspecialchars($destination) ?>
        </h1>

        <?php if (empty($biens)): ?>
            <p class="text-center text-gray-600">
                Aucun bien n’est disponible actuellement à <?= htmlspecialchars($destination) ?>.
            </p>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php foreach ($biens as $bien): ?>
                    <?php
                        // 🔹 On récupère les photos liées à ce bien
                        $photos = getPhotosByBienId($pdo, $bien['id_bien']);
                        $photoUrl = !empty($photos)
                            ? '../../' . $photos[0]['lien_photo'] // ✅ lien_photo contient déjà "Photo/uploads/..."
                            : '../../Photo/uploads/pas-image.jpg';
                    ?>

                    <div class="rounded-xl overflow-hidden shadow-md bg-white hover:shadow-xl transition">
                                <img src="<?= htmlspecialchars($photoUrl) ?>"
                                    alt="Photo de <?= htmlspecialchars($bien['nom_bien']) ?> a <?= htmlspecialchars($bien['nom_commune']) ?>"
                                    loading="lazy" width="320" height="160"
                                    class="w-full h-40 object-cover">

                        <div class="p-4">
                            <h2 class="text-lg font-semibold mb-2">
                                <?= htmlspecialchars($bien['nom_bien']) ?>
                            </h2>

                            <p class="text-sm text-gray-600 mb-1">
                                🏙️ Ville : <?= htmlspecialchars($bien['nom_commune']) ?>
                            </p>

                            <p class="text-sm text-gray-600 mb-1">
                                🏠 Type : <?= htmlspecialchars($bien['des_typebien']) ?>
                            </p>

                            <p class="text-sm text-gray-600 mb-1">
                                📍 Adresse : <?= htmlspecialchars($bien['rue_bien']) ?>
                            </p>

                            <p class="text-sm text-gray-600 mb-1">
                                📏 Superficie : <?= htmlspecialchars($bien['superficie_bien']) ?> m²
                            </p>

                            <p class="text-sm text-gray-600 mb-1">
                                🛏️ Couchages : <?= htmlspecialchars($bien['nb_couchage']) ?>
                            </p>

                            <p class="text-sm text-gray-600 mb-1">
                                🐾 Animaux : <?= htmlspecialchars($bien['animaux_bien']) ?>
                            </p>

                            <p class="text-sm text-gray-600 mt-2 italic text-gray-700">
                                "<?= htmlspecialchars($bien['description_bien']) ?>"
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="text-center mt-8">
            <a href="../index.php"
               class="inline-block bg-purple-600 text-white py-2 px-6 rounded-lg hover:bg-purple-700 transition">
               ← Retour à l’accueil
            </a>
        </div>
    </div>

<?php include '../footer.php'; ?>

</body>
</html>
