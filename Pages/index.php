<?php
session_start();

// Chemin absolu vers la racine → marche partout
$root = realpath(__DIR__ . '/../') . '/';
require_once '../include/db.php';
require 'Bien/bien_class.php';
require 'Communes/communes_class.php';
require 'TypeBien/typebien_class.php';
require  'Tarifs/tarifs_class.php';
$controller = new BiensController($pdo);

// SEULEMENT LES BIENS VALIDÉS
$biens = $controller->getAllBiens();

// === DESTINATIONS POPULAIRES ===
// Liste des grandes villes pour les destinations populaires
$villesMajeurs = ['Paris', 'Lyon', 'Marseille', 'Bordeaux', 'Toulouse', 'Nice', 'Nantes', 'Strasbourg', 'Montpellier', 'Lille', 'Angers', 'Grenoble'];

// Mapping des images pour chaque grande ville 
$imagesVilles = [
    'Paris' => '../Photo/villes/paris.jpg',
    'Lyon' => '../Photo/villes/lyon.jpg',
    'Marseille' => '../Photo/villes/marseille.jpg',
    'Bordeaux' => '../Photo/villes/bordeaux.jpg',
    'Toulouse' => '../Photo/villes/toulouse.jpg',
    'Nice' => '../Photo/villes/nice.jpg',
    'Nantes' => '../Photo/villes/nantes.jpg',
    'Strasbourg' => '../Photo/villes/strasbourg.jpg',
    'Montpellier' => '../Photo/villes/montpellier.jpeg',
    'Lille' => '../Photo/villes/lille.jpg',
    'Angers' => '../Photo/villes/angers.jpg',
    'Grenoble' => '../Photo/villes/grenoble.jpg',
];

// Compter les biens par commune et garder uniquement les grandes villes
$communesUniques = [];
foreach ($biens as $b) {
    $commune = $b['nom_commune'] ?? 'Inconnue';
    
    // Filtrer uniquement les grandes villes
    if (!in_array($commune, $villesMajeurs)) {
        continue;
    }
    
    $cp = $b['cp_commune'] ?? '';
    $key = $commune . '_' . $cp;
    $communesUniques[$key] ??= ['nom' => $commune, 'cp' => $cp, 'count' => 0];
    $communesUniques[$key]['count']++;
}

uasort($communesUniques, fn($a,$b) => $b['count'] - $a['count']);
$topCommunes = array_slice($communesUniques, 0, 4, true);

if (empty($topCommunes)) {
    $topCommunes = [
        ['nom' => 'Paris',     'cp' => '75000', 'count' => 0],
        ['nom' => 'Lyon',      'cp' => '69000', 'count' => 0],
        ['nom' => 'Marseille', 'cp' => '13000', 'count' => 0],
        ['nom' => 'Bordeaux',  'cp' => '33000', 'count' => 0],
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holidaze - Locations de vacances</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" href="../Photo/icon.png" type="image/png">
    <style>
        #loader { display: none; }
        .badge-note { background: rgba(0,0,0,0.75); backdrop-filter: blur(4px); }
    </style>
</head>
<body class="bg-gray-50">

<?php include '../Pages/header.php'; ?>

<!-- HERO -->
<section class="hero bg-gradient-to-br from-indigo-600 to-purple-700 text-white py-32 px-4 text-center relative overflow-hidden">
        <div class="absolute inset-0 bg-black opacity-20"></div>
        <div class="max-w-4xl mx-auto relative z-10">
            <h1 class="text-5xl md:text-6xl font-bold mb-4 leading-tight">Découvrez des séjours uniques</h1>
            <p class="text-xl mb-8 opacity-90">Des locations meublées pour des vacances mémorables</p>

            <!-- FILTRES RAPIDES (gardés) -->
            <div class="flex flex-wrap gap-3 justify-center">
                <a href="?type=maison" class="bg-white border border-gray-200 px-4 py-2 rounded-full text-sm hover:bg-gray-100 transition">Maisons</a>
                <a href="?type=appartement" class="bg-white border border-gray-200 px-4 py-2 rounded-full text-sm hover:bg-gray-100 transition">Appartements</a>
                <a href="?type=villa" class="bg-white border border-gray-200 px-4 py-2 rounded-full text-sm hover:bg-gray-100 transition">Villas</a>
                <a href="../Bien/bien_form.php" class="bg-white border border-gray-200 px-4 py-2 rounded-full text-sm hover:bg-gray-100 transition">+ Ajouter un bien</a>
            </div>
        </div>
    </section>

<!-- DESTINATIONS POPULAIRES -->
<section class="max-w-7xl mx-auto px-6 py-16">
    <h2 class="text-4xl font-bold text-center mb-12">Destinations populaires</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
        <?php foreach ($topCommunes as $c):
            $img = $imagesVilles[$c['nom']] ?? "https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=800";
        ?>
            <a href="../recherche.php?destination=<?= urlencode($c['nom']) ?>" class="rounded-2xl overflow-hidden shadow-xl relative h-80">
                <img src="<?= $img ?>" alt="<?= htmlspecialchars($c['nom']) ?>" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/70"></div>
                <div class="absolute bottom-8 left-8 text-white">
                    <h3 class="text-3xl font-bold"><?= htmlspecialchars($c['nom']) ?></h3>
                    <p><?= $c['count'] ?> location<?= $c['count'] > 1 ? 's' : '' ?></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- RECHERCHE AVANCÉE + LISTE -->
<section class="max-w-7xl mx-auto px-6 py-12">
    <h2 class="text-4xl font-bold text-center mb-12">Toutes les locations</h2>

    <!-- FILTRES -->
    <div class="bg-white rounded-2xl shadow-xl p-8 mb-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Champ Ville avec autocomplétion -->
            <div>
                <label class="block font-bold mb-2 text-gray-700">Ville, code postal ou département</label>
                <div class="relative">
                    <input type="text" id="filter-commune" placeholder="Paris, 75008, 69..." autocomplete="off"
                           class="w-full px-4 py-3 border rounded-xl focus:ring-4 focus:ring-blue-200">
                    <div id="communeResults" class="absolute z-20 bg-white border border-gray-300 rounded-lg shadow-lg mt-1 w-full hidden max-h-60 overflow-y-auto"></div>
                </div>
            </div>
            <div>
                <label class="block font-bold mb-2 text-gray-700">Type</label>
                <select id="filter-type" class="w-full px-4 py-3 border rounded-xl">
                    <option value="">Tous</option>
                    <?php foreach ($pdo->query("SELECT DISTINCT des_typebien FROM type_bien ORDER BY des_typebien")->fetchAll(PDO::FETCH_COLUMN) as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block font-bold mb-2 text-gray-700">Prix / nuit</label>
                <div class="flex gap-3">
                    <input type="number" id="prix-min" placeholder="Min" class="w-full px-3 py-3 border rounded-xl">
                    <input type="number" id="prix-max" placeholder="Max" class="w-full px-3 py-3 border rounded-xl">
                </div>
            </div>
            <div>
                <label class="block font-bold mb-2 text-gray-700">Prestations</label>
                <div class="max-h-48 overflow-y-auto space-y-2">
                    <?php foreach ($pdo->query("SELECT id_prestation, libelle_prestation FROM prestation ORDER BY libelle_prestation")->fetchAll() as $p): ?>
                        <label class="flex items-center">
                            <input type="checkbox" value="<?= $p['id_prestation'] ?>" class="filter-prestation">
                            <span class="ml-2"><?= htmlspecialchars($p['libelle_prestation']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="text-right mt-8">
            <button id="reset-filters" class="text-blue-600 font-bold text-lg hover:underline">Réinitialiser</button>
        </div>
    </div>

    <!-- Résultats + Loader -->
    <div class="flex items-center gap-4 mb-8">
        <div id="loader" class="text-blue-600"><i class="fas fa-spinner fa-spin"></i> Recherche...</div>
        <div class="font-bold text-xl"><span id="nb-resultats">0</span> location<span id="plural">s</span></div>
    </div>

    <div id="liste-biens" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-10"></div>

    <div id="aucun-resultat" class="text-center py-20 hidden">
        <i class="fas fa-home text-8xl text-gray-300 mb-6"></i>
        <p class="text-3xl text-gray-600">Aucune location trouvée</p>
    </div>
</section>

<?php include '../Pages/footer.php'; ?>

<script>
let timer;

// === AUTOCOMPLÉTION COMMUNE / CP / DÉPARTEMENT ===
const filterCommune = document.getElementById('filter-commune');
const communeResults = document.getElementById('communeResults');

filterCommune.addEventListener('input', function() {
    const query = this.value.trim();
    if (query.length >= 2) {
        let url = `https://geo.api.gouv.fr/communes?nom=${encodeURIComponent(query)}&fields=nom,code,codesPostaux,departement,population&boost=population&limit=10`;

        if (/^\d{1,5}$/.test(query)) {
            if (query.length === 5) {
                url = `https://geo.api.gouv.fr/communes?codePostal=${query}&fields=nom,code,codesPostaux,departement,population&limit=10`;
            } else {
                url = `https://geo.api.gouv.fr/communes?codeDepartement=${query.padStart(2, '0')}&fields=nom,code,codesPostaux,departement,population&limit=20`;
            }
        }

        fetch(url)
            .then(res => res.json())
            .then(data => {
                communeResults.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(c => {
                        const cps = c.codesPostaux ? c.codesPostaux.join(', ') : 'Inconnu';
                        const item = document.createElement('div');
                        item.className = 'p-3 cursor-pointer hover:bg-blue-100 border-b last:border-b-0';
                        item.innerHTML = `
                            <div class="font-semibold">${c.nom}</div>
                            <div class="text-sm text-gray-600">
                                ${cps} — Département ${c.departement.code} (${c.departement.nom})
                            </div>
                        `;
                        item.addEventListener('click', () => {
                            filterCommune.value = c.nom;
                            communeResults.classList.add('hidden');
                            load(); // ← La recherche ne se lance QUE quand on clique sur une suggestion
                        });
                        communeResults.appendChild(item);
                    });
                    communeResults.classList.remove('hidden');
                } else {
                    communeResults.innerHTML = '<div class="p-3 text-gray-600">Aucune commune trouvée</div>';
                    communeResults.classList.remove('hidden');
                }
            })
            .catch(() => {
                communeResults.innerHTML = '<div class="p-3 text-red-600">Erreur de recherche</div>';
                communeResults.classList.remove('hidden');
            });
    } else {
        communeResults.classList.add('hidden');
    }
});

// Fermer les résultats si clic ailleurs
document.addEventListener('click', function(e) {
    if (!filterCommune.contains(e.target) && !communeResults.contains(e.target)) {
        communeResults.classList.add('hidden');
    }
});

function load() {
    document.getElementById('loader').style.display = 'block';
    document.getElementById('liste-biens').innerHTML = '';

    clearTimeout(timer);
    timer = setTimeout(() => {
        const commune = document.getElementById('filter-commune').value.trim();
        const type = document.getElementById('filter-type').value;
        const min = document.getElementById('prix-min').value || '';
        const max = document.getElementById('prix-max').value || '';
        const pres = Array.from(document.querySelectorAll('.filter-prestation:checked')).map(c => c.value);

        let url = `../ajax/ajax_recherche_filtre.php?commune=${encodeURIComponent(commune)}&type=${type}&prix_min=${min}&prix_max=${max}`;
        if (pres.length) url += '&' + pres.map(p => `prestations[]=${p}`).join('&');

        fetch(url)
            .then(r => r.json())
            .then(data => {
                document.getElementById('loader').style.display = 'none';
                document.getElementById('nb-resultats').textContent = data.count || 0;

                const container = document.getElementById('liste-biens');
                if (data.count === 0) {
                    document.getElementById('aucun-resultat').classList.remove('hidden');
                    return;
                }
                document.getElementById('aucun-resultat').classList.add('hidden');
                container.innerHTML = '';

                data.biens.forEach(b => {
                    const photo = b.premiere_photo_lien ? '/' + b.premiere_photo_lien : 'https://via.placeholder.com/600x400.png?text=Photo';
                    const note = b.note_moyenne 
                        ? `<div class="absolute top-4 right-4 bg-black/80 text-white px-4 py-2 rounded-full text-sm font-bold"><i class="fas fa-star text-yellow-400"></i> ${b.note_moyenne} (${b.nb_avis})</div>`
                        : '<div class="absolute top-4 right-4 bg-gradient-to-r from-purple-600 to-pink-600 text-white px-5 py-2 rounded-full text-sm font-bold">Nouveau</div>';
                    const animaux = b.animaux_bien == 1 ? '<div class="absolute top-4 left-4 bg-green-600 text-white px-4 py-2 rounded-full text-sm">Animaux OK</div>' : '';
                    const prix = b.prix_min_nuit ? `<p class="text-3xl font-bold text-blue-600">€${Math.round(b.prix_min_nuit)} <span class="text-lg font-normal text-gray-600">/nuit</span></p>` : '<p class="text-gray-500 italic">Prix sur demande</p>';

                    container.innerHTML += `
                    <a href="Bien/bien_detail.php?id=${b.id_bien}" class="bg-white rounded-2xl overflow-hidden shadow-xl hover:shadow-2xl transition hover:-translate-y-3">
                        <div class="relative h-64">
                            <img src="${photo}" class="w-full h-full object-cover">
                            ${note}${animaux}
                        </div>
                        <div class="p-8">
                            <h3 class="text-2xl font-bold mb-2">${b.nom_bien}</h3>
                            <p class="text-gray-600 flex items-center gap-2"><i class="fas fa-map-marker-alt"></i> ${b.nom_commune}</p>
                            ${prix}
                            <div class="flex gap-4 text-sm text-gray-600 mt-4">
                                <span>${b.superficie_bien} m²</span>
                                <span>${b.nb_couchage} couchages</span>
                                <span>${b.des_typebien}</span>
                            </div>
                        </div>
                    </a>`;
                });
            })
            .catch(() => document.getElementById('loader').style.display = 'none');
    }, 400);
}

// Lancement au chargement
document.addEventListener('DOMContentLoaded', () => {
    // On retire l'écouteur input sur le champ commune
    // → la recherche ne se lance plus pendant la saisie

    document.getElementById('filter-type').addEventListener('change', load);
    document.getElementById('prix-min').addEventListener('input', load);
    document.getElementById('prix-max').addEventListener('input', load);
    document.querySelectorAll('.filter-prestation').forEach(c => c.addEventListener('change', load));
    document.getElementById('reset-filters').onclick = () => {
        document.querySelectorAll('input, select').forEach(el => el.value = '');
        document.querySelectorAll('.filter-prestation').forEach(c => c.checked = false);
        load();
    };
    load(); // Chargement initial (tous les biens)
});
</script>
</body>
</html>