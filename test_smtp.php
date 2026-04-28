<?php

require_once 'include/smtp_mail.php';

echo "=== Test SMTP MailHog ===\n";
echo "Host: 127.0.0.1\n";
echo "Port: 1025\n\n";

$testEmail = 'test@example.com';
$subject = 'Test SMTP MailHog';
$body = "Ceci est un mail de test pour verifier que la connexion SMTP fonctionne correctement vers MailHog.\n\nDate: " . date('Y-m-d H:i:s') . "\n\nTest avec domaine example.com";

echo "Envoi du mail de test...\n";
$result = sendSmtpMail($testEmail, $subject, $body);

if ($result) {
    echo "✓ Mail envoyé avec succès!\n";
    echo "Vérifiez MailHog sur http://localhost:8026\n";
} else {
    echo "✗ Erreur lors de l'envoi du mail.\n";
    echo "Vérifiez le fichier /debug-logs ou le terminal pour les erreurs SMTP.\n";
}

echo "\nFin du test.\n";
?>
