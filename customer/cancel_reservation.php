<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_role(['customer']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/customer/reservations.php');
}

$reservationId = (int)($_POST['reservation_id'] ?? 0);
if (!$reservationId) {
    flash('error', 'Invalid reservation.');
    redirect('/customer/reservations.php');
}

/* Verify reservation belongs to current user */
$stmt = $pdo->prepare("SELECT id FROM reservations WHERE id = ? AND user_id = ?");
$stmt->execute([$reservationId, current_user()['id']]);
$row = $stmt->fetch();
if (!$row) {
    flash('error', 'Reservation not found or permission denied.');
    redirect('/customer/reservations.php');
}

/* Delete the reservation */
$pdo->prepare("DELETE FROM reservations WHERE id = ? AND user_id = ?")->execute([$reservationId, current_user()['id']]);
log_action($pdo, current_user()['id'], "deleted reservation {$reservationId}");
flash('message', 'Reservation cancelled.');
redirect('/customer/reservations.php');
