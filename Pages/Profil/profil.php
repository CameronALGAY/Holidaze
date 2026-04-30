<?php
session_start();
require_once '../../include/db.php';
require_once '../../include/csrf.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: /Pages/Formulaires/connexion.php');
    exit;
}

$message = "";
$message_type = "";

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_utilisateur = ?");
$stmt->execute([$_SESSION['utilisateur_id']]);
$utilisateur = $stmt->fetch();

// Préparer le chemin d'affichage de la photo
$photo_display = '';
if (!empty($utilisateur['photo_profil'])) {
    $photo_display = '/Photo/profil/' . $utilisateur['photo_profil'];
}

// Traitement des POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    // === Mise à jour du profil ===
    if (isset($_POST['update_profile'])) {
        $prenom = trim($_POST['prenom']);
        $nom = trim($_POST['nom']);
        $email = trim($_POST['email']);
        $tel = trim($_POST['tel']);
        
        $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateurs WHERE email = ? AND id_utilisateur != ?");
        $stmt->execute([$email, $_SESSION['utilisateur_id']]);
        
        if ($stmt->fetch()) {
            $message = "Cet email est déjà utilisé.";
            $message_type = "error";
        } else {
            $stmt = $pdo->prepare("UPDATE utilisateurs SET prenom = ?, nom = ?, email = ?, tel = ? WHERE id_utilisateur = ?");
            $stmt->execute([$prenom, $nom, $email, $tel, $_SESSION['utilisateur_id']]);
            
            $_SESSION['prenom'] = $prenom;
            $_SESSION['nom'] = $nom;
            $_SESSION['email'] = $email;
            
            $message = "Profil mis à jour !";
            $message_type = "success";
            
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_utilisateur = ?");
            $stmt->execute([$_SESSION['utilisateur_id']]);
            $utilisateur = $stmt->fetch();
        }
    }
    
    // === Upload de photo via base64 (recadrée) ===
    if (isset($_POST['update_photo']) && !empty($_POST['photo_profil'])) {
        $base64_string = $_POST['photo_profil'];
        
        // Extraire le type et le base64
        if (preg_match('/^data:image\/(\w+);base64,/', $base64_string, $type)) {
            $base64_string = substr($base64_string, strpos($base64_string, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif

            if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
                $message = "Format non autorisé.";
                $message_type = "error";
            } else {
                $data = base64_decode($base64_string);
                if ($data === false) {
                    $message = "Erreur de décodage.";
                    $message_type = "error";
                } else {
                    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/Photo/profil/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                    $type = 'png'; // Forcer PNG pour supporter la transparence du cercle
                    $filename = 'profil_' . $_SESSION['utilisateur_id'] . '_' . time() . '.' . $type;
                    $filepath_absolute = $upload_dir . $filename;
                    $filepath_relative = $filename;

                    // Supprimer l'ancienne photo
                    if (!empty($utilisateur['photo_profil'])) {
                        $old = $_SERVER['DOCUMENT_ROOT'] . '/Photo/profil/' . $utilisateur['photo_profil'];
                        if (file_exists($old)) unlink($old);
                    }

                    if (file_put_contents($filepath_absolute, $data)) {
                        $stmt = $pdo->prepare("UPDATE utilisateurs SET photo_profil = ? WHERE id_utilisateur = ?");
                        $stmt->execute([$filepath_relative, $_SESSION['utilisateur_id']]);

                        $_SESSION['photo_profil'] = '/Photo/profil/' . $filepath_relative;

                        $message = "Photo mise à jour avec succès !";
                        $message_type = "success";

                        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_utilisateur = ?");
                        $stmt->execute([$_SESSION['utilisateur_id']]);
                        $utilisateur = $stmt->fetch();
                        $photo_display = '/Photo/profil/' . $utilisateur['photo_profil'];
                    } else {
                        $message = "Erreur d'écriture du fichier.";
                        $message_type = "error";
                    }
                }
            }
        } else {
            $message = "Données image invalides.";
            $message_type = "error";
        }
    }
    
    // === Supprimer la photo ===
    if (isset($_POST['delete_photo'])) {
        if (!empty($utilisateur['photo_profil'])) {
            $photo_path = $_SERVER['DOCUMENT_ROOT'] . '/Photo/profil/' . $utilisateur['photo_profil'];
            if (file_exists($photo_path)) unlink($photo_path);
        }
        
        $stmt = $pdo->prepare("UPDATE utilisateurs SET photo_profil = NULL WHERE id_utilisateur = ?");
        $stmt->execute([$_SESSION['utilisateur_id']]);
        $_SESSION['photo_profil'] = null;
        
        $message = "Photo supprimée.";
        $message_type = "success";
        
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id_utilisateur = ?");
        $stmt->execute([$_SESSION['utilisateur_id']]);
        $utilisateur = $stmt->fetch();
        $photo_display = '';
    }
    
    // === Changer le mot de passe ===
    if (isset($_POST['change_password'])) {
        $ancien = $_POST['ancien_mot_de_passe'];
        $nouveau = $_POST['nouveau_mot_de_passe'];
        $confirmer = $_POST['confirmer_mot_de_passe'];
        
        if (!password_verify($ancien, $utilisateur['mot_de_passe'])) {
            $message = "Ancien mot de passe incorrect.";
            $message_type = "error";
        } elseif ($nouveau !== $confirmer) {
            $message = "Les mots de passe ne correspondent pas.";
            $message_type = "error";
        } elseif (strlen($nouveau) < 12) {
            $message = "Minimum 12 caractères.";
            $message_type = "error";
        } else {
            $hash = password_hash($nouveau, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE id_utilisateur = ?");
            $stmt->execute([$hash, $_SESSION['utilisateur_id']]);
            $message = "Mot de passe modifié !";
            $message_type = "success";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Holidaze</title>
    <meta name="description" content="Gerez votre profil Holidaze, votre photo et vos informations personnelles.">
    <meta name="robots" content="noindex, nofollow">
    <link rel="canonical" href="<?php
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        echo $scheme . '://' . $_SERVER['HTTP_HOST'] . '/Pages/Profil/profil.php';
    ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    
    <style>
        body { background-color: #f8f9fa; }
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 60px 0; margin-bottom: 30px;
        }
        .profile-avatar {
            width: 120px; height: 120px; background: white; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 48px; color: #667eea; margin: 0 auto 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); position: relative; overflow: hidden; cursor: pointer;
        }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .profile-avatar-edit {
            position: absolute; bottom: 0; left: 0; right: 0;
            background: rgba(0,0,0,0.7); color: white; padding: 8px;
            font-size: 12px; opacity: 0; transition: opacity 0.3s; text-align: center;
        }
        .profile-avatar:hover .profile-avatar-edit { opacity: 1; }
        .profile-card { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 30px; margin-bottom: 30px; }
        .profile-card h2 { color: #333; font-size: 24px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea; }
        .form-label { font-weight: 600; color: #555; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 12px 30px; }
        .btn-primary:hover { background: linear-gradient(135deg, #5568d3 0%, #633d8f 100%); transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .alert { border-radius: 10px; }
        .info-badge { background-color: #f0f0f0; padding: 10px 15px; border-radius: 8px; margin-bottom: 10px; }
        .photo-upload-section { text-align: center; padding: 20px; border: 2px dashed #ddd; border-radius: 10px; background-color: #f9f9f9; margin-bottom: 20px; }
        
        /* Styles pour rendre le crop circulaire */
        .cropper-view-box,
        .cropper-face {
            border-radius: 50% !important;
        }
        
        .cropper-view-box {
            box-shadow: 0 0 0 1px #39f;
            outline: 0;
        }
    </style>
</head>
<body>
    <?php include '../header.php'; ?>

    <div class="profile-header text-center">
        <div class="profile-avatar" data-bs-toggle="modal" data-bs-target="#photoModal">
            <?php if ($photo_display): ?>
                <img src="<?= htmlspecialchars($photo_display) ?>" alt="Photo de profil" loading="lazy" width="120" height="120">
            <?php else: ?>
                <?= strtoupper(substr($utilisateur['prenom'], 0, 1) . substr($utilisateur['nom'], 0, 1)) ?>
            <?php endif; ?>
            <div class="profile-avatar-edit">Modifier photo</div>
        </div>
        <h1 class="fs-2"><?= htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?></h1>
        <p class="lead"><?= htmlspecialchars($utilisateur['email']) ?></p>
    </div>

    <!-- Modal pour la photo -->
    <div class="modal fade" id="photoModal" tabindex="-1" aria-labelledby="photoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="photoModalLabel">Gestion de la photo de profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body d-flex flex-column align-items-center">
                    <div id="current_photo_section" class="mb-4 text-center">
                        <h6>Photo actuelle</h6>
                        <?php if ($photo_display): ?>
                            <img src="<?= htmlspecialchars($photo_display) ?>" alt="Photo actuelle" loading="lazy" width="200" height="200" class="rounded-circle mb-3" style="object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mb-3" style="width: 200px; height: 200px; font-size: 80px; color: #667eea;">
                                <?= strtoupper(substr($utilisateur['prenom'], 0, 1) . substr($utilisateur['nom'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="crop_container" class="w-100 mb-4" style="display: none; max-height: 400px; overflow: hidden;">
                        <img id="image_crop" src="" alt="Image a recadrer" loading="lazy" style="width: 100%;">
                    </div>

                    <div id="crop_controls" class="d-flex gap-2 mb-4" style="display: none;">
                        <button class="btn btn-outline-secondary" onclick="rotateImage(-90)"><i class="bi bi-arrow-counterclockwise"></i> -90°</button>
                        <button class="btn btn-outline-secondary" onclick="rotateImage(90)"><i class="bi bi-arrow-clockwise"></i> +90°</button>
                        <button class="btn btn-outline-secondary" onclick="resetCrop()"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
                    </div>

                    <div id="default_buttons" class="d-flex gap-3">
                        <label class="btn btn-primary">
                            <i class="bi bi-upload"></i> Choisir une photo
                            <input type="file" id="photo_input" accept="image/*" style="display: none;">
                        </label>
                        <?php if ($photo_display): ?>
                            <form method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="delete_photo" value="1">
                                <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash"></i> Supprimer</button>
                            </form>
                        <?php endif; ?>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>

                    <form id="crop_form" method="POST" style="display: none; width: 100%;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="update_photo" value="1">
                        <input type="hidden" id="cropped_image_data" name="photo_profil">
                        <div class="w-100 d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-danger" onclick="cancelCrop()">
                                Annuler
                            </button>
                            <button type="button" id="crop_and_upload" class="btn btn-primary">
                                Enregistrer la photo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="profile-card">
                    <h2>Informations</h2>
                    <div class="info-badge"><strong>Membre depuis:</strong><br><?= date('d/m/Y', strtotime($utilisateur['date_creation'])) ?></div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="profile-card">
                    <h2>Modifier mon profil</h2>
                    <form method="POST">
                        <?= csrf_field() ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Prénom</label>
                                <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($utilisateur['prenom']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nom</label>
                                <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($utilisateur['nom']) ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($utilisateur['email']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" name="tel" class="form-control" value="<?= htmlspecialchars($utilisateur['tel'] ?? '') ?>" placeholder="Optionnel">
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            Enregistrer
                        </button>
                    </form>
                </div>

                <div class="profile-card">
                    <h2>Mot de passe</h2>
                    <form method="POST">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">Ancien</label>
                            <input type="password" name="ancien_mot_de_passe" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nouveau (min. 12 car.)</label>
                            <input type="password" name="nouveau_mot_de_passe" class="form-control" minlength="12" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirmer</label>
                            <input type="password" name="confirmer_mot_de_passe" class="form-control" minlength="12" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">
                            Changer
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Cropper.js + JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        let cropper = null;

        document.getElementById('photo_input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) { alert('Max 5 Mo'); return; }
            if (!file.type.match('image.*')) { alert('Image invalide'); return; }

            const reader = new FileReader();
            reader.onload = function(event) {
                const img = document.getElementById('image_crop');
                const container = document.getElementById('crop_container');
                const current = document.getElementById('current_photo_section');
                const defaultBtns = document.getElementById('default_buttons');
                const cropForm = document.getElementById('crop_form');
                const cropControls = document.getElementById('crop_controls');

                img.src = event.target.result;
                
                // CORRECTION : utiliser style.display au lieu de classList
                container.style.display = 'block';
                cropControls.style.display = 'flex';
                current.style.display = 'none';
                defaultBtns.style.display = 'none';
                cropForm.style.display = 'block';

                if (cropper) cropper.destroy();

                cropper = new Cropper(img, {
                    aspectRatio: 1,
                    viewMode: 1,
                    autoCropArea: 0.7,
                    responsive: true,
                    cropBoxMovable: true,
                    cropBoxResizable: true,
                    dragMode: 'move',
                    minCropBoxWidth: 100,
                    minCropBoxHeight: 100,
                    ready: function () {
                        const containerData = cropper.getContainerData();
                        const imageData = cropper.getImageData();
                        const ratio = Math.min(
                            containerData.width / imageData.naturalWidth,
                            containerData.height / imageData.naturalHeight
                        );
                        cropper.zoomTo(ratio * 0.9);
                        
                        // Rendre la zone de crop circulaire
                        const cropBox = document.querySelector('.cropper-view-box');
                        const face = document.querySelector('.cropper-face');
                        if (cropBox) {
                            cropBox.style.borderRadius = '50%';
                        }
                        if (face) {
                            face.style.borderRadius = '50%';
                        }
                    }
                });
            };
            reader.readAsDataURL(file);
        });

        function rotateImage(deg) { 
            if (cropper) cropper.rotate(deg); 
        }
        
        function resetCrop() { 
            if (cropper) cropper.reset(); 
        }

        function cancelCrop() {
            if (cropper) { 
                cropper.destroy(); 
                cropper = null; 
            }
            // CORRECTION : utiliser style.display
            document.getElementById('crop_container').style.display = 'none';
            document.getElementById('crop_controls').style.display = 'none';
            document.getElementById('current_photo_section').style.display = 'block';
            document.getElementById('default_buttons').style.display = 'flex';
            document.getElementById('crop_form').style.display = 'none';
            document.getElementById('photo_input').value = '';
        }

        function cropAndUpload() {
            if (!cropper) return;
            const croppedCanvas = cropper.getCroppedCanvas({
                width: 400,
                height: 400,
                imageSmoothingQuality: 'high'
            });

            // Créer un canvas circulaire
            const circleCanvas = document.createElement('canvas');
            circleCanvas.width = 400;
            circleCanvas.height = 400;
            const ctx = circleCanvas.getContext('2d');

            // Clip en cercle
            ctx.beginPath();
            ctx.arc(200, 200, 200, 0, Math.PI * 2, true);
            ctx.clip();

            // Dessiner l'image cropped dans le cercle
            ctx.drawImage(croppedCanvas, 0, 0, 400, 400);

            circleCanvas.toBlob(function(blob) {
                const reader = new FileReader();
                reader.onloadend = function() {
                    document.getElementById('cropped_image_data').value = reader.result;
                    const btn = document.getElementById('crop_and_upload');
                    btn.innerHTML = 'Envoi...';
                    btn.disabled = true;
                    document.getElementById('crop_form').submit();
                };
                reader.readAsDataURL(blob);
            }, 'image/png', 0.95); // PNG pour transparence
        }

        document.getElementById('photoModal').addEventListener('hidden.bs.modal', cancelCrop);
        document.getElementById('crop_and_upload').addEventListener('click', cropAndUpload);
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" defer></script>
    <?php include '../footer.php'; ?>
</body>
</html>