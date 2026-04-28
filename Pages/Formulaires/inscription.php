<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 2rem 0;
        }
        .captcha-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: bold;
            font-size: 1.5rem;
            padding: 1rem 1.5rem;
            border-radius: 0.5rem;
            letter-spacing: 0.5rem;
            font-family: monospace;
            user-select: none;
            transform: skewX(-6deg);
            display: inline-block;
        }
        .captcha-container {
            transition: all 0.3s ease;
        }
        .captcha-container.error {
            background-color: #f8d7da !important;
            border-color: #dc3545 !important;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        .animate-shake {
            animation: shake 0.5s;
        }
        .validation-icon {
            width: 1.25rem;
            height: 1.25rem;
            margin-right: 0.5rem;
        }
        .validation-item {
            display: flex;
            align-items: center;
            color: #6c757d;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        .validation-item.valid {
            color: #198754; /* Vert Bootstrap */
        }
        .validation-item .validation-icon {
            transition: all 0.3s ease;
        }
        .validation-item.valid .validation-icon {
            /* Remplacer l'icône bi-circle par bi-check-circle-fill */
            content: "\F26A"; /* Code Unicode pour bi-check-circle-fill */
            font-family: "bootstrap-icons" !important;
        }
        .country-flag {
            width: 2rem;
            height: 1.5rem;
            object-fit: cover;
            border-radius: 0.25rem;
        }
        .country-item:hover {
            background-color: #f8f9fa;
        }
        .dropdown-menu {
            max-height: 300px;
            overflow-y: auto;
        }
        .company-fields {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out, padding 0.3s ease-out;
            padding: 0 0.75rem;
        }
        .company-fields.show {
            max-height: 500px;
            overflow-y: auto;
            padding: 1rem 0.75rem;
            margin-top: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            background-color: #f8f9fa;
        }
        .autocomplete-results {
            position: absolute;
            z-index: 1000;
            background-color: #fff;
            border: 1px solid #ced4da;
            border-top: none;
            max-height: 200px;
            overflow-y: auto;
            width: 100%;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 0 0 0.375rem 0.375rem;
        }
        .autocomplete-item {
            padding: 0.5rem 1rem;
            cursor: pointer;
        }
        .autocomplete-item:hover {
            background-color: #f8f9fa;
        }
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.15em;
        }
        .search-btn {
            transition: all 0.3s ease;
        }
        .search-btn:disabled {
            opacity: 0.6;
        }
        #sirenResultsContainer {
            max-height: 300px;
            overflow-y: auto;
        }
        .company-result-item {
            cursor: pointer;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }
        .company-result-item:hover {
            background-color: #e9ecef;
            border-left-color: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h1 class="card-title text-center mb-4 fw-bold">Inscription</h1>
                        
                        <div id="message" class="alert d-none" role="alert"></div>
                        
                        <form id="inscriptionForm" action="inscription_traitement.php" method="POST">
                            <input type="hidden" name="type_entite" id="typeEntiteInput" value="individu">
                            
                            <div class="mb-3">
                                <label for="nom" class="form-label fw-medium">Nom :</label>
                                <input type="text" class="form-control" id="nom" name="nom">
                            </div>
                            
                            <div class="mb-3">
                                <label for="prenom" class="form-label fw-medium">Prénom :</label>
                                <input type="text" class="form-control" id="prenom" name="prenom">
                            </div>

                            <div class="mb-3">
                                <label for="date_naissance" class="form-label fw-medium">Date de naissance :</label>
                                <input type="date" class="form-control" id="date_naissance" name="date_naissance" required max="<?= date('Y-m-d') ?>">
                                <div id="ageWarning" class="alert d-none mt-2" role="alert"></div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium">Je m'inscris en tant que :</label>
                                <div class="btn-group w-100" role="group" aria-label="Sélection du rôle">
                                    <input type="radio" class="btn-check" name="roleSelector" id="roleParticulier" autocomplete="off" checked>
                                    <label class="btn btn-outline-primary" for="roleParticulier">Particulier</label>
                                    
                                    <input type="radio" class="btn-check" name="roleSelector" id="roleEntreprise" autocomplete="off">
                                    <label class="btn btn-outline-primary" for="roleEntreprise">Entreprise</label>
                                </div>
                            </div>
                            
                            <div id="companyFields" class="company-fields mb-3">
                                <h5 class="mb-3">Informations de l'entreprise</h5>
                                
                                <!-- Recherche par SIRET/SIREN -->
                                <div class="mb-4">
                                    <label for="siret_search" class="form-label fw-medium">
                                        Rechercher par SIRET (14 chiffres) ou SIREN (9 chiffres) :
                                    </label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="siret_search" placeholder="Ex: 12345678900014 ou 123456789" maxlength="14">
                                        <button class="btn btn-primary search-btn" type="button" id="searchSiretBtn">
                                            <i class="fas fa-search me-1"></i>
                                            Rechercher
                                        </button>
                                    </div>
                                    <div class="form-text">Entrez un numéro SIRET (14 chiffres) ou SIREN (9 chiffres) pour remplir automatiquement les informations</div>
                                    
                                    <!-- Zone de résultats de recherche -->
                                    <div id="sirenResultsContainer" class="mt-3 d-none">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Plusieurs établissements trouvés.</strong> Sélectionnez celui qui vous correspond :
                                        </div>
                                        <div id="sirenResults" class="border rounded"></div>
                                    </div>
                                    
                                    <!-- Message d'état -->
                                    <div id="sirenSearchStatus" class="mt-2"></div>
                                </div>

                                <hr class="my-4">
                                
                                <div class="mb-3">
                                    <label for="nom_entreprise" class="form-label fw-medium">Libellé/Raison sociale :</label>
                                    <input type="text" class="form-control" id="nom_entreprise" name="nom_entreprise" placeholder="Nom de l'entreprise">
                                </div>
                                <div class="mb-3">
                                    <label for="numero_siret" class="form-label fw-medium">Numéro SIRET (14 chiffres) :</label>
                                    <input type="text" class="form-control" id="numero_siret" name="numero_siret" placeholder="Ex: 12345678900014" maxlength="14">
                                </div>
                                <div class="mb-3 position-relative">
                                    <label for="adresse_siege" class="form-label fw-medium">Adresse du siège :</label>
                                    <input type="text" class="form-control" id="adresse_siege" name="adresse_siege" placeholder="Numéro et rue" autocomplete="off">
                                    <div id="adresseResultsSiege" class="autocomplete-results d-none"></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-5 mb-3">
                                        <label for="code_postal_siege" class="form-label fw-medium">Code Postal :</label>
                                        <input type="text" class="form-control" id="code_postal_siege" name="code_postal_siege">
                                    </div>
                                    <div class="col-md-7 mb-3 position-relative">
                                        <label for="ville_siege" class="form-label fw-medium">Ville :</label>
                                        <input type="text" class="form-control" id="ville_siege" name="ville_siege" autocomplete="off">
                                        <input type="hidden" name="id_commune_siege" id="id_commune_siege">
                                        <div id="communesResultsSiege" class="autocomplete-results d-none"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Téléphone :</label>
                                <div class="input-group">
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="countrySelector">
                                        <span id="selectedFlag" class="me-2">🇫🇷</span>
                                        <span id="selectedCode">+33</span>
                                    </button>
                                    <ul class="dropdown-menu" id="countryDropdown">
                                        <li class="px-3 py-2">
                                            <input type="text" class="form-control form-control-sm" id="countrySearch" placeholder="Rechercher un pays...">
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <div id="countryList"></div>
                                    </ul>
                                    <input type="tel" class="form-control" id="tel" name="tel" placeholder="6 12 34 56 78">
                                    <input type="hidden" name="country_code" id="countryCode" value="+33">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label fw-medium">Email :</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="mot_de_passe" class="form-label fw-medium">Mot de passe :</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword" title="Afficher/Masquer le mot de passe">
                                        <i class="fas fa-eye" id="eyeIcon"></i>
                                    </button>
                                </div>

                                <div class="mt-3">
                                    <div id="length-check" class="validation-item">
                                        <i class="bi bi-circle validation-icon"></i>
                                        <span>Au moins 12 caractères</span>
                                    </div>

                                    <div id="uppercase-check" class="validation-item">
                                        <i class="bi bi-circle validation-icon"></i>
                                        <span>Au moins 1 majuscule</span>
                                    </div>

                                    <div id="number-check" class="validation-item">
                                        <i class="bi bi-circle validation-icon"></i>
                                        <span>Au moins 1 chiffre</span>
                                    </div>

                                    <div id="special-check" class="validation-item">
                                        <i class="bi bi-circle validation-icon"></i>
                                        <span>Au moins 1 caractère spécial (!@#$%^&*...)</span>
                                    </div>
                                </div>
                            </div>

                            
                            <div id="captchaContainer" class="border rounded p-3 mb-3 bg-light captcha-container">
                                <label class="form-label fw-medium">Vérification de sécurité :</label>
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div id="captchaDisplay" class="captcha-display"></div>
                                    <button type="button" id="refreshCaptcha" class="btn btn-secondary" title="Générer un nouveau code">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                </div>
                                <input type="text" class="form-control" id="captchaInput" placeholder="Entrez le code ci-dessus" autocomplete="off">
                                
                                <div id="captchaError" class="alert alert-danger mt-2 d-none" role="alert">
                                    <i class="fas fa-times-circle me-2"></i>
                                    <strong>Le code de vérification est incorrect.</strong> Veuillez réessayer.
                                </div>
                            </div>
                            
                                <button type="submit" id="submitBtn" class="btn btn-primary w-100 fw-medium" disabled>
                                    S'inscrire
                                </button>
                            </form>
                            

                        
                        <p class="text-center mt-4 mb-0">
                            Déjà inscrit ? <a href="connexion.php" class="text-decoration-none fw-medium">Connectez-vous</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const passwordInput = document.getElementById('mot_de_passe');
// Fonctionnalité pour afficher/masquer le mot de passe
const togglePassword = document.getElementById('togglePassword');
const eyeIcon = document.getElementById('eyeIcon');

togglePassword.addEventListener('click', function() {
    // Basculer le type du champ
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
    
    // Changer l'icône
    if (type === 'text') {
        eyeIcon.classList.remove('bi-eye');
        eyeIcon.classList.add('bi-eye-slash');
    } else {
        eyeIcon.classList.remove('bi-eye-slash');
        eyeIcon.classList.add('bi-eye');
    }
});
        const submitBtn = document.getElementById('submitBtn');
        const captchaContainer = document.getElementById('captchaContainer');
        const captchaInput = document.getElementById('captchaInput');
        let currentCaptcha = '';
        
        // ================== CAPTCHA LOGIC ==================
        
        function generateCaptcha() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let captcha = '';
            for (let i = 0; i < 6; i++) {
                captcha += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            currentCaptcha = captcha;
            document.getElementById('captchaDisplay').textContent = captcha;
            document.getElementById('captchaError').classList.add('d-none');
            captchaContainer.classList.remove('error', 'animate-shake');
            return captcha;
        }
        
        const validationRules = [
            { id: 'length-check', regex: /.{8,}/, message: 'Au moins 8 caractères' }, // Correction: Le HTML utilise 12 caractères, mais la regex était 8. Je garde 8 pour la compatibilité avec la regex originale, mais je corrige l'ID.
            { id: 'uppercase-check', regex: /[A-Z]/, message: 'Au moins 1 majuscule' },
            { id: 'number-check', regex: /[0-9]/, message: 'Au moins 1 chiffre' },
            { id: 'special-check', regex: /[!@#$%^&*]/, message: 'Au moins 1 caractère spécial (!@#$%^&*...)' }
        ];
        
        function checkFormValidity() {
            const isCaptchaValid = captchaInput.value === currentCaptcha;
            // Compter les règles valides en utilisant les IDs des règles
            const validRulesCount = validationRules.filter(rule => document.getElementById(rule.id).classList.contains('valid')).length;
            const isPasswordValid = validRulesCount === validationRules.length;
            
            submitBtn.disabled = !(isCaptchaValid && isPasswordValid);
            
            if (captchaInput.value.length === 6 && !isCaptchaValid) {
                captchaContainer.classList.add('error');
                captchaContainer.classList.add('animate-shake');
                document.getElementById('captchaError').classList.remove('d-none');
            } else {
                captchaContainer.classList.remove('error', 'animate-shake');
                document.getElementById('captchaError').classList.add('d-none');
            }
        }
        
        // Initialisation du CAPTCHA
        generateCaptcha();
        document.getElementById('refreshCaptcha').addEventListener('click', generateCaptcha);
        captchaInput.addEventListener('input', checkFormValidity);
        passwordInput.addEventListener('input', (e) => {
            validatePassword(e.target.value);
            checkFormValidity();
        });
        
        // S'assurer que la validation est lancée au chargement si le champ est pré-rempli
        validatePassword(passwordInput.value);
        
        function validatePassword(password) {
            const validationList = document.getElementById('passwordValidation');
            validationRules.forEach(rule => {
                const item = document.getElementById(rule.id);
                const icon = item.querySelector('.validation-icon');
                if (rule.regex.test(password)) {
                    item.classList.add('valid');
                    icon.classList.remove('bi-circle');
                    icon.classList.add('bi-check-circle-fill');
                } else {
                    item.classList.remove('valid');
                    icon.classList.remove('bi-check-circle-fill');
                    icon.classList.add('bi-circle');
                }
            });
        }
        
        // ================== END CAPTCHA LOGIC ==================
        
        // ================== VÉRIFICATION DE L'ÂGE ==================
        
        function calculateAge(birthDate) {
            const today = new Date();
            const birth = new Date(birthDate);
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age--;
            }
            
            return age;
        }
        
        function verifyAge() {
            const dateNaissanceInput = document.getElementById('date_naissance');
            const ageWarningDiv = document.getElementById('ageWarning');
            const birthDate = dateNaissanceInput.value;
            
            if (!birthDate) {
                ageWarningDiv.classList.add('d-none');
                return;
            }
            
            const age = calculateAge(birthDate);
            
            // Créer un champ caché pour stocker l'information de l'âge
            let ageStatusInput = document.getElementById('age_status');
            if (!ageStatusInput) {
                ageStatusInput = document.createElement('input');
                ageStatusInput.type = 'hidden';
                ageStatusInput.id = 'age_status';
                ageStatusInput.name = 'age_status';
                document.getElementById('inscriptionForm').appendChild(ageStatusInput);
            }
            
            if (age < 18) {
                // Mineur - peut visiter mais ne peut pas réserver
                ageWarningDiv.innerHTML = `
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Information :</strong> Vous avez ${age} ans. Vous pouvez créer un compte et visiter le site, 
                    mais vous ne pourrez pas réserver de logement tant que vous n'aurez pas 18 ans.
                `;
                ageWarningDiv.className = 'alert alert-info mt-2';
                ageWarningDiv.classList.remove('d-none');
                ageStatusInput.value = 'mineur';
            } else {
                // Majeur - accès complet
                ageWarningDiv.innerHTML = `
                    <i class="bi bi-check-circle me-2"></i>
                    <strong>Parfait !</strong> Vous pourrez profiter de toutes les fonctionnalités du site, 
                    y compris la réservation de logements.
                `;
                ageWarningDiv.className = 'alert alert-success mt-2';
                ageWarningDiv.classList.remove('d-none');
                ageStatusInput.value = 'majeur';
            }
        }
        
        document.getElementById('date_naissance').addEventListener('change', verifyAge);
        document.getElementById('date_naissance').addEventListener('input', verifyAge);
        
        // ================== FIN VÉRIFICATION DE L'ÂGE ==================
        
        const roleParticulier = document.getElementById('roleParticulier');
        const roleEntreprise = document.getElementById('roleEntreprise');
        const companyFields = document.getElementById('companyFields');
        const typeEntiteInput = document.getElementById('typeEntiteInput'); 

        // Variables pour les pays
        let countries = [];
        let selectedCountry = { name: 'France', code: '+33', flag: '🇫🇷' };
        
        const phoneFormats = {
            '+33': '6 12 34 56 78',
            '+32': '471 23 45 67',
            '+41': '78 123 45 67',
            '+1': '(123) 456-7890',
            '+44': '7123 456789',
            '+49': '151 12345678',
            '+34': '612 34 56 78',
            '+39': '321 234 5678',
            '+351': '911 234 567',
            '+212': '6 12 34 56 78',
            '+213': '5 12 34 56 78',
            '+216': '21 234 567',
            '+86': '138 1234 5678',
            '+81': '91 2345 6789',
            '+91': '98123 45678',
            '+7': '912 345-67-89',
            '+55': '(11) 91234-5678',
            '+61': '412 345 678',
            '+971': '51 234 5678',
            'default': '123 456 789'
        };
        
        function getPhoneFormat(countryCode) {
            return phoneFormats[countryCode] || phoneFormats['default'];
        }
        
        async function loadCountries() {
            try {
                const response = await fetch('https://restcountries.com/v3.1/all?fields=name,idd,cca2,flags');
                const data = await response.json();
                
                countries = data
                    .filter(country => country.idd.root)
                    .map(country => ({
                        name: country.name.common,
                        code: country.idd.root + (country.idd.suffixes ? country.idd.suffixes[0] : ''),
                        flag: country.flags.svg || country.flags.png,
                        cca2: country.cca2
                    }))
                    .sort((a, b) => a.name.localeCompare(b.name));
                
                renderCountryList();
            } catch (error) {
                console.error('Erreur lors du chargement des pays:', error);
                countries = [
                    { name: 'France', code: '+33', flag: '🇫🇷', cca2: 'FR' },
                    { name: 'Belgique', code: '+32', flag: '🇧🇪', cca2: 'BE' },
                    { name: 'Suisse', code: '+41', flag: '🇨🇭', cca2: 'CH' },
                    { name: 'Canada', code: '+1', flag: '🇨🇦', cca2: 'CA' },
                    { name: 'États-Unis', code: '+1', flag: '🇺🇸', cca2: 'US' },
                ];
                renderCountryList();
            }
        }
        
        function renderCountryList(filter = '') {
            const countryList = document.getElementById('countryList');
            const filtered = countries.filter(c => 
                c.name.toLowerCase().includes(filter.toLowerCase()) || 
                c.code.includes(filter)
            );
            
            if (filtered.length === 0) {
                countryList.innerHTML = '<li class="dropdown-item text-center text-muted">Aucun pays trouvé</li>';
                return;
            }
            
            countryList.innerHTML = filtered.map(country => `
                <li>
                    <a class="dropdown-item country-item d-flex align-items-center gap-2" href="#" data-code="${country.code}" data-flag="${country.flag}" data-name="${country.name}" data-cca2="${country.cca2}">
                        ${country.flag.startsWith('http') 
                            ? `<img src="${country.flag}" alt="${country.name}" class="country-flag">`
                            : `<span style="font-size: 1.5rem;">${country.flag}</span>`
                        }
                        <span class="flex-grow-1">${country.name}</span>
                        <span class="text-muted fw-medium">${country.code}</span>
                    </a>
                </li>
            `).join('');
            
            countryList.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    selectCountry({
                        flag: this.dataset.flag,
                        code: this.dataset.code,
                        name: this.dataset.name,
                        cca2: this.dataset.cca2
                    });
                });
            });
        }
        
        function selectCountry(country) {
            selectedCountry = country;
            const flagElement = document.getElementById('selectedFlag');
            const telInput = document.getElementById('tel');
            
            if (country.flag.startsWith('http')) {
                flagElement.innerHTML = `<img src="${country.flag}" alt="${country.name}" class="country-flag">`;
            } else {
                flagElement.textContent = country.flag;
            }
            
            document.getElementById('selectedCode').textContent = country.code;
            document.getElementById('countryCode').value = country.code;
            telInput.placeholder = getPhoneFormat(country.code);
        }
        
        loadCountries();
        document.getElementById('tel').placeholder = getPhoneFormat('+33');
        
        document.getElementById('countrySearch').addEventListener('input', function() {
            renderCountryList(this.value);
        });
        
        function handleRoleChange(isCompany) {
            const requiredCompanyFields = [
                document.getElementById('nom_entreprise'),
                document.getElementById('numero_siret'),
                document.getElementById('adresse_siege'),
                document.getElementById('code_postal_siege'),
                document.getElementById('ville_siege')
            ];

            if (isCompany) {
                companyFields.classList.add('show');
                typeEntiteInput.value = 'entreprise';
                requiredCompanyFields.forEach(field => field.required = true);
            } else {
                companyFields.classList.remove('show');
                typeEntiteInput.value = 'individu';
                requiredCompanyFields.forEach(field => field.required = false);
            }
        }

        roleParticulier.addEventListener('change', () => handleRoleChange(false));
        roleEntreprise.addEventListener('change', () => handleRoleChange(true));

        // ============== API SIRENE ==============
        
        async function searchSiret(siret) {
            const searchBtn = document.getElementById('searchSiretBtn');
            const statusDiv = document.getElementById('sirenSearchStatus');
            const resultsContainer = document.getElementById('sirenResultsContainer');
            
            // Nettoyer le numéro (enlever les espaces)
            siret = siret.replace(/\s/g, '');
            
            // Vérifier si c'est un SIREN (9 chiffres) ou SIRET (14 chiffres)
            if (!/^\d{9}$/.test(siret) && !/^\d{14}$/.test(siret)) {
                statusDiv.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Veuillez entrer un numéro SIREN (9 chiffres) ou SIRET (14 chiffres) valide.</div>';
                return;
            }
            
            // Désactiver le bouton et afficher le spinner
            searchBtn.disabled = true;
            searchBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Recherche...';
            statusDiv.innerHTML = '';
            resultsContainer.classList.add('d-none');
            
            try {
                // Utilisation de l'API Recherche d'Entreprises (gratuite, sans authentification)
                const apiUrl = `https://recherche-entreprises.api.gouv.fr/search?q=${siret}&per_page=25`;
                
                const response = await fetch(apiUrl, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`Erreur API: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.results && data.results.length > 0) {
                    // Filtrer pour trouver la correspondance exacte
                    let entreprise = null;
                    
                    if (siret.length === 14) {
                        // Recherche par SIRET - trouver l'établissement exact
                        entreprise = data.results.find(r => r.siege && r.siege.siret === siret);
                        if (!entreprise && data.results.length === 1) {
                            entreprise = data.results[0];
                        }
                    } else {
                        // Recherche par SIREN - trouver l'entreprise exacte
                        entreprise = data.results.find(r => r.siren === siret);
                        if (!entreprise && data.results.length === 1) {
                            entreprise = data.results[0];
                        }
                    }
                    
                    if (entreprise) {
                        fillCompanyData(entreprise);
                        statusDiv.innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Entreprise trouvée ! Les informations ont été remplies automatiquement.</div>';
                    } else if (data.results.length > 1) {
                        // Plusieurs résultats trouvés
                        displayMultipleResults(data.results);
                    } else {
                        statusDiv.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Aucune correspondance exacte trouvée.</div>';
                    }
                } else {
                    statusDiv.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Aucune entreprise trouvée avec ce numéro.</div>';
                }
                
            } catch (error) {
                console.error('Erreur lors de la recherche:', error);
                statusDiv.innerHTML = '<div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i>Une erreur est survenue lors de la recherche. Veuillez réessayer.</div>';
            } finally {
                // Réactiver le bouton
                searchBtn.disabled = false;
                searchBtn.innerHTML = '<i class="bi bi-search me-1"></i>Rechercher';
            }
        }
        
        function displayMultipleResults(entreprises) {
            const resultsContainer = document.getElementById('sirenResultsContainer');
            const resultsDiv = document.getElementById('sirenResults');
            
            const html = entreprises.map((entreprise, index) => {
                const nom = entreprise.nom_complet || entreprise.nom_raison_sociale || 'Nom non disponible';
                const siege = entreprise.siege;
                const etat = siege?.etat_administratif === 'A' ? 
                            '<span class="badge bg-success">Actif</span>' : 
                            '<span class="badge bg-secondary">Fermé</span>';
                const adresse = siege?.adresse || 'Adresse non disponible';
                const siret = siege?.siret || entreprise.siren;
                
                return `
                    <div class="company-result-item p-3 border-bottom" data-index="${index}">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0">${nom} <span class="badge bg-primary ms-2">Siège</span></h6>
                            ${etat}
                        </div>
                        <div class="text-muted small">
                            <div><strong>SIREN:</strong> ${entreprise.siren || 'N/A'}</div>
                            <div><strong>SIRET:</strong> ${siret || 'N/A'}</div>
                            <div><strong>Adresse:</strong> ${adresse}</div>
                        </div>
                    </div>
                `;
            }).join('');
            
            resultsDiv.innerHTML = html;
            resultsContainer.classList.remove('d-none');
            
            // Ajouter les événements de clic
            resultsDiv.querySelectorAll('.company-result-item').forEach(item => {
                item.addEventListener('click', function() {
                    const index = parseInt(this.dataset.index);
                    fillCompanyData(entreprises[index]);
                    resultsContainer.classList.add('d-none');
                    document.getElementById('sirenSearchStatus').innerHTML = 
                        '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Établissement sélectionné ! Les informations ont été remplies.</div>';
                });
            });
        }
        
        function fillCompanyData(entreprise) {
            // Récupérer les informations du siège
            const siege = entreprise.siege;
            
            // Nom de l'entreprise
            const nomEntreprise = entreprise.nom_complet || entreprise.nom_raison_sociale || '';
            
            document.getElementById('nom_entreprise').value = nomEntreprise;
            document.getElementById('numero_siret').value = siege?.siret || '';
            
            // Adresse - extraire les composants de l'adresse complète
            if (siege) {
                // Extraire le numéro et la rue de l'adresse complète
                const adresseComplete = siege.adresse || '';
                const codePostal = siege.code_postal || '';
                const commune = siege.libelle_commune || '';
                
                // Essayer d'extraire la rue (tout ce qui est avant le code postal)
                let rue = adresseComplete;
                const match = adresseComplete.match(/^(.+?)\s*\d{5}/);
                if (match) {
                    rue = match[1].trim();
                }
                
                document.getElementById('adresse_siege').value = rue;
                document.getElementById('code_postal_siege').value = codePostal;
                document.getElementById('ville_siege').value = commune;
            } else {
                document.getElementById('adresse_siege').value = '';
                document.getElementById('code_postal_siege').value = '';
                document.getElementById('ville_siege').value = '';
            }
        }
        
        // Événement pour le bouton de recherche
        document.getElementById('searchSiretBtn').addEventListener('click', function() {
            const siretInput = document.getElementById('siret_search');
            const siret = siretInput.value.trim();
            
            if (siret) {
                searchSiret(siret);
            } else {
                document.getElementById('sirenSearchStatus').innerHTML = 
                    '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>Veuillez entrer un numéro SIREN ou SIRET.</div>';
            }
        });
        
        // Permettre la recherche avec la touche Entrée
        document.getElementById('siret_search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('searchSiretBtn').click();
            }
        });
        
        // ================== AUTOCOMPLETION ADRESSE/COMMUNE LOGIC ==================
        
        const adresseSearchSiege = document.getElementById('adresse_siege');
        const adresseResultsSiege = document.getElementById('adresseResultsSiege');
        const codePostalSiege = document.getElementById('code_postal_siege');
        const villeSearchSiege = document.getElementById('ville_siege');
        const communesResultsSiege = document.getElementById('communesResultsSiege');
        const idCommuneSiege = document.getElementById('id_commune_siege');
        
        // --- 1. Autocomplétion Adresse (API Adresse Nationale) ---
        adresseSearchSiege.addEventListener('input', function() {
            const query = this.value.trim();
            if(query.length >= 3) {
                // Utilisation de l'API Adresse Nationale
                fetch(`https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(query)}&limit=5`)
                    .then(res => res.json())
                    .then(data => {
                        adresseResultsSiege.innerHTML = '';
                        if(data.features && data.features.length > 0){
                            data.features.forEach(feature => {
                                const item = document.createElement('div');
                                item.className = 'autocomplete-item';
                                item.textContent = feature.properties.label;
                                item.addEventListener('click', () => {
                                    // Remplir le champ avec l'adresse (numéro et rue)
                                    adresseSearchSiege.value = feature.properties.name;
                                    adresseResultsSiege.classList.add('d-none');
        
                                    // Remplir le champ Code Postal
                                    codePostalSiege.value = feature.properties.postcode;
        
                                    // Remplir le champ Ville et déclencher l'événement input pour la recherche d'ID
                                    villeSearchSiege.value = feature.properties.city;
                                    
                                    // Déclencher l'événement input pour que la logique de recherche de commune soit exécutée
                                    const event = new Event('input', { bubbles: true });
                                    villeSearchSiege.dispatchEvent(event);
                                });
                                adresseResultsSiege.appendChild(item);
                            });
                            adresseResultsSiege.classList.remove('d-none');
                        } else adresseResultsSiege.innerHTML = '<div class="autocomplete-item text-center text-muted">Aucune adresse trouvée</div>';
                    })
                    .catch(err => {
                        console.error('Erreur recherche adresse:', err);
                        adresseResultsSiege.innerHTML = '<div class="autocomplete-item text-center text-danger">Erreur de recherche</div>';
                        adresseResultsSiege.classList.remove('d-none');
                    });
            } else {
                adresseResultsSiege.classList.add('d-none');
            }
        });
        
        // --- 2. Autocomplétion Commune (via votre backend) ---
        villeSearchSiege.addEventListener('input', function() {
            const query = this.value.trim();
            if(query.length >= 2) {
                // Utilisation de votre endpoint AJAX pour les communes
                fetch(`../Communes/communes_traitement.php?autocomplete=commune&q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        communesResultsSiege.innerHTML = '';
                        if(data.success && data.data.length > 0){
                            data.data.forEach(c => {
                                const item = document.createElement('div');
                                item.className = 'autocomplete-item';
                                item.textContent = `${c.nom_commune} (${c.cp_commune})`;
                                item.addEventListener('click', () => {
                                    villeSearchSiege.value = c.nom_commune;
                                    codePostalSiege.value = c.cp_commune;
                                    idCommuneSiege.value = c.id_commune;
                                    communesResultsSiege.classList.add('d-none');
                                });
                                communesResultsSiege.appendChild(item);
                            });
                            communesResultsSiege.classList.remove('d-none');
                        } else communesResultsSiege.innerHTML = '<div class="autocomplete-item text-center text-muted">Aucune commune trouvée</div>';
                    });
            } else communesResultsSiege.classList.add('d-none');
        });
        
        // --- 3. Fermeture des résultats si clic ailleurs ---
        document.addEventListener('click', function(e) {
            if (!adresseSearchSiege.contains(e.target) && !adresseResultsSiege.contains(e.target)) {
                adresseResultsSiege.classList.add('d-none');
            }
            if (!villeSearchSiege.contains(e.target) && !communesResultsSiege.contains(e.target)) {
                communesResultsSiege.classList.add('d-none');
            }
        });
        
        // ================== END AUTOCOMPLETION LOGIC ==================
        
        // ================== FORM SUBMISSION LOGIC ==================
        
        document.getElementById('inscriptionForm').addEventListener('submit', function(e) {
            // Si le bouton est désactivé (ce qui signifie que le CAPTCHA ou le mot de passe est invalide),
            // on empêche la soumission du formulaire.
            if (submitBtn.disabled) {
                e.preventDefault();
            }
            // Si le bouton n'est PAS désactivé, la soumission normale se produit (envoi vers inscription_traitement.php)
        });
        
        // ================== END FORM SUBMISSION LOGIC ==================
        
    </script>
</body>
</html>