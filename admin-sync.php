<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once __DIR__ . '/includes/smm_api.php';

$error = '';
$success = '';
$providerServices = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import') {
    $margin = (float)($_POST['margin'] ?? 0);
    $selected = $_POST['selected'] ?? []; // array of provider service IDs
    $servicesData = json_decode($_POST['services_data'] ?? '[]', true) ?: [];

    $imported = 0;
    foreach ($servicesData as $svc) {
        if (!in_array((string)$svc['service'], $selected)) {
            continue;
        }

        $providerRate = (float)$svc['rate'];
        $ourRate = round($providerRate * (1 + $margin / 100), 2);

        // Upsert by provider_service_id
        $stmt = $pdo->prepare('SELECT id FROM services WHERE provider_service_id = ?');
        $stmt->execute([$svc['service']]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $pdo->prepare(
                'UPDATE services SET name=?, category=?, rate_per_1000=?, min_order=?, max_order=? WHERE id=?'
            );
            $stmt->execute([$svc['name'], $svc['category'] ?? 'General', $ourRate, $svc['min'], $svc['max'], $existing['id']]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO services (provider_service_id, name, category, rate_per_1000, min_order, max_order) VALUES (?,?,?,?,?,?)'
            );
            $stmt->execute([$svc['service'], $svc['name'], $svc['category'] ?? 'General', $ourRate, $svc['min'], $svc['max']]);
        }
        $imported++;
    }

    $success = "$imported service(s) imported/updated with a {$margin}% margin.";
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch'])) {
    $result = smm_service_list();

    if (isset($result['error'])) {
        $error = 'Failed to fetch provider services: ' . htmlspecialchars($result['error']);
    } elseif (is_array($result)) {
        $providerServices = $result;
    } else {
        $error = 'Unexpected response from provider.';
    }
}

$activeTab = 'sync';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Sync Services - NIMA DEV SMM</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/includes/admin_nav.php'; ?>

    <div class="container xwide">
        <h1>Sync Services from Provider</h1>
        <p class="card-note" style="margin-bottom:20px;">
            Pull the real service list (IDs, rates, min/max) from your API provider and import them
            straight into your <code>services</code> table, with a markup applied automatically.
        </p>

        <?php if ($error): ?><div class="alert error"><?= $error ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?= $success ?></div><?php endif; ?>

        <a href="admin-sync.php?fetch=1" class="btn btn-sm" style="width:auto;display:inline-block;margin-bottom:20px;">
            Fetch Latest Services from Provider
        </a>

        <?php if (!empty($providerServices)): ?>
            <form method="POST" action="admin-sync.php">
                <input type="hidden" name="action" value="import">
                <input type="hidden" name="services_data" value='<?= htmlspecialchars(json_encode($providerServices)) ?>'>

                <div class="form-inline">
                    <div class="form-group">
                        <label>Markup % (added on top of provider rate)</label>
                        <input type="number" step="0.1" name="margin" value="20" required>
                    </div>
                    <button type="submit" style="width:auto;">Import Selected</button>
                </div>

                <table>
                    <tr>
                        <th><input type="checkbox" onclick="document.querySelectorAll('.svc-check').forEach(c=>c.checked=this.checked)"></th>
                        <th>Provider ID</th><th>Name</th><th>Category</th><th>Rate/1000</th><th>Min–Max</th>
                    </tr>
                    <?php foreach ($providerServices as $svc): ?>
                    <tr>
                        <td><input type="checkbox" class="svc-check" name="selected[]" value="<?= htmlspecialchars($svc['service']) ?>"></td>
                        <td><?= htmlspecialchars($svc['service']) ?></td>
                        <td><?= htmlspecialchars($svc['name']) ?></td>
                        <td><?= htmlspecialchars($svc['category'] ?? '-') ?></td>
                        <td>$<?= htmlspecialchars($svc['rate']) ?></td>
                        <td><?= htmlspecialchars($svc['min']) ?>–<?= htmlspecialchars($svc['max']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </form>
        <?php elseif (isset($_GET['fetch']) && !$error): ?>
            <p style="color:#a0a5b1;">No services returned by the provider.</p>
        <?php endif; ?>
    </div>
</body>
</html>
