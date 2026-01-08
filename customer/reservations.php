<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_role(['customer']);

$message = get_flash('message');
$error = get_flash('error');

/* Fetch user reservations */
$stmt = $pdo->prepare(
    "SELECT r.id, r.reservation_time, r.created_at,
           h.name AS hotel_name, s.name AS status_name
    FROM reservations r
    JOIN hotels h ON r.hotel_id = h.id
    JOIN reservation_status s ON r.status_id = s.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC"
);
$stmt->execute([current_user()['id']]);
$reservations = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Your Reservations</title>
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
</head>
<body class="customer-layout">

<header class="customer-header">
    <div class="header-content">
        <span class="user-name"><?php echo safe_output(current_user()['name'] ?? ''); ?></span>
        <a href="<?php echo BASE_PATH; ?>/auth/logout.php" class="logout-link">Logout</a>
    </div>
</header>

<div class="customer-main" style="padding:18px;">

<?php if ($message): ?><div class="flash success"><?php echo safe_output($message); ?></div><?php endif; ?>
<?php if ($error): ?><div class="flash error"><?php echo safe_output($error); ?></div><?php endif; ?>

<div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
    <h2>Your Reservations</h2>
    <div>
        <a href="<?php echo BASE_PATH; ?>/customer/index.php" class="btn-outline">Back to Hotels</a>
    </div>
 </div>

<?php if (empty($reservations)): ?>
    <p>No reservations found.</p>
<?php else: ?>
    <div style="margin-top:12px;">
    <?php foreach ($reservations as $r): ?>
        <div class="reservation-card">
            <h4><?php echo safe_output($r['hotel_name']); ?></h4>
            <p><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($r['reservation_time'])); ?></p>
            <p><strong>Submitted:</strong> <?php echo date('M d, Y h:i A', strtotime($r['created_at'])); ?></p>
            <p><strong>Status:</strong>
                <span class="status-badge status-<?php echo strtolower($r['status_name']); ?>">
                    <?php echo safe_output($r['status_name']); ?>
                </span>
            </p>

            <div style="margin-top:8px; display:flex; gap:8px;">
                <?php if (strtolower($r['status_name']) === 'pending'): ?>
                    <form method="post" action="<?php echo BASE_PATH; ?>/customer/cancel_reservation.php" onsubmit="return confirm('Cancel this reservation?');">
                        <input type="hidden" name="reservation_id" value="<?php echo (int)$r['id']; ?>">
                        <button type="submit" class="btn-danger">Cancel</button>
                    </form>
                <?php else: ?>
                    <form method="post" action="<?php echo BASE_PATH; ?>/customer/cancel_reservation.php" onsubmit="return confirm('Cancel this reservation?');">
                        <input type="hidden" name="reservation_id" value="<?php echo (int)$r['id']; ?>">
                        <button type="submit" class="btn-outline">Remove</button>
                    </form>
                <?php endif; ?>
            </div>

        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

</div>

</body>
</html>
