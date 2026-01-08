<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_role(['admin']);

$totalReservations = (int)$pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$pendingReservations = (int)$pdo->query("SELECT COUNT(*) FROM reservations r JOIN reservation_status s ON r.status_id = s.id WHERE s.name = 'pending'")->fetchColumn();
$approvedReservations = (int)$pdo->query("SELECT COUNT(*) FROM reservations r JOIN reservation_status s ON r.status_id = s.id WHERE s.name = 'approved'")->fetchColumn();
$rejectedReservations = (int)$pdo->query("SELECT COUNT(*) FROM reservations r JOIN reservation_status s ON r.status_id = s.id WHERE s.name = 'rejected'")->fetchColumn();

$stmt = $pdo->query("SELECT * FROM hotels ORDER BY name LIMIT 5");
$hotels = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-header">Hotel Admin</div>
        <ul class="sidebar-menu">
            <li><a href="<?php echo BASE_PATH; ?>/admin/index.php" class="active">Dashboard</a></li>
            <li><a href="<?php echo BASE_PATH; ?>/admin/analytics.php">Analytics</a></li>
            <li><a href="<?php echo BASE_PATH; ?>/admin/reservations.php">Customer Reserve</a></li>
            <li><a href="<?php echo BASE_PATH; ?>/auth/logout.php" class="logout-link">Logout</a></li>
        </ul>
    </aside>
    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Dashboard</div>
            <div class="topbar-user">
                <?php $user = current_user(); echo safe_output($user['name'] ?? ''); ?>
            </div>
        </div>
        <div class="content">
            <div class="content-header">Overview</div>

            <div class="cards">
                <div class="card orange">
                    <div class="number"><?php echo $totalReservations; ?></div>
                    <div class="label">Total Reservations</div>
                    <div class="more">More info</div>
                </div>
                <div class="card green">
                    <div class="number"><?php echo $approvedReservations; ?></div>
                    <div class="label">Approved</div>
                    <div class="more">More info</div>
                </div>
                <div class="card red">
                    <div class="number"><?php echo $pendingReservations; ?></div>
                    <div class="label">Pending</div>
                    <div class="more">More info</div>
                </div>
                <div class="card blue">
                    <div class="number"><?php echo $rejectedReservations; ?></div>
                    <div class="label">Rejected</div>
                    <div class="more">More info</div>
                </div>
            </div>

            <div class="box">
                <div class="box-header">Hotels / Services</div>
                <div class="box-body">
                    <table>
                        <tr><th>Name</th><th>Description</th></tr>
                        <?php foreach ($hotels as $hotel): ?>
                            <tr>
                                <td><?php echo safe_output($hotel['name']); ?></td>
                                <td><?php echo safe_output($hotel['description']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
<script src="<?php echo BASE_PATH; ?>/assets/js/app.js"></script>
</body>
</html>
