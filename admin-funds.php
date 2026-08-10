<?php
require_once __DIR__ . '/includes/admin_auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = (int)($_POST['request_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM fund_requests WHERE id = ? AND status = ?');
    $stmt->execute([$requestId, 'pending']);
    $request = $stmt->fetch();

    if (!$request) {
        $error = 'Request not found or already reviewed.';
    } elseif ($action === 'approve') {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE users SET balance = balance + ? WHERE id = ?');
            $stmt->execute([$request['amount'], $request['user_id']]);

            $stmt = $pdo->prepare("UPDATE fund_requests SET status = 'approved', reviewed_at = NOW() WHERE id = ?");
            $stmt->execute([$requestId]);

            $pdo->commit();
            $success = 'Request #' . $requestId . ' approved and balance updated.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to approve request.';
        }
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE fund_requests SET status = 'rejected', reviewed_at = NOW() WHERE id = ?");
        $stmt->execute([$requestId]);
        $success = 'Request #' . $requestId . ' rejected.';
    }
}

$stmt = $pdo->query(
    "SELECT fr.*, u.username FROM fund_requests fr
     JOIN users u ON u.id = fr.user_id
     ORDER BY (fr.status = 'pending') DESC, fr.created_at DESC
     LIMIT 50"
);
$requests = $stmt->fetchAll();
$activeTab = 'funds';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Fund Requests - NIMA DEV SMM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/includes/admin_nav.php'; ?>

    <div class="container xwide">
        <h1>Fund Requests</h1>
        <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <table>
            <tr><th>User</th><th>Amount</th><th>Reference</th><th>Receipt</th><th>Status</th><th>Date</th><th>Action</th></tr>
            <?php foreach ($requests as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['username']) ?></td>
                <td>$<?= number_format($r['amount'], 2) ?></td>
                <td><?= htmlspecialchars($r['bank_reference']) ?></td>
                <td>
                    <?php if ($r['receipt_path']): ?>
                        <a href="<?= htmlspecialchars($r['receipt_path']) ?>" target="_blank" style="color:#7c5cff;">View</a>
                    <?php else: ?>-<?php endif; ?>
                </td>
                <td><span class="badge <?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars(ucfirst($r['status'])) ?></span></td>
                <td><?= $r['created_at'] ?></td>
                <td>
                    <?php if ($r['status'] === 'pending'): ?>
                        <div class="actions-row">
                        <form method="POST">
                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn-sm">Approve</button>
                        </form>
                        <form method="POST">
                            <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn-sm btn-danger">Reject</button>
                        </form>
                        </div>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
