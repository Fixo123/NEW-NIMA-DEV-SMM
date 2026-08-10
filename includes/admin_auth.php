<?php
// Include this at the top of any admin-only page.
require_once __DIR__ . '/auth.php';

$stmt = $pdo->prepare('SELECT is_admin FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$isAdmin = (bool)$stmt->fetchColumn();

if (!$isAdmin) {
    header('Location: dashboard.php');
    exit;
}
