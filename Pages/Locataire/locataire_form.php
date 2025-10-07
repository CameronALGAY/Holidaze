<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajout Locataire</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Styles pour l'autocomplétion */
        .autocomplete-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #d1d5db;
            border-top: none;
            border-radius: 0 0 0.5rem 0.5rem;
            max-height: 250px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .autocomplete-item {
            padding: 0.75rem;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
        }
        .autocomplete-item:hover {
            background: #f9fafb;
        }
        .autocomplete-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body class="bg-gray-100 p-6">

<div class="max-w-2xl mx-auto bg-white shadow-md rounded-lg p-6">
    <h1 class="text-2xl font-bold mb-6 text-center">Ajouter un Locataire</h1>
    
    <form action="locataire_traitement.php" method="POST">
        
        <!-- Nom -->
        <div class="mb-4">
            <label for="nom" class="block text-gray-700 font-semibold mb-2">Nom :</label>
            <input type="text" name="nom_locataire" id="nom" required
                   class="w-full border border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-300">
        </div>

        <!-- Prénom -->
        <div class="mb-4">
            <label for="prenom" class="block text-gray-700 font-semibold mb-2">Prénom :</label>
            <input type="text" name="prenom_locataire" id="prenom" required
                   class="w-full border border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-300">
        </div>

        <!-- Date de naissance -->
        <div class="mb-4">
            <label for="dna" class="block text-gray-700 font-semibold mb-2">Date de naissance :</label>
            <input type="date" name="dna_locataire" id="dna" required
                   class="w-full border border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-300">
        </div>

        <!-- Email -->
        <div class="mb-4">
            <label for="email" class="block text-gray-700 font-semibold mb-2">Email :</label>
            <input type="email" name="email_locataire" id="email" required
                   class="w-full border border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-300">
        </div>

        <!-- Rue -->
        <div class="mb-4">
            <label for="rue" class="block text-gray-700 font-semibold mb-2">Rue :</label>
            <input type="text" name="rue_locataire" id="rue" required
                   class="w-full border border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-300">
        </div>

        <!-- Téléphone -->
        <div class="mb-4">
            <label for="tel" class="block text-gray-700 font-semibold mb-2">Téléphone :</label>
            <input type="text" name="tel_locataire" id="tel" required
                   class="w-full border border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-300">
        </div>

        <!-- Complément d'adresse -->
        <div class="mb-4">
            <label for="comp" class="block text-gray-700 font-semibold mb-2">Complément d'adresse :</label>
            <input type="text" name="comp_locataire" id="comp"
                   class="w-full border border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-300">
        </div>

        <!-- Commune avec autocomplétion -->
        <div class="mb-4 relative">
            <label for="commune_search" class="block text-gray-700 font-semibold mb-2">Commune :</label>
            <input 
                type="text" 
                id="commune_search" 
                autocomplete="off"
                required
                class="w-full border border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-300"
            >
            <input type="hidden" name="id_commune" id="id_commune" required>
            <div id="autocomplete_results" class="autocomplete-results hidden"></div>
        </div>

        <!-- Mot de passe -->
        <div class="mb-4">
            <label for="pass" class="block text-gray-700 font-semibold mb-2">Mot de passe :</label>
            <input type="password" name="pass_locataire" id="pass" required
                   class="w-full border border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-300">
        </div>

        <!-- Checkbox Entreprise -->
        <div class="mb-4 flex items-center">
            <input type="checkbox" id="isEntreprise" name="isEntreprise" value="1" 
                   onclick="toggleEntrepriseFields()"
                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
            <label for="isEntreprise" class="ml-2 text-gray-700 font-semibold">C'est une entreprise</label>
        </div>

        <!-- Champs entreprise (cachés par défaut) -->
        <div id="entrepriseFields" class="hidden bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
            <h3 class="text-lg font-semibold mb-3 text-gray-700">Informations entreprise</h3>
            
            <div class="mb-4">
                <label for="raison_social" class="block text-gray-700 font-semibold mb-2">Raison sociale :</label>
                <input type="text" name="raison_social" id="raison_social"
                       class="w-full border border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-300">
            </div>

            <div class="mb-4">
                <label for="siret" class="block text-gray-700 font-semibold mb-2">SIRET :</label>
                <input type="text" name="siret" id="siret"
                       class="w-full border border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-300">
            </div>
        </div>

        <!-- Bouton d'envoi -->
        <div class="text-center">
            <button type="submit" 
                    class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition font-semibold">
                Enregistrer le locataire
            </button>
        </div>
    </form>
</div>

<script>
    let searchTimeout;
    const communeSearch = document.getElementById('commune_search');
    const communeIdInput = document.getElementById('id_commune');
    const resultsContainer = document.getElementById('autocomplete_results');

    function toggleEntrepriseFields() {
        let entrepriseFields = document.getElementById("entrepriseFields");
        let checkbox = document.getElementById("isEntreprise");

        if (checkbox.checked) {
            entrepriseFields.classList.remove("hidden");
        } else {
            entrepriseFields.classList.add("hidden");
        }
    }

    communeSearch.addEventListener('input', function() {
        const query = this.value.trim();
        
        // Réinitialiser l'ID caché si l'utilisateur modifie le champ
        communeIdInput.value = '';
        
        // Effacer le timeout précédent
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            resultsContainer.classList.add('hidden');
            resultsContainer.innerHTML = '';
            return;
        }
        
        // Attendre 300ms après la dernière frappe
        searchTimeout = setTimeout(() => {
            searchCommunes(query);
        }, 300);
    });

    function searchCommunes(query) {
        resultsContainer.innerHTML = '<div class="p-3 text-center text-gray-600">Recherche en cours...</div>';
        resultsContainer.classList.remove('hidden');
        
        fetch(`../../ajax/ajax_locataire.php?q=${encodeURIComponent(query)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success && data.communes.length > 0) {
                        displayResults(data.communes);
                    } else {
                        resultsContainer.innerHTML = '<div class="p-3 text-center text-gray-600">Aucune commune trouvée</div>';
                    }
                } catch (e) {
                    console.error('Erreur parsing JSON:', text);
                    resultsContainer.innerHTML = '<div class="p-3 text-center text-red-600">Erreur de réponse du serveur</div>';
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                resultsContainer.innerHTML = '<div class="p-3 text-center text-red-600">Erreur de connexion</div>';
            });
    }

    function displayResults(communes) {
        resultsContainer.innerHTML = '';
        
        communes.forEach(commune => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item';
            item.innerHTML = `
                <div class="font-semibold text-gray-800">${commune.nom_commune}</div>
                <div class="text-sm text-gray-600">
                    ${commune.cp_commune ? 'CP: ' + commune.cp_commune : ''}
                    ${commune.commune_departement ? ' - Dép: ' + commune.commune_departement : ''}
                </div>
            `;
            
            item.addEventListener('click', () => {
                selectCommune(commune);
            });
            
            resultsContainer.appendChild(item);
        });
        
        resultsContainer.classList.remove('hidden');
    }

    function selectCommune(commune) {
        communeSearch.value = commune.nom_commune + (commune.cp_commune ? ' (' + commune.cp_commune + ')' : '');
        communeIdInput.value = commune.id_commune;
        resultsContainer.classList.add('hidden');
        resultsContainer.innerHTML = '';
    }

    // Fermer les résultats en cliquant ailleurs
    document.addEventListener('click', function(e) {
        if (!communeSearch.contains(e.target) && !resultsContainer.contains(e.target)) {
            resultsContainer.classList.add('hidden');
        }
    });

    // Empêcher la soumission si aucune commune n'est sélectionnée
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!communeIdInput.value) {
            e.preventDefault();
            alert('Veuillez sélectionner une commune dans la liste');
            communeSearch.focus();
        }
    });
</script>

</body>
</html>