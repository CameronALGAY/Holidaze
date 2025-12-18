<?php
session_start();
if ($_SESSION['role'] !== 'admin') {
    header('Location: /Pages/index.php');
    exit;
}

require_once '../../include/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_message'])) {
    $id = (int)$_POST['id_message'];
    $stmt = $pdo->prepare("UPDATE messages_contact SET lu = 1 WHERE id_message = ?");
    $stmt->execute([$id]);
}

header('Location: admin_dashboard.php');
exit;
?>