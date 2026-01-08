<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_role(['admin']);

// Fetch counts by status
$statusCounts = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
];
$stmt = $pdo->query("
    SELECT s.name AS status_name, COUNT(*) AS total
    FROM reservations r
    JOIN reservation_status s ON r.status_id = s.id
    GROUP BY s.name
");
foreach ($stmt->fetchAll() as $row) {
    $name = strtolower($row['status_name']);
    $statusCounts[$name] = (int)$row['total'];
}
$total = (int)$pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Analytics</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-header">Hotel Admin</div>
        <ul class="sidebar-menu">
            <li><a href="<?php echo BASE_PATH; ?>/admin/index.php">Dashboard</a></li>
            <li><a href="<?php echo BASE_PATH; ?>/admin/analytics.php" class="active">Analytics</a></li>
            <li><a href="<?php echo BASE_PATH; ?>/admin/reservations.php">Customer Reserve</a></li>
            <li><a href="<?php echo BASE_PATH; ?>/auth/logout.php" class="logout-link">Logout</a></li>
        </ul>
    </aside>
    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Analytics</div>
            <div class="topbar-user">
                <?php $user = current_user(); echo safe_output($user['name'] ?? ''); ?>
            </div>
        </div>
        <div class="content">
            <div class="content-header">Reservation Status Overview</div>
            <div class="box">
                <div class="box-header">Status Distribution</div>
                <div class="box-body">
                    <canvas id="statusChart" height="90"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?php echo BASE_PATH; ?>/assets/js/app.js"></script>
<script>
const ctx = document.getElementById('statusChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Pending', 'Approved', 'Rejected', 'Total'],
        datasets: [{
            label: 'Reservations',
            data: [
                <?php echo $statusCounts['pending']; ?>,
                <?php echo $statusCounts['approved']; ?>,
                <?php echo $statusCounts['rejected']; ?>,
                <?php echo $total; ?>
            ],
            backgroundColor: ['#f39c12', '#00a65a', '#dd4b39', '#00c0ef']
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: { precision: 0 }
            }
        }
    }
});
</script>
</body>
</html>
