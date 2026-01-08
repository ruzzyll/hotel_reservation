<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_role(['admin']);

$message = get_flash('message');
$error = get_flash('error');

// Load status ids
$statusStmt = $pdo->query("SELECT id, name FROM reservation_status");
$statusMap = [];
foreach ($statusStmt->fetchAll() as $row) {
    $statusMap[$row['name']] = (int)$row['id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservationId = (int)($_POST['reservation_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if ($reservationId && in_array($action, ['approve', 'reject'], true)) {
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $pdo->prepare("UPDATE reservations SET status_id = ? WHERE id = ?")
            ->execute([$statusMap[$newStatus] ?? 0, $reservationId]);
        log_action($pdo, current_user()['id'], "{$action} reservation #{$reservationId}");
        flash('message', "Reservation #{$reservationId} {$newStatus}.");
    } else {
        flash('error', 'Invalid action.');
    }
    redirect('/admin/reservations.php');
}

$stmt = $pdo->query("
    SELECT r.id, r.reservation_time, r.created_at, c.name AS customer_name, c.contact,
           h.name AS hotel_name, u.name AS created_by, u.role AS created_role,
           s.name AS status_name
    FROM reservations r
    JOIN customers c ON r.customer_id = c.id
    JOIN hotels h ON r.hotel_id = h.id
    JOIN users u ON r.user_id = u.id
    JOIN reservation_status s ON r.status_id = s.id
    ORDER BY r.created_at DESC
");
$reservations = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Customer Reservations</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar">
        <div class="sidebar-header">Hotel Admin</div>
        <ul class="sidebar-menu">
            <li><a href="<?php echo BASE_PATH; ?>/admin/index.php">Dashboard</a></li>
            <li><a href="<?php echo BASE_PATH; ?>/admin/analytics.php">Analytics</a></li>
            <li><a href="<?php echo BASE_PATH; ?>/admin/reservations.php" class="active">Customer Reserve</a></li>
            <li><a href="<?php echo BASE_PATH; ?>/auth/logout.php" class="logout-link">Logout</a></li>
        </ul>
    </aside>
    <div class="main">
        <div class="topbar">
            <div class="topbar-title">Customer Reserve</div>
            <div class="topbar-user">
                <?php $user = current_user(); echo safe_output($user['name'] ?? ''); ?>
            </div>
        </div>
        <div class="content">
            <?php if ($message): ?><div class="flash"><?php echo safe_output($message); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="flash"><?php echo safe_output($error); ?></div><?php endif; ?>

            <div class="box">
                <div class="box-header">
                    All Reservations
                    <span style="float:right; font-weight:normal;">
                        <a href="<?php echo BASE_PATH; ?>/admin/export.php?download=1">Export to Excel</a>
                    </span>
                </div>
                <div class="box-body">
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Hotel / Service</th>
                            <th>Date &amp; Time</th>
                            <th>Created By</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        <?php foreach ($reservations as $reservation): ?>
                            <tr>
                                <td><?php echo $reservation['id']; ?></td>
                                <td><?php echo safe_output($reservation['customer_name']); ?></td>
                                <td><?php echo safe_output($reservation['contact']); ?></td>
                                <td><?php echo safe_output($reservation['hotel_name']); ?></td>
                                <td><?php echo safe_output($reservation['reservation_time']); ?></td>
                                <td><?php echo safe_output($reservation['created_by']); ?> (<?php echo safe_output($reservation['created_role']); ?>)</td>
                                <td>
                                    <span class="status <?php echo safe_output($reservation['status_name']); ?>">
                                        <?php echo safe_output($reservation['status_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($reservation['status_name'] === 'pending'): ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit">Approve</button>
                                        </form>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="secondary">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <em>Handled</em>
                                    <?php endif; ?>
                                </td>
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
