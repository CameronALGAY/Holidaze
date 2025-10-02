<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajout Locataire</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
        }
        .form-container {
            width: 600px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background: #f9f9f9;
        }
        .form-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #aaa;
            border-radius: 5px;
        }
        .hidden {
            display: none;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            background: #3498db;
            color: white;
            cursor: pointer;
        }
        .btn:hover {
            background: #2980b9;
        }
    </style>
    <script>
        function toggleEntrepriseFields() {
            let entrepriseFields = document.getElementById("entrepriseFields");
            let checkbox = document.getElementById("isEntreprise");

            if (checkbox.checked) {
                entrepriseFields.classList.remove("hidden");
            } else {
                entrepriseFields.classList.add("hidden");
            }
        }
    </script>
</head>
<body>

<div class="form-container">
    <h2>Ajouter un Locataire</h2>
    <form action="traitement_locataire.php" method="POST">
        
        <div class="form-group">
            <label for="nom">Nom :</label>
            <input type="text" name="nom_locataire" id="nom" required>
        </div>

        <div class="form-group">
            <label for="prenom">Prénom :</label>
            <input type="text" name="prenom_locataire" id="prenom" required>
        </div>

        <div class="form-group">
            <label for="dna">Date de naissance :</label>
            <input type="date" name="dna_locataire" id="dna" required>
        </div>

        <div class="form-group">
            <label for="email">Email :</label>
            <input type="email" name="email_locataire" id="email" required>
        </div>

        <div class="form-group">
            <label for="rue">Rue :</label>
            <input type="text" name="rue_locataire" id="rue" required>
        </div>

        <div class="form-group">
            <label for="tel">Téléphone :</label>
            <input type="text" name="tel_locataire" id="tel" required>
        </div>

        <div class="form-group">
            <label for="comp">Complément d'adresse :</label>
            <input type="text" name="comp_locataire" id="comp">
        </div>

        <div class="form-group">
            <label for="commune">Commune :</label>
            <select name="id_commune" id="commune" required>
                <option value="">-- Sélectionnez une commune --</option>
                <?php
                //Exemple de remplissage avec la table commune
                 require_once '../db.php';
                $req = $pdo->query("SELECT id_commune, ville FROM commune");
                 while($row = $req->fetch()) {
                     echo "<option value='{$row['id_commune']}'>{$row['ville']}</option>";
                 }
                ?>
            </select>
        </div>

        <div class="form-group">
            <label for="pass">Mot de passe :</label>
            <input type="password" name="pass_locataire" id="pass" required>
        </div>

        <!-- Checkbox Entreprise -->
        <div class="form-group">
            <input type="checkbox" id="isEntreprise" name="isEntreprise" value="1" onclick="toggleEntrepriseFields()">
            <label for="isEntreprise">C'est une entreprise</label>
        </div>

        <!-- Champs entreprise (cachés par défaut) -->
        <div id="entrepriseFields" class="hidden">
            <div class="form-group">
                <label for="raison_social">Raison sociale :</label>
                <input type="text" name="raison_social" id="raison_social">
            </div>

            <div class="form-group">
                <label for="siret">SIRET :</label>
                <input type="text" name="siret" id="siret">
            </div>
        </div>

        <div style="text-align:center;">
            <button type="submit" class="btn">Enregistrer</button>
        </div>
    </form>
</div>

</body>
</html>
