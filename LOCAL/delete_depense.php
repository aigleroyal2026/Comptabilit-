<?php
require __DIR__ . "/config.php";
require __DIR__ . "/auth.php";

$isAdmin = (($_SESSION['role'] ?? '') === 'admin');
if (!$isAdmin) {
    http_response_code(403);
    die("Accès refusé.");
}

$id = (int)($_GET["id"] ?? 0);
if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM depenses WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
}

// Retour : si on a un back on l'utilise, sinon index
$back = $_GET['back'] ?? 'index.php';
header("Location: " . $back);
exit;
