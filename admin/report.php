<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_role(['admin']);

$stmt = $pdo->query("
    SELECT DATE_FORMAT(reservation_time, '%Y-%m') AS month, COUNT(*) AS total
    FROM reservations
    GROUP BY DATE_FORMAT(reservation_time, '%Y-%m')
    ORDER BY month DESC
");
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Report</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-header">Hotel Admin</div>
        <ul class="sidebar-menu">
            <li><a href="<?php echo BASE_PATH; ?>/admin/index.php">Dashboard</a></li>
            <li><a href="<?php echo BASE_PATH; ?>/admin/analytics.php">Analytics</a></li>
            <li><a href="<?php echo BASE_PATH; ?>/admin/reservations.php">Customer Reserve</a></li>
            <li><a href="<?php echo BASE_PATH; ?>/auth/logout.php" class="logout-link">Logout</a></li>
        </ul>
    </aside>
    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Monthly Report</div>
            <div class="topbar-user">
                <?php $user = current_user(); echo safe_output($user['name'] ?? ''); ?>
            </div>
        </div>
        <div class="content">
            <div class="box">
                <div class="box-header">Monthly Reservations</div>
                <div class="box-body">
                    <table>
                        <tr><th>Month</th><th>Reservations</th></tr>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?php echo safe_output($row['month']); ?></td>
                                <td><?php echo safe_output($row['total']); ?></td>
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
