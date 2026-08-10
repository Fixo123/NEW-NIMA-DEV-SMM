<?php
// $activeTab should be set by the including page: overview, services, orders, funds, sync
$activeTab = $activeTab ?? '';
?>
<div class="navbar">
    <div class="brand">NIMA DEV SMM · Admin</div>
    <div>
        <a href="dashboard.php">User View</a>
        <a href="logout.php">Logout</a>
    </div>
</div>
<div class="container xwide" style="margin-bottom:0;padding-bottom:0;">
    <div class="tabs">
        <a href="admin.php" class="<?= $activeTab === 'overview' ? 'active' : '' ?>">Overview</a>
        <a href="admin-services.php" class="<?= $activeTab === 'services' ? 'active' : '' ?>">Services</a>
        <a href="admin-orders.php" class="<?= $activeTab === 'orders' ? 'active' : '' ?>">Orders</a>
        <a href="admin-funds.php" class="<?= $activeTab === 'funds' ? 'active' : '' ?>">Fund Requests</a>
        <a href="admin-sync.php" class="<?= $activeTab === 'sync' ? 'active' : '' ?>">Sync Services</a>
    </div>
</div>
