<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/smm_api.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'refresh') {
    $orderId = (int)($_POST['order_id'] ?? 0);

    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order || !$order['api_order_id']) {
        $error = 'This order has no provider order ID to check.';
    } else {
        $result = smm_order_status($order['api_order_id']);

        if (isset($result['status'])) {
            $newStatus = strtolower($result['status']);
            $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $stmt->execute([$newStatus, $orderId]);
            $success = "Order #$orderId status updated to: " . htmlspecialchars($result['status']);
        } else {
            $error = 'Could not fetch status: ' . htmlspecialchars($result['error'] ?? 'unknown error');
        }
    }
}

$filter = $_GET['status'] ?? '';
$sql = "SELECT o.*, u.username, s.name AS service_name FROM orders o
        JOIN users u ON u.id = o.user_id
        JOIN services s ON s.id = o.service_id";
$params = [];
if ($filter !== '') {
    $sql .= ' WHERE o.status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY o.created_at DESC LIMIT 100';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$activeTab = 'orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Orders - NIMA DEV SMM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/includes/admin_nav.php'; ?>

    <div class="container xwide">
        <h1>All Orders</h1>
        <?php if ($error): ?><div class="alert error"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?= $success ?></div><?php endif; ?>

        <div class="tabs">
            <a href="admin-orders.php" class="<?= $filter === '' ? 'active' : '' ?>">All</a>
            <a href="admin-orders.php?status=pending" class="<?= $filter === 'pending' ? 'active' : '' ?>">Pending</a>
            <a href="admin-orders.php?status=processing" class="<?= $filter === 'processing' ? 'active' : '' ?>">Processing</a>
            <a href="admin-orders.php?status=completed" class="<?= $filter === 'completed' ? 'active' : '' ?>">Completed</a>
            <a href="admin-orders.php?status=failed" class="<?= $filter === 'failed' ? 'active' : '' ?>">Failed</a>
        </div>

        <?php if (empty($orders)): ?>
            <p style="color:#a0a5b1;">No orders found.</p>
        <?php else: ?>
            <table>
                <tr><th>ID</th><th>User</th><th>Service</th><th>Link</th><th>Qty</th><th>Charge</th><th>Status</th><th>API #</th><th>Date</th><th>Action</th></tr>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td>#<?= $o['id'] ?></td>
                    <td><?= htmlspecialchars($o['username']) ?></td>
                    <td><?= htmlspecialchars($o['service_name']) ?></td>
                    <td><a href="<?= htmlspecialchars($o['link']) ?>" target="_blank" style="color:#7c5cff;"><?= htmlspecialchars(substr($o['link'], 0, 25)) ?>...</a></td>
                    <td><?= $o['quantity'] ?></td>
                    <td>$<?= number_format($o['charge'], 2) ?></td>
                    <td><span class="badge <?= htmlspecialchars($o['status']) ?>"><?= htmlspecialchars(ucfirst($o['status'])) ?></span></td>
                    <td><?= $o['api_order_id'] ? '#' . htmlspecialchars($o['api_order_id']) : '-' ?></td>
                    <td><?= $o['created_at'] ?></td>
                    <td>
                        <?php if ($o['api_order_id']): ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="refresh">
                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                            <button type="submit" class="btn-sm btn-outline">Refresh</button>
                        </form>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
