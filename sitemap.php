<?php
require_once __DIR__ . '/include/db.php';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];

header('Content-Type: application/xml; charset=UTF-8');

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

$excludedPrefixes = [
    '/Pages/Admin/',
    '/Pages/Utilisateur/',
    '/Pages/Formulaires/',
    '/Pages/Contact/',
    '/Pages/Mes_Reservations/',
    '/Pages/Profil/',
    '/Pages/Réservations/',
    '/Pages/Bien/bien_form.php',
    '/Pages/Bien/bien_traitement.php',
    '/Pages/admin_dashboard.php',
];

$pagesRoot = realpath(__DIR__ . '/Pages');
if ($pagesRoot) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($pagesRoot, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $relative = str_replace($pagesRoot, '', $file->getPathname());
        $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
        $path = '/Pages' . $relative;

        $isExcluded = false;
        foreach ($excludedPrefixes as $prefix) {
            if (strpos($path, $prefix) === 0) {
                $isExcluded = true;
                break;
            }
        }
        if ($isExcluded) {
            continue;
        }

        $lastmod = date('Y-m-d', $file->getMTime());
        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($baseUrl . $path, ENT_QUOTES, 'UTF-8') . "</loc>\n";
        echo "    <lastmod>" . $lastmod . "</lastmod>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>0.7</priority>\n";
        echo "  </url>\n";
    }
}

try {
    $stmt = $pdo->query("SELECT id_bien, date_creation FROM bien ORDER BY id_bien DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $lastmod = !empty($row['date_creation']) ? date('Y-m-d', strtotime($row['date_creation'])) : date('Y-m-d');
        $loc = $baseUrl . '/Pages/Bien/bien_detail.php?id=' . urlencode((string)$row['id_bien']);
        echo "  <url>\n";
        echo "    <loc>" . htmlspecialchars($loc, ENT_QUOTES, 'UTF-8') . "</loc>\n";
        echo "    <lastmod>" . $lastmod . "</lastmod>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>0.9</priority>\n";
        echo "  </url>\n";
    }
} catch (Exception $e) {
    // Silent fail to avoid breaking sitemap generation
}

echo "</urlset>\n";
