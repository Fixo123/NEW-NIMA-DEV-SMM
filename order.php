<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/smm_api.php';

$stmt = $pdo->query('SELECT * FROM services WHERE active = 1 ORDER BY category, name');
$services = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceId = (int)($_POST['service_id'] ?? 0);
    $link      = trim($_POST['link'] ?? '');
    $quantity  = (int)($_POST['quantity'] ?? 0);

    $stmt = $pdo->prepare('SELECT * FROM services WHERE id = ? AND active = 1');
    $stmt->execute([$serviceId]);
    $service = $stmt->fetch();

    if (!$service) {
        $error = 'Please select a valid service.';
    } elseif ($link === '') {
        $error = 'Please enter a link.';
    } elseif ($quantity < $service['min_order'] || $quantity > $service['max_order']) {
        $error = "Quantity must be between {$service['min_order']} and {$service['max_order']}.";
    } else {
        $charge = round(($quantity / 1000) * $service['rate_per_1000'], 2);

        // Check user balance
        $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $balance = (float)$stmt->fetchColumn();

        if ($balance < $charge) {
            $error = 'Insufficient balance. Please add funds first.';
        } else {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO orders (user_id, service_id, link, quantity, charge, status) VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([$_SESSION['user_id'], $serviceId, $link, $quantity, $charge, 'pending']);
                $orderId = $pdo->lastInsertId();

                $stmt = $pdo->prepare('UPDATE users SET balance = balance - ? WHERE id = ?');
                $stmt->execute([$charge, $_SESSION['user_id']]);

                $pdo->commit();

                // Now actually send the order to the API provider.
                if (empty($service['provider_service_id'])) {
                    $error = 'This service is not linked to a provider service ID yet. Order saved as pending — set provider_service_id in the services table.';
                } else {
                    $apiResponse = smm_place_order((int)$service['provider_service_id'], $link, $quantity);

                    if (isset($apiResponse['order'])) {
                        // Success — store the provider's order ID and mark it as processing.
                        $stmt = $pdo->prepare('UPDATE orders SET api_order_id = ?, status = ? WHERE id = ?');
                        $stmt->execute([$apiResponse['order'], 'processing', $orderId]);
                        $success = 'Order placed and sent to the provider! Order #' . $orderId;
                    } else {
                        // Provider rejected it — refund the user and mark the order failed.
                        $pdo->beginTransaction();
                        $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
                        $stmt->execute(['failed', $orderId]);
                        $stmt = $pdo->prepare('UPDATE users SET balance = balance + ? WHERE id = ?');
                        $stmt->execute([$charge, $_SESSION['user_id']]);
                        $pdo->commit();

                        $error = 'Provider rejected the order: ' . ($apiResponse['error'] ?? 'unknown error') . '. You have been refunded.';
                    }
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Something went wrong. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Order - NIMA DEV SMM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="navbar">
        <div class="brand">NIMA DEV SMM</div>
        <div>
            <a href="dashboard.php">Dashboard</a>
            <a href="order.php">New Order</a>
            <a href="funds.php">Add Funds</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <h1>Place New Order</h1>
        <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <form method="POST" action="order.php">
            <div class="form-group">
                <label>Service</label>
                <select name="service_id" required>
                    <option value="">-- Select a service --</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= $s['id'] ?>">
                            <?= htmlspecialchars($s['category'] . ' - ' . $s['name']) ?>
                            ($<?= number_format($s['rate_per_1000'], 2) ?> / 1000)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Link (post/profile URL)</label>
                <input type="text" name="link" placeholder="https://instagram.com/yourprofile" required>
            </div>
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" placeholder="e.g. 1000" required>
            </div>
            <button type="submit">Submit Order</button>
        </form>
    </div>
</body>
</html>
