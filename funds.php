<?php
require_once __DIR__ . '/includes/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)($_POST['amount'] ?? 0);
    $bankReference = trim($_POST['bank_reference'] ?? '');

    if ($amount <= 0) {
        $error = 'Please enter a valid amount.';
    } elseif ($bankReference === '') {
        $error = 'Please enter your bank transfer reference / slip number.';
    } else {
        $receiptPath = null;

        // Optional receipt upload
        if (!empty($_FILES['receipt']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            $ext = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                $error = 'Receipt must be a JPG, PNG, or PDF file.';
            } elseif ($_FILES['receipt']['size'] > 5 * 1024 * 1024) {
                $error = 'Receipt file must be under 5MB.';
            } else {
                $filename = 'receipt_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                $destination = __DIR__ . '/uploads/receipts/' . $filename;

                if (move_uploaded_file($_FILES['receipt']['tmp_name'], $destination)) {
                    $receiptPath = 'uploads/receipts/' . $filename;
                } else {
                    $error = 'Failed to upload receipt. Please try again.';
                }
            }
        }

        if ($error === '') {
            $stmt = $pdo->prepare(
                'INSERT INTO fund_requests (user_id, amount, bank_reference, receipt_path) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$_SESSION['user_id'], $amount, $bankReference, $receiptPath]);
            $success = 'Your top-up request has been submitted. It will be reviewed and approved shortly.';
        }
    }
}

$stmt = $pdo->prepare('SELECT * FROM fund_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
$stmt->execute([$_SESSION['user_id']]);
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Funds - NIMA DEV SMM</title>
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

    <div class="container wide">
        <h1>Add Funds</h1>

        <div class="alert" style="background:#1c2a3a;color:#8ac8ff;border:1px solid #2a3f5a;">
            Transfer to the bank account below, then submit the form with your reference number.
            Your balance will be updated once an admin approves your request.
            <br><br>
            <strong>Bank:</strong> <?= htmlspecialchars(BANK_NAME) ?><br>
            <strong>Account Name:</strong> <?= htmlspecialchars(BANK_ACCOUNT_NAME) ?><br>
            <strong>Account Number:</strong> <?= htmlspecialchars(BANK_ACCOUNT_NUMBER) ?><br>
            <strong>Branch:</strong> <?= htmlspecialchars(BANK_BRANCH) ?>
        </div>

        <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <form method="POST" action="funds.php" enctype="multipart/form-data">
            <div class="form-group">
                <label>Amount ($)</label>
                <input type="number" step="0.01" min="0.01" name="amount" required>
            </div>
            <div class="form-group">
                <label>Bank Transfer Reference / Slip Number</label>
                <input type="text" name="bank_reference" placeholder="e.g. TXN123456" required>
            </div>
            <div class="form-group">
                <label>Receipt (optional, JPG/PNG/PDF, max 5MB)</label>
                <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf">
            </div>
            <button type="submit">Submit Top-Up Request</button>
        </form>

        <h2 style="margin-top:32px;">Your Requests</h2>
        <?php if (empty($requests)): ?>
            <p style="color:#a0a5b1;">No top-up requests yet.</p>
        <?php else: ?>
            <table>
                <tr><th>Amount</th><th>Reference</th><th>Status</th><th>Date</th></tr>
                <?php foreach ($requests as $r): ?>
                <tr>
                    <td>$<?= number_format($r['amount'], 2) ?></td>
                    <td><?= htmlspecialchars($r['bank_reference']) ?></td>
                    <td><?= htmlspecialchars(ucfirst($r['status'])) ?></td>
                    <td><?= $r['created_at'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
