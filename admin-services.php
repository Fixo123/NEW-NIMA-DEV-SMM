<?php
require_once __DIR__ . '/includes/admin_auth.php';

$error = '';
$success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $rate = (float)($_POST['rate_per_1000'] ?? 0);
        $min = (int)($_POST['min_order'] ?? 0);
        $max = (int)($_POST['max_order'] ?? 0);
        $providerServiceId = $_POST['provider_service_id'] !== '' ? (int)$_POST['provider_service_id'] : null;
        $description = trim($_POST['description'] ?? '');

        if ($name === '' || $category === '' || $rate <= 0 || $min <= 0 || $max < $min) {
            $error = 'Please fill in all fields correctly (max must be >= min).';
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare(
                    'UPDATE services SET name=?, category=?, rate_per_1000=?, min_order=?, max_order=?, provider_service_id=?, description=? WHERE id=?'
                );
                $stmt->execute([$name, $category, $rate, $min, $max, $providerServiceId, $description, $id]);
                $success = 'Service updated.';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO services (name, category, rate_per_1000, min_order, max_order, provider_service_id, description) VALUES (?,?,?,?,?,?,?)'
                );
                $stmt->execute([$name, $category, $rate, $min, $max, $providerServiceId, $description]);
                $success = 'Service added.';
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM services WHERE id = ?');
        $stmt->execute([$id]);
        $success = 'Service deleted.';
    } elseif ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE services SET active = NOT active WHERE id = ?');
        $stmt->execute([$id]);
        $success = 'Service status updated.';
    }
}

// If editing, load that service
$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $editing = $stmt->fetch();
}

$services = $pdo->query('SELECT * FROM services ORDER BY category, name')->fetchAll();
$activeTab = 'services';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Services - NIMA DEV SMM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/includes/admin_nav.php'; ?>

    <div class="container xwide">
        <div class="section-header">
            <h1><?= $editing ? 'Edit Service' : 'Add Service' ?></h1>
            <?php if ($editing): ?><a href="admin-services.php" class="btn btn-outline btn-sm">Cancel Edit</a><?php endif; ?>
        </div>

        <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <form method="POST" action="admin-services.php" style="margin-bottom:32px;">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= $editing['id'] ?? 0 ?>">
            <div class="form-inline">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($editing['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" value="<?= htmlspecialchars($editing['category'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Provider Service ID</label>
                    <input type="number" name="provider_service_id" value="<?= htmlspecialchars($editing['provider_service_id'] ?? '') ?>" placeholder="optional">
                </div>
            </div>
            <div class="form-inline">
                <div class="form-group">
                    <label>Rate per 1000 ($)</label>
                    <input type="number" step="0.01" name="rate_per_1000" value="<?= htmlspecialchars($editing['rate_per_1000'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Min Order</label>
                    <input type="number" name="min_order" value="<?= htmlspecialchars($editing['min_order'] ?? 100) ?>" required>
                </div>
                <div class="form-group">
                    <label>Max Order</label>
                    <input type="number" name="max_order" value="<?= htmlspecialchars($editing['max_order'] ?? 10000) ?>" required>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <input type="text" name="description" value="<?= htmlspecialchars($editing['description'] ?? '') ?>">
            </div>
            <button type="submit" class="btn-sm" style="width:auto;"><?= $editing ? 'Update Service' : 'Add Service' ?></button>
        </form>

        <h2>All Services</h2>
        <table>
            <tr><th>Name</th><th>Category</th><th>Rate/1000</th><th>Min–Max</th><th>Provider ID</th><th>Status</th><th>Actions</th></tr>
            <?php foreach ($services as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['name']) ?></td>
                <td><?= htmlspecialchars($s['category']) ?></td>
                <td>$<?= number_format($s['rate_per_1000'], 2) ?></td>
                <td><?= $s['min_order'] ?>–<?= $s['max_order'] ?></td>
                <td><?= $s['provider_service_id'] ?: '-' ?></td>
                <td><span class="badge <?= $s['active'] ? 'approved' : 'rejected' ?>"><?= $s['active'] ? 'Active' : 'Disabled' ?></span></td>
                <td>
                    <div class="actions-row">
                        <a href="admin-services.php?edit=<?= $s['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn-sm"><?= $s['active'] ? 'Disable' : 'Enable' ?></button>
                        </form>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this service?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                            <button type="submit" class="btn-sm btn-danger">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
