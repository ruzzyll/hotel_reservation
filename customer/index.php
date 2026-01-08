<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_role(['customer']);

$message = get_flash('message');
$error = get_flash('error');

/* Load hotels */
$hotels = $pdo->query("SELECT id, name, description FROM hotels ORDER BY name")->fetchAll();

/* Map status name => id */
$statusStmt = $pdo->query("SELECT id, name FROM reservation_status");
$statusMap = [];
foreach ($statusStmt->fetchAll() as $row) {
    $statusMap[$row['name']] = (int)$row['id'];
}

/* Handle reservation submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hotelId = (int)($_POST['hotel_id'] ?? 0);
    $reservationTime = $_POST['reservation_time'] ?? '';
    $guests = trim($_POST['guests'] ?? '');

    $customerName = trim($_POST['customer_name'] ?? (current_user()['name'] ?? ''));
    $contact = trim($_POST['contact'] ?? (current_user()['email'] ?? ''));

    if (!$hotelId || !$reservationTime) {
        flash('error', 'Hotel and reservation time are required.');
        redirect('/customer/index.php');
    }

    $stmt = $pdo->prepare("INSERT INTO customers (name, contact) VALUES (?, ?)");
    $stmt->execute([$customerName ?: 'Customer', $contact ?: '']);
    $customerId = (int)$pdo->lastInsertId();

    $pdo->prepare("
        INSERT INTO reservations (customer_id, hotel_id, user_id, reservation_time, status_id)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([
        $customerId,
        $hotelId,
        current_user()['id'],
        $reservationTime,
        $statusMap['pending'] ?? 1
    ]);

    log_action($pdo, current_user()['id'], "created reservation");
    flash('message', 'Reservation submitted and awaiting approval.');
    redirect('/customer/index.php');
}

/* Fetch user reservations */
$stmt = $pdo->prepare("
    SELECT r.id, r.reservation_time, r.created_at,
           h.name AS hotel_name, s.name AS status_name
    FROM reservations r
    JOIN hotels h ON r.hotel_id = h.id
    JOIN reservation_status s ON r.status_id = s.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([current_user()['id']]);
$reservations = $stmt->fetchAll();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Customer | Hotels</title>
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

<!-- AVAILABLE HOTELS -->
<h2>Available Hotels</h2>
<p>Select a hotel and click <strong>Reserve</strong>.</p>

<div class="hotel-listings">
<?php foreach ($hotels as $hotel): ?>
    <div class="hotel-card">
        <h3><?php echo safe_output($hotel['name']); ?></h3>
        <p><?php echo safe_output($hotel['description']); ?></p>
        <button class="btn-booking"
            onclick="openBookingModal(<?php echo (int)$hotel['id']; ?>)">
            Reserve
        </button>
    </div>
<?php endforeach; ?>
</div>

<!-- VIEW RESERVATIONS BUTTON -->
<div style="margin-top:20px; text-align:right;">
    <a href="<?php echo BASE_PATH; ?>/customer/reservations.php" class="btn-view-reservations">
        Your Reservations
    </a>
</div>

<!-- Reservations moved to dedicated page -->

</div>

<!-- BOOKING MODAL -->
<div id="bookingModal" class="booking-modal-overlay">
<div class="booking-modal">
    <div class="modal-header">
        <h3>Complete Reservation</h3>
        <button onclick="closeBookingModal()">×</button>
    </div>

    <form method="post">
        <input type="hidden" name="hotel_id" id="modal_hotel_id">

        <label>Your Name</label>
        <input type="text" name="customer_name" required>

        <label>Contact</label>
        <input type="text" name="contact" required>

        <label>Reservation Date & Time</label>
        <input type="datetime-local" name="reservation_time" required>

        <label>Guests</label>
        <input type="text" name="guests" value="2 adults, 1 room">

        <div class="modal-actions">
            <button type="button" onclick="closeBookingModal()">Cancel</button>
            <button type="submit">Confirm</button>
        </div>
    </form>
</div>
</div>

<script>
function openBookingModal(id) {
    document.getElementById('modal_hotel_id').value = id;
    document.getElementById('bookingModal').style.display = 'flex';
}
function closeBookingModal() {
    document.getElementById('bookingModal').style.display = 'none';
}
function toggleReservations() {
    const sec = document.getElementById('reservationsSection');
    if (!sec) return;
    sec.style.display = sec.style.display === 'none' ? 'block' : 'none';
    sec.scrollIntoView({ behavior: 'smooth' });
}
</script>

</body>
</html>
