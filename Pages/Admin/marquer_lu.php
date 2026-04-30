<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header('Location: /Pages/index.php');
    exit;
}

require_once '../../include/db.php';
require_once '../../include/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_message'])) {
    csrf_verify();
    $id = (int)$_POST['id_message'];
    $stmt = $pdo->prepare("UPDATE messages_contact SET lu = 1 WHERE id_message = ?");
    $stmt->execute([$id]);
}

header('Location: admin_dashboard.php');
exit;
?>