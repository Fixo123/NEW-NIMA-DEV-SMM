<?php
require_once __DIR__ . '/includes/admin_auth.php';

$totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalOrders = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(charge),0) FROM orders WHERE status != 'failed'")->fetchColumn();
$pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','processing')")->fetchColumn();
$pendingFunds = (int)$pdo->query("SELECT COUNT(*) FROM fund_requests WHERE status = 'pending'")->fetchColumn();
$activeServices = (int)$pdo->query('SELECT COUNT(*) FROM services WHERE active = 1')->fetchColumn();

$stmt = $pdo->query(
    "SELECT o.*, u.username, s.name AS service_name FROM orders o
     JOIN users u ON u.id = o.user_id
     JOIN services s ON s.id = o.service_id
     ORDER BY o.created_at DESC LIMIT 8"
);
$recentOrders = $stmt->fetchAll();

$activeTab = 'overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Overview - NIMA DEV SMM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/includes/admin_nav.php'; ?>

    <div class="container xwide">
        <h1>Overview</h1>

        <div class="stats-row">
            <div class="stat-card"><div class="label">Users</div><div class="value"><?= $totalUsers ?></div></div>
            <div class="stat-card"><div class="label">Total Orders</div><div class="value"><?= $totalOrders ?></div></div>
            <div class="stat-card"><div class="label">Revenue</div><div class="value">$<?= number_format($totalRevenue, 2) ?></div></div>
            <div class="stat-card"><div class="label">Pending Orders</div><div class="value"><?= $pendingOrders ?></div></div>
            <div class="stat-card"><div class="label">Pending Top-Ups</div><div class="value"><?= $pendingFunds ?></div></div>
            <div class="stat-card"><div class="label">Active Services</div><div class="value"><?= $activeServices ?></div></div>
        </div>

        <?php if ($pendingFunds > 0): ?>
            <div class="alert" style="background:#3a2f1c;color:#ffcf7a;border:1px solid #5a4a2a;">
                You have <?= $pendingFunds ?> pending top-up request<?= $pendingFunds > 1 ? 's' : '' ?> waiting for review.
                <a href="admin-funds.php" style="color:#ffcf7a;text-decoration:underline;">Review now</a>
            </div>
        <?php endif; ?>

        <h2>Recent Orders</h2>
        <?php if (empty($recentOrders)): ?>
            <p style="color:#a0a5b1;">No orders yet.</p>
        <?php else: ?>
            <table>
                <tr><th>ID</th><th>User</th><th>Service</th><th>Qty</th><th>Charge</th><th>Status</th><th>Date</th></tr>
                <?php foreach ($recentOrders as $o): ?>
                <tr>
                    <td>#<?= $o['id'] ?></td>
                    <td><?= htmlspecialchars($o['username']) ?></td>
                    <td><?= htmlspecialchars($o['service_name']) ?></td>
                    <td><?= $o['quantity'] ?></td>
                    <td>$<?= number_format($o['charge'], 2) ?></td>
                    <td><span class="badge <?= htmlspecialchars($o['status']) ?>"><?= htmlspecialchars(ucfirst($o['status'])) ?></span></td>
                    <td><?= $o['created_at'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
