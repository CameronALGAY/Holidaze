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
    $contenu = trim($_POST['message'] ?? '');
    
    if (empty($sujet) || empty($contenu)) {
        $erreur = 'Veuillez remplir tous les champs.';
    } elseif (strlen($contenu) < 10) {
        $erreur = 'Votre message doit faire au moins 10 caractères.';
    } else {
        try {
            // Début de la transaction
            $pdo->beginTransaction();
            
            // 1. Créer une nouvelle conversation
            $stmt_conv = $pdo->prepare("
                INSERT INTO conversations (id_utilisateur, sujet, lu, supprime, date_creation, statut) 
                VALUES (?, ?, 0, 0, NOW(), 'en attente admin')
            ");
            $stmt_conv->execute([$_SESSION['utilisateur_id'], $sujet]);
            
            $id_conversation = $pdo->lastInsertId();
            
            if (!$id_conversation) {
                throw new Exception('Erreur lors de la création de la conversation.');
            }
            
            // 2. Ajouter le message initial
            $stmt_msg = $pdo->prepare("
                INSERT INTO messages (id_conversation, role_expediteur, contenu, date_envoi) 
                VALUES (?, 'utilisateur', ?, NOW())
            ");
            $stmt_msg->execute([$id_conversation, $contenu]);
            
            // Valider la transaction
            $pdo->commit();
            $message_envoye = true;
        } catch (PDOException $e) {
            // Annuler la transaction en cas d'erreur
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('PDO Error contact.php: ' . $e->getMessage());
            $erreur = 'Erreur lors de l\'envoi du message. Réessayez plus tard.';
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Erreur contact.php: ' . $e->getMessage());
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