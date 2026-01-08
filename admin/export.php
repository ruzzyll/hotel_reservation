<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_role(['admin']);

if (isset($_GET['download'])) {
    // Export minimal fields: customer name, reservation date, and status
    $stmt = $pdo->query("
        SELECT c.name AS customer_name,
               DATE(r.reservation_time) AS reservation_date,
               TIME(r.reservation_time) AS reservation_time,
               s.name AS status_name
        FROM reservations r
        JOIN customers c ON r.customer_id = c.id
        JOIN reservation_status s ON r.status_id = s.id
        ORDER BY r.reservation_time DESC
    ");
    $reservations = $stmt->fetchAll();

    // Calculate summary counts
    $pending = 0;
    $approved = 0;
    $rejected = 0;
    foreach ($reservations as $row) {
        $status = strtolower($row['status_name']);
        if ($status === 'pending') {
            $pending++;
        } elseif ($status === 'approved') {
            $approved++;
        } elseif ($status === 'rejected') {
            $rejected++;
        }
    }
    $total = count($reservations);

    $filename = 'reservations_summary_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename={$filename}");
    // Add UTF-8 BOM so Excel detects encoding and separators properly
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");

    // Summary table at the top of the sheet
    fputcsv($out, ['Pending', 'Approved', 'Rejected', 'Total']);
    fputcsv($out, [$pending, $approved, $rejected, $total]);

    // Blank line then detailed customer reservations
    fputcsv($out, []);
    fputcsv($out, ['Customer Name', 'Reservation Date', 'Reservation Time', 'Status']);
    foreach ($reservations as $row) {
        fputcsv($out, [
            $row['customer_name'],
            $row['reservation_date'],
            $row['reservation_time'],
            $row['status_name'],
        ]);
    }
    fclose($out);

    $pdo->prepare("INSERT INTO exports (user_id, file_name) VALUES (?, ?)")
        ->execute([current_user()['id'], $filename]);
    log_action($pdo, current_user()['id'], "exported {$filename}");
    exit;
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Export Reservations</title>
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
            <div class="topbar-title">Export</div>
            <div class="topbar-user">
                <?php $user = current_user(); echo safe_output($user['name'] ?? ''); ?>
            </div>
        </div>
        <div class="content">
            <div class="box">
                <div class="box-header">Export Reservations</div>
                <div class="box-body">
                    <p>Export customer name, reservation date, and status, plus a summary of pending, approved, rejected, and total reservations.</p>
                    <a href="<?php echo BASE_PATH; ?>/admin/export.php?download=1">
                        <button type="button">Export to Excel</button>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?php echo BASE_PATH; ?>/assets/js/app.js"></script>
</body>
</html>
