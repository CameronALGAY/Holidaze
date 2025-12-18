<?php
session_start();
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: /Pages/Formulaires/connexion.php');
    exit;
}

require_once '../../include/db.php';

$message_envoye = false;
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sujet = trim($_POST['sujet'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($sujet) || empty($message)) {
        $erreur = 'Veuillez remplir tous les champs.';
    } elseif (strlen($message) < 10) {
        $erreur = 'Votre message doit faire au moins 10 caractères.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO messages_contact (id_utilisateur, sujet, message, date_envoi) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$_SESSION['utilisateur_id'], $sujet, $message]);
            $message_envoye = true;
        } catch (Exception $e) {
            $erreur = 'Erreur lors de l\'envoi du message. Réessayez plus tard.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Holidaze</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include '../header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="bi bi-envelope"></i> Contacter l'administration</h3>
                </div>
                <div class="card-body p-5">
                    <?php if ($message_envoye): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.
                        </div>
                        <a href="/Pages/index.php" class="btn btn-primary">Retour à l'accueil</a>
                    <?php else: ?>
                        <?php if ($erreur): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
                        <?php endif; ?>

                        <p class="text-muted mb-4">
                            Besoin d'aide ? Une question ? Un problème technique ? Écrivez-nous, l'équipe Holidaze vous répondra rapidement.
                        </p>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="sujet" class="form-label fw-bold">Sujet</label>
                                <input type="text" class="form-control" id="sujet" name="sujet" required maxlength="100"
                                       value="<?= htmlspecialchars($_POST['sujet'] ?? '') ?>">
                            </div>

                            <div class="mb-4">
                                <label for="message" class="form-label fw-bold">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="8" required minlength="10"
                                          placeholder="Décrivez votre demande..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-send"></i> Envoyer le message
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
</body>
</html>