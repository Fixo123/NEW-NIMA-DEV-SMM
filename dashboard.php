<?php
require_once __DIR__ . '/includes/auth.php';

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$stmt = $pdo->prepare(
    'SELECT o.*, s.name AS service_name FROM orders o
     JOIN services s ON s.id = o.service_id
     WHERE o.user_id = ? ORDER BY o.created_at DESC LIMIT 20'
);
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$totalOrders = count($orders);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - NIMA DEV SMM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="navbar">
        <div class="brand">NIMA DEV SMM</div>
        <div>
            <a href="dashboard.php">Dashboard</a>
            <a href="order.php">New Order</a>
            <a href="funds.php">Add Funds</a>
            <?php if ($user['is_admin']): ?><a href="admin.php">Admin</a><?php endif; ?>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container wide">
        <h1>Welcome, <?= htmlspecialchars($user['username']) ?></h1>

        <div class="stats-row">
            <div class="stat-card">
                <div class="label">Balance</div>
                <div class="value">$<?= number_format($user['balance'], 2) ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Total Orders</div>
                <div class="value"><?= $totalOrders ?></div>
            </div>
        </div>

        <h2>Recent Orders</h2>
        <?php if (empty($orders)): ?>
            <p style="color:#a0a5b1;">No orders yet. <a href="order.php" style="color:#7c5cff;">Place your first order</a>.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>ID</th><th>Service</th><th>Link</th><th>Qty</th><th>Charge</th><th>Status</th><th>API Order</th><th>Date</th>
                </tr>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td>#<?= $o['id'] ?></td>
                    <td><?= htmlspecialchars($o['service_name']) ?></td>
                    <td><?= htmlspecialchars(substr($o['link'], 0, 30)) ?>...</td>
                    <td><?= $o['quantity'] ?></td>
                    <td>$<?= number_format($o['charge'], 2) ?></td>
                    <td><?= htmlspecialchars($o['status']) ?></td>
                    <td><?= $o['api_order_id'] ? '#' . htmlspecialchars($o['api_order_id']) : '-' ?></td>
                    <td><?= $o['created_at'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
