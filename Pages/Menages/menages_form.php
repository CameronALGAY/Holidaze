<?php
// menages_form.php

require_once '../../include/db.php';           // connexion PDO
require_once '../../include/csrf.php';
include '../header.php';
require_once 'menages_class.php';             // classe Menage
require_once 'menages_traitement.php';        // MenageManager (ou inclus dans ce fichier)

// Récupération de la connexion PDO
$menageManager = new MenageManager($pdo);

$message = '';
$message_type = '';

// Gestion d'un ménage en modification (si besoin)
$id_menage = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$menage = null;
$errors = [];

if ($id_menage > 0) {
    $menage = $menageManager->getById($id_menage);
    if ($menage === null) {
        $errors[] = "Ménage introuvable.";
    }
}

// Traitement du formulaire (création / modification)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $id_menage      = isset($_POST['id_menage']) ? (int)$_POST['id_menage'] : 0;
    $id_reservations = (int)($_POST['id_reservations'] ?? 0);
    $id_intervenant  = (int)($_POST['id_intervenant'] ?? 0);
    $date_menage     = $_POST['date_menage'] ?? '';
    $statut          = $_POST['statut'] ?? 'a_faire';
    $commentaire     = $_POST['commentaire'] ?? null;

    if ($id_reservations <= 0) {
        $errors[] = "La réservation est obligatoire.";
    }
    if ($id_intervenant <= 0) {
        $errors[] = "L'intervenant est obligatoire.";
    }
    if ($date_menage === '') {
        $errors[] = "La date de ménage est obligatoire.";
    }

    if (empty($errors)) {
        if ($id_menage > 0) {
            // Update
            $menage = $menageManager->getById($id_menage);
            if ($menage !== null) {
                $menage->setIdReservations($id_reservations);
                $menage->setIdIntervenant($id_intervenant);
                $menage->setDateMenage($date_menage);
                $menage->setStatut($statut);
                $menage->setCommentaire($commentaire);

                $menageManager->update($menage);
                $message = "✅ Ménage modifié avec succès.";
                $message_type = 'success';
            } else {
                $message = "❌ Ménage introuvable.";
                $message_type = 'error';
            }
        } else {
            // Insert
            $menage = new Menage(
                $id_reservations,
                $id_intervenant,
                $date_menage,
                $statut,
                $commentaire
            );
            $menageManager->add($menage);
            $message = "✅ Ménage ajouté avec succès.";
            $message_type = 'success';
        }
    } else {
        $message = implode(' ', $errors);
        $message_type = 'error';
    }
}

// Préparation des valeurs (si tu veux préremplir en cas de modif)
if ($menage === null) {
    $id_reservations_value = '';
    $id_intervenant_value  = '';
    $date_menage_value     = '';
    $statut_value          = 'a_faire';
    $commentaire_value     = '';
} else {
    $id_reservations_value = $menage->getIdReservations();
    $id_intervenant_value  = $menage->getIdIntervenant();
    $date_menage_value     = $menage->getDateMenage();
    $statut_value          = $menage->getStatut();
    $commentaire_value     = $menage->getCommentaire();
}

// ---- CHARGEMENT DE LA LISTE DES MENAGES ----
$search_reservation = $_GET['search_reservation'] ?? '';

if ($search_reservation !== '') {
    $menages = $menageManager->getByReservation((int)$search_reservation);
} else {
    $menages = $menageManager->getAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des ménages</title>
    <meta name="description" content="Interface de gestion des menages pour l'administration Holidaze.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?php
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        echo $scheme . '://' . $_SERVER['HTTP_HOST'] . '/Pages/Menages/menages_form.php';
    ?>">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">
<main class="max-w-5xl mx-auto mt-10 space-y-8">
    <h1 class="text-3xl font-bold text-slate-800">Gestion des ménages</h1>

    <!-- Message -->
    <?php if ($message): ?>
        <div class="border-l-4 px-4 py-3 rounded <?php echo $message_type === 'success'
            ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
            : 'border-rose-500 bg-rose-50 text-rose-700'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Formulaire d'ajout -->
    <section class="bg-white shadow rounded-xl p-6">
        <h2 class="text-xl font-semibold text-slate-800 mb-4">➕ Ajouter un ménage</h2>

        <form action="" method="POST" id="menageForm" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="id_menage" value="0">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Autocomplete Réservation -->
                <div class="relative">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Réservation</label>
                    <input type="text" id="reservation_search" autocomplete="off"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           placeholder="Tapez un locataire, un bien ou %%% pour tout afficher">
                    <input type="hidden" name="id_reservations" id="id_reservations" required>
                    <div id="reservation_results"
                         class="absolute left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden z-20 text-sm">
                    </div>
                    <p class="mt-1 text-xs text-slate-500">
                        Sélectionnez une réservation dans la liste pour lier le ménage.
                    </p>
                </div>

                <!-- Autocomplete Intervenant -->
                <div class="relative">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Intervenant</label>
                    <input type="text" id="intervenant_search" autocomplete="off"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                           placeholder="Tapez un nom d'intervenant ou %%% pour tout afficher">
                    <input type="hidden" name="id_intervenant" id="id_intervenant" required>
                    <div id="intervenant_results"
                         class="absolute left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden z-20 text-sm">
                    </div>
                    <p class="mt-1 text-xs text-slate-500">
                        Sélectionnez un intervenant dans la liste.
                    </p>
                </div>

                <!-- Date -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Date du ménage</label>
                    <input type="date" name="date_menage" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Statut</label>
                    <select name="statut"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="a_faire">À faire</option>
                        <option value="en_cours">En cours</option>
                        <option value="termine">Terminé</option>
                    </select>
                </div>

                <div class="md:col-span-1">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Commentaire</label>
                    <textarea name="commentaire" rows="2"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                              placeholder="Ex: Prévoir temps supplémentaire, intervention après 11h..."></textarea>
                </div>
            </div>

            <div class="pt-2">
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    ➕ Ajouter le ménage
                </button>
            </div>
        </form>
    </section>

    <!-- Filtre par réservation -->
    <section class="bg-white shadow rounded-xl p-6">
        <h2 class="text-lg font-semibold text-slate-800 mb-3">🔍 Filtrer par réservation</h2>
        <form action="" method="GET" class="flex flex-col md:flex-row gap-3 items-start md:items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-slate-700 mb-1">ID réservation</label>
                <input type="number" name="search_reservation"
                       value="<?php echo htmlspecialchars($search_reservation); ?>"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                       placeholder="Ex: 29">
            </div>
            <button type="submit"
                    class="inline-flex items-center gap-2 bg-emerald-500 text-white px-4 py-2 rounded-lg text-sm font-medium shadow hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                🔍 Filtrer
            </button>
        </form>
    </section>

    <!-- Liste des ménages -->
    <section class="bg-white shadow rounded-xl p-6">
        <h2 class="text-lg font-semibold text-slate-800 mb-4">📋 Liste des ménages</h2>

        <?php if (empty($menages)): ?>
            <p class="text-sm text-slate-600">Aucun ménage trouvé.</p>
        <?php else: ?>
            <div class="divide-y divide-slate-200">
                <?php foreach ($menages as $m): ?>
                    <div class="py-3 flex justify-between items-start">
                        <div class="space-y-1">
                            <div class="text-sm font-semibold text-slate-800">
                                Réservation #<?php echo htmlspecialchars($m->getIdReservations()); ?>
                                • Intervenant #<?php echo htmlspecialchars($m->getIdIntervenant()); ?>
                            </div>
                            <div class="text-xs text-slate-600">
                                Date : <?php echo htmlspecialchars($m->getDateMenage()); ?>
                                • Statut :
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs
                                    <?php
                                    echo $m->getStatut() === 'termine'  ? 'bg-emerald-100 text-emerald-700' :
                                         ($m->getStatut() === 'en_cours' ? 'bg-amber-100 text-amber-700' :
                                                                            'bg-slate-100 text-slate-700');
                                    ?>">
                                    <?php echo htmlspecialchars($m->getStatut()); ?>
                                </span>
                            </div>
                            <?php if ($m->getCommentaire()): ?>
                                <div class="text-xs text-slate-600">
                                    Commentaire : <?php echo htmlspecialchars($m->getCommentaire()); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <!-- Boutons si tu veux plus tard : modifier / supprimer -->
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<script>
/* ---------- AUTOCOMPLETE RESERVATION ---------- */
const searchInput = document.getElementById('reservation_search');
const hiddenIdInput = document.getElementById('id_reservations');
const resultsBox = document.getElementById('reservation_results');

let reservationTimeout;

if (searchInput) {
    searchInput.addEventListener('input', function () {
        const query = this.value.trim();

        hiddenIdInput.value = '';
        clearTimeout(reservationTimeout);

        // Cas spécial "%%%" => tout afficher
        if (query !== '%%%') {
            const clean = query.replace(/[%_]/g, '');
            if (clean.length < 2) {
                resultsBox.classList.add('hidden');
                resultsBox.innerHTML = '';
                return;
            }
        }

        reservationTimeout = setTimeout(() => {
            fetchReservations(query);
        }, 300);
    });

    document.addEventListener('click', (e) => {
        if (!resultsBox.contains(e.target) && e.target !== searchInput) {
            resultsBox.classList.add('hidden');
        }
    });
}

function fetchReservations(query) {
    resultsBox.innerHTML = '<div class="px-3 py-2 text-xs text-slate-500">Recherche...</div>';
    resultsBox.classList.remove('hidden');

    // Depuis Pages/Menages/ vers /ajax/
    fetch('../../ajax/ajax_reservations.php?q=' + encodeURIComponent(query))
        .then(res => res.json())
        .then(data => {
            if (!data.success || !Array.isArray(data.reservations) || data.reservations.length === 0) {
                resultsBox.innerHTML = '<div class="px-3 py-2 text-xs text-slate-500">Aucune réservation trouvée</div>';
                return;
            }
            displayReservationResults(data.reservations);
        })
        .catch(() => {
            resultsBox.innerHTML = '<div class="px-3 py-2 text-xs text-rose-600">Erreur de chargement</div>';
        });
}

function displayReservationResults(list) {
    resultsBox.innerHTML = '';
    list.forEach(item => {
        const div = document.createElement('div');
        div.className = 'px-3 py-2 cursor-pointer hover:bg-slate-100';
        div.textContent = item.label;
        div.onclick = () => selectReservation(item);
        resultsBox.appendChild(div);
    });
    resultsBox.classList.remove('hidden');
}

function selectReservation(item) {
    searchInput.value = item.label;
    hiddenIdInput.value = item.id_reservations;
    resultsBox.classList.add('hidden');
}

/* ---------- AUTOCOMPLETE INTERVENANT ---------- */
const intervSearchInput = document.getElementById('intervenant_search');
const intervHiddenIdInput = document.getElementById('id_intervenant');
const intervResultsBox = document.getElementById('intervenant_results');
let intervTimeout;

if (intervSearchInput) {
    intervSearchInput.addEventListener('input', function () {
        const query = this.value.trim();
        intervHiddenIdInput.value = '';
        clearTimeout(intervTimeout);

        if (query !== '%%%') {
            const clean = query.replace(/[%_]/g, '');
            if (clean.length < 1) {
                intervResultsBox.classList.add('hidden');
                intervResultsBox.innerHTML = '';
                return;
            }
        }

        intervTimeout = setTimeout(() => {
            fetchIntervenants(query);
        }, 300);
    });

    document.addEventListener('click', (e) => {
        if (!intervResultsBox.contains(e.target) && e.target !== intervSearchInput) {
            intervResultsBox.classList.add('hidden');
        }
    });
}

function fetchIntervenants(query) {
    intervResultsBox.innerHTML = '<div class="px-3 py-2 text-xs text-slate-500">Recherche...</div>';
    intervResultsBox.classList.remove('hidden');

    fetch('../../ajax/ajax_intervenants.php?q=' + encodeURIComponent(query))
        .then(res => res.json())
        .then(data => {
            if (!data.success || !Array.isArray(data.intervenants) || data.intervenants.length === 0) {
                intervResultsBox.innerHTML = '<div class="px-3 py-2 text-xs text-slate-500">Aucun intervenant trouvé</div>';
                return;
            }
            displayIntervenantResults(data.intervenants);
        })
        .catch(() => {
            intervResultsBox.innerHTML = '<div class="px-3 py-2 text-xs text-rose-600">Erreur de chargement</div>';
        });
}

function displayIntervenantResults(list) {
    intervResultsBox.innerHTML = '';
    list.forEach(item => {
        const div = document.createElement('div');
        div.className = 'px-3 py-2 cursor-pointer hover:bg-slate-100';
        div.textContent = item.label;
        div.onclick = () => selectIntervenant(item);
        intervResultsBox.appendChild(div);
    });
    intervResultsBox.classList.remove('hidden');
}

function selectIntervenant(item) {
    intervSearchInput.value = item.label;
    intervHiddenIdInput.value = item.id_intervenant;
    intervResultsBox.classList.add('hidden');
}
</script>
</body>
</html>