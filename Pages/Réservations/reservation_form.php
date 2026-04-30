<?php
include '../header.php';
require_once '../../include/db.php';
require_once '../../include/csrf.php';
require_once 'reservation_class.php';

$controller = new ReservationsController($pdo);

// Vérifier si édition
$editId = $_GET['edit'] ?? null;
$editReservation = $editId ? $controller->getReservationById($editId) : null;

// Toutes les réservations
$reservations = $controller->getAllReservations();

// Tous les locataires
$locataires = $controller->getAllLocataires();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion des réservations</title>
<meta name="description" content="Interface de gestion des reservations pour l'administration Holidaze.">
<meta name="robots" content="noindex, nofollow">
<link rel="canonical" href="<?php
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    echo $scheme . '://' . $_SERVER['HTTP_HOST'] . '/Pages/R%C3%A9servations/reservation_form.php';
?>">
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-6xl mx-auto bg-white shadow-md rounded-lg p-6">
    <h1 class="text-2xl font-bold mb-4">📅 Gestion des réservations</h1>

    <form method="POST" action="reservation_traitement.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $editId ? 'update' : 'create' ?>">
        <input type="hidden" name="id_reservation" value="<?= $editReservation['id_reservations'] ?? '' ?>">

        <!-- Date de début -->
        <div>
            <label class="block text-gray-700 mb-2 font-semibold">Date de début :</label>
            <input type="date" name="date_debut" required 
                   class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300"
                   value="<?= htmlspecialchars($editReservation['date_debut'] ?? '') ?>">
        </div>

        <!-- Date de fin -->
        <div>
            <label class="block text-gray-700 mb-2 font-semibold">Date de fin :</label>
            <input type="date" name="date_fin" required 
                   class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300"
                   value="<?= htmlspecialchars($editReservation['date_fin'] ?? '') ?>">
        </div>

        <!-- Autocomplétion Locataire -->
        <div class="relative">
            <label class="block text-gray-700 mb-2 font-semibold">Locataire :</label>
            <input type="text" id="locataireSearch" autocomplete="off" placeholder="Rechercher un locataire..."
                   class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300"
                   value="<?= isset($editReservation['nom_locataire']) ? htmlspecialchars($editReservation['nom_locataire'] . ' ' . $editReservation['prenom_locataire']) : '' ?>">
            <input type="hidden" name="id_locataire" id="id_locataire" value="<?= htmlspecialchars($editReservation['id_locataire'] ?? '') ?>">
            <div id="locataireResults" class="absolute z-10 bg-white border rounded-lg shadow-lg mt-1 w-full hidden max-h-60 overflow-y-auto"></div>
        </div>

        <!-- Autocomplétion Bien -->
        <div class="relative">
            <label class="block text-gray-700 mb-2 font-semibold">Bien :</label>
            <input type="text" id="bienSearch" autocomplete="off" placeholder="Rechercher un bien..."
                   class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300"
                   value="<?= htmlspecialchars($editReservation['nom_bien'] ?? '') ?>">
            <input type="hidden" name="id_bien" id="id_bien" value="<?= htmlspecialchars($editReservation['id_bien'] ?? '') ?>">
            <div id="bienResults" class="absolute z-10 bg-white border rounded-lg shadow-lg mt-1 w-full hidden max-h-60 overflow-y-auto"></div>
        </div>

        <!-- Autocomplétion Tarif -->
        <div class="relative md:col-span-2">
            <label class="block text-gray-700 mb-2 font-semibold">Tarif :</label>
            <input type="text" id="tarifSearch" autocomplete="off" placeholder="D'abord sélectionner un bien..."
                   class="w-full border rounded-lg p-2 focus:ring focus:ring-blue-300" disabled
                   value="<?= isset($editReservation['tarif']) ? 'S' . $editReservation['semaine_tarif'] . ' - ' . $editReservation['annee_tarif'] . ' : ' . number_format($editReservation['tarif'], 2) . ' €' : '' ?>">
            <input type="hidden" name="id_tarif" id="id_tarif" value="<?= htmlspecialchars($editReservation['id_tarif'] ?? '') ?>">
            <div id="tarifResults" class="absolute z-10 bg-white border rounded-lg shadow-lg mt-1 w-full hidden max-h-60 overflow-y-auto"></div>
        </div>

        <div class="md:col-span-2">
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 w-full">
                <?= $editId ? '💾 Modifier la réservation' : '➕ Ajouter une réservation' ?>
            </button>
        </div>
    </form>

    <!-- Liste des réservations -->
    <h2 class="text-xl font-semibold mt-6 mb-2">📋 Liste des réservations</h2>
    <div class="border rounded-lg p-4 bg-gray-50 overflow-x-auto">
        <?php if ($reservations): ?>
            <table class="w-full">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="p-2 text-left">Période</th>
                        <th class="p-2 text-left">Locataire</th>
                        <th class="p-2 text-left">Bien</th>
                        <th class="p-2 text-left">Tarif</th>
                        <th class="p-2 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $r): ?>
                        <tr class="border-b hover:bg-gray-100">
                            <td class="p-2">
                                <div class="font-semibold text-blue-600">
                                    <?= date('d/m/Y', strtotime($r['date_debut'])) ?>
                                </div>
                                <div class="text-sm text-gray-600">
                                    au <?= date('d/m/Y', strtotime($r['date_fin'])) ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php 
                                    $debut = new DateTime($r['date_debut']);
                                    $fin = new DateTime($r['date_fin']);
                                    $diff = $debut->diff($fin);
                                    echo $diff->days . ' jour(s)';
                                    ?>
                                </div>
                            </td>
                            <td class="p-2">
                                <div class="font-semibold">
                                    <?= htmlspecialchars($r['nom_locataire'] . ' ' . $r['prenom_locataire']) ?>
                                </div>
                            </td>
                            <td class="p-2">
                                <div class="font-semibold text-green-700">
                                    <?= htmlspecialchars($r['nom_bien']) ?>
                                </div>
                            </td>
                            <td class="p-2">
                                <div class="font-semibold text-purple-600">
                                    <?= number_format($r['tarif'], 2, ',', ' ') ?> €
                                </div>
                                <div class="text-xs text-gray-600">
                                    S<?= htmlspecialchars($r['semaine_tarif']) ?> - <?= htmlspecialchars($r['annee_tarif']) ?>
                                    <?php if (!empty($r['libelle_saison'])): ?>
                                        <span class="text-orange-600">(<?= htmlspecialchars($r['libelle_saison']) ?>)</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="p-2 text-center">
                                <div class="flex gap-2 justify-center">
                                    <a href="reservation_form.php?edit=<?= $r['id_reservations'] ?>" 
                                       class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">✏️</a>
                                    <a href="#" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600"
                                       onclick="if(confirm('Voulez-vous vraiment supprimer cette réservation ?')) {
                                           fetch('reservation_traitement.php', {
                                               method: 'POST',
                                               headers: {'Content-Type':'application/x-www-form-urlencoded'},
                                               body: 'action=delete&id_reservation=<?= $r['id_reservations'] ?>'
                                           }).then(()=> window.location='reservation_form.php');
                                       }">🗑️</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="text-gray-500">Aucune réservation trouvée.</p>
        <?php endif; ?>
    </div>
</div>

<script>
// Variables globales
const locataireSearch = document.getElementById('locataireSearch');
const locataireResults = document.getElementById('locataireResults');
const id_locataire = document.getElementById('id_locataire');

const bienSearch = document.getElementById('bienSearch');
const bienResults = document.getElementById('bienResults');
const id_bien = document.getElementById('id_bien');

const tarifSearch = document.getElementById('tarifSearch');
const tarifResults = document.getElementById('tarifResults');
const id_tarif = document.getElementById('id_tarif');

// === LOCATAIRE AUTOCOMPLETE ===
locataireSearch.addEventListener('input', function() {
    const query = this.value.trim();
    console.log('🔍 Recherche locataire:', query, '(longueur:', query.length + ')');
    
    if(query.length >= 2) {
        const url = `reservation_traitement.php?autocomplete=locataire&q=${encodeURIComponent(query)}`;
        console.log('📡 URL:', url);
        
        fetch(url)
            .then(res => {
                console.log('📥 Status HTTP:', res.status);
                if (!res.ok) throw new Error('Erreur réseau: ' + res.status);
                return res.text();
            })
            .then(text => {
                console.log('📄 Réponse brute:', text);
                try {
                    const data = JSON.parse(text);
                    console.log('✅ JSON parsé:', data);
                    console.log('   - success:', data.success);
                    console.log('   - data:', data.data);
                    console.log('   - nombre:', data.data ? data.data.length : 0);
                    
                    locataireResults.innerHTML = '';
                    
                    if(data.success && data.data && data.data.length > 0) {
                        console.log('✨ Affichage de', data.data.length, 'résultats');
                        
                        data.data.forEach((l, index) => {
                            console.log('  Locataire', index, ':', l);
                            const item = document.createElement('div');
                            item.className = 'p-3 cursor-pointer hover:bg-blue-100 border-b';
                            item.innerHTML = `
                                <div class="font-semibold text-blue-700">${l.nom_locataire} ${l.prenom_locataire}</div>
                                <div class="text-sm text-gray-600">
                                    ${l.mail_locataire ? `📧 ${l.mail_locataire}` : ''} 
                                    ${l.tel_locataire ? `📞 ${l.tel_locataire}` : ''}
                                </div>
                            `;
                            item.addEventListener('click', () => {
                                console.log('👆 Sélection:', l);
                                locataireSearch.value = `${l.nom_locataire} ${l.prenom_locataire}`;
                                id_locataire.value = l.id_locataire;
                                locataireResults.classList.add('hidden');
                            });
                            locataireResults.appendChild(item);
                        });
                        locataireResults.classList.remove('hidden');
                        console.log('✅ Dropdown affiché');
                    } else {
                        console.warn('⚠️ Aucun résultat');
                        locataireResults.innerHTML = '<div class="p-3 text-center text-gray-600">Aucun locataire trouvé</div>';
                        locataireResults.classList.remove('hidden');
                    }
                } catch(e) {
                    console.error('❌ Erreur parsing JSON:', e);
                    console.error('Texte reçu:', text);
                    throw e;
                }
            })
            .catch(err => {
                console.error('❌ Erreur autocomplétion locataire:', err);
                locataireResults.innerHTML = '<div class="p-3 text-center text-red-600">Erreur: ' + err.message + '</div>';
                locataireResults.classList.remove('hidden');
            });
    } else {
        console.log('⏸️ Recherche trop courte');
        locataireResults.classList.add('hidden');
    }
});

// === BIEN AUTOCOMPLETE ===
bienSearch.addEventListener('input', function() {
    const query = this.value.trim();
    if(query.length >= 2) {
        fetch(`reservation_traitement.php?autocomplete=bien&q=${encodeURIComponent(query)}`)
            .then(res => {
                if (!res.ok) throw new Error('Erreur réseau');
                return res.json();
            })
            .then(data => {
                bienResults.innerHTML = '';
                if(data.success && data.data.length > 0) {
                    data.data.forEach(b => {
                        const item = document.createElement('div');
                        item.className = 'p-3 cursor-pointer hover:bg-blue-100 border-b';
                        item.innerHTML = `
                            <div class="font-semibold text-green-700">${b.nom_bien}</div>
                            <div class="text-sm text-gray-600">${b.description_bien || ''}</div>
                            <div class="text-xs text-gray-500">${b.nom_commune || ''} - ${b.des_typebien || ''}</div>
                        `;
                        item.addEventListener('click', () => {
                            bienSearch.value = b.nom_bien;
                            id_bien.value = b.id_bien;
                            bienResults.classList.add('hidden');
                            
                            // Activer la recherche de tarifs
                            tarifSearch.disabled = false;
                            tarifSearch.placeholder = 'Rechercher un tarif pour ce bien...';
                            tarifSearch.value = '';
                            id_tarif.value = '';
                            
                            // Charger automatiquement les tarifs
                            loadTarifs(b.id_bien);
                        });
                        bienResults.appendChild(item);
                    });
                    bienResults.classList.remove('hidden');
                } else {
                    bienResults.innerHTML = '<div class="p-3 text-center text-gray-600">Aucun bien trouvé</div>';
                    bienResults.classList.remove('hidden');
                }
            })
            .catch(err => {
                console.error('Erreur autocomplétion bien:', err);
                bienResults.innerHTML = '<div class="p-3 text-center text-red-600">Erreur de chargement</div>';
                bienResults.classList.remove('hidden');
            });
    } else {
        bienResults.classList.add('hidden');
    }
});

// === TARIF AUTOCOMPLETE ===
tarifSearch.addEventListener('input', function() {
    const bienId = id_bien.value;
    if (!bienId) {
        alert('Veuillez d\'abord sélectionner un bien');
        return;
    }
    
    const query = this.value.trim();
    loadTarifs(bienId, query);
});

function loadTarifs(bienId, query = '') {
    fetch(`reservation_traitement.php?autocomplete=tarif&id_bien=${bienId}&q=${encodeURIComponent(query)}`)
        .then(res => {
            if (!res.ok) throw new Error('Erreur réseau');
            return res.json();
        })
        .then(data => {
            tarifResults.innerHTML = '';
            if(data.success && data.data.length > 0) {
                data.data.forEach(t => {
                    const item = document.createElement('div');
                    item.className = 'p-3 cursor-pointer hover:bg-green-100 border-b';
                    item.innerHTML = `
                        <div class="font-semibold text-purple-600">${t.tarif} €</div>
                        <div class="text-sm text-gray-600">
                            Semaine ${t.semaine_tarif || 'N/A'} - Année ${t.annee_tarif || 'N/A'}
                            ${t.libelle_saison ? `<span class="text-orange-600">(${t.libelle_saison})</span>` : ''}
                        </div>
                    `;
                    item.addEventListener('click', () => {
                        tarifSearch.value = `S${t.semaine_tarif} - ${t.annee_tarif} : ${parseFloat(t.tarif).toFixed(2)} €`;
                        id_tarif.value = t.id_tarif;
                        tarifResults.classList.add('hidden');
                    });
                    tarifResults.appendChild(item);
                });
                tarifResults.classList.remove('hidden');
            } else {
                tarifResults.innerHTML = '<div class="p-3 text-center text-gray-600">Aucun tarif trouvé pour ce bien</div>';
                tarifResults.classList.remove('hidden');
            }
        })
        .catch(err => {
            console.error('Erreur chargement tarifs:', err);
            tarifResults.innerHTML = '<div class="p-3 text-center text-red-600">Erreur de chargement</div>';
            tarifResults.classList.remove('hidden');
        });
}

// Fermer les résultats au clic extérieur
document.addEventListener('click', function(e) {
    if (!locataireSearch.contains(e.target) && !locataireResults.contains(e.target)) {
        locataireResults.classList.add('hidden');
    }
    if (!bienSearch.contains(e.target) && !bienResults.contains(e.target)) {
        bienResults.classList.add('hidden');
    }
    if (!tarifSearch.contains(e.target) && !tarifResults.contains(e.target)) {
        tarifResults.classList.add('hidden');
    }
});
</script>
</body>
</html>