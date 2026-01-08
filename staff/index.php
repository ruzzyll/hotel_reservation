<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_role(['staff', 'admin']);

$message = get_flash('message');
$error = get_flash('error');

// Load hotels/services with full details
$hotels = $pdo->query("SELECT id, name, description FROM hotels ORDER BY name")->fetchAll();
$statusStmt = $pdo->query("SELECT id, name FROM reservation_status");
$statusMap = [];
foreach ($statusStmt->fetchAll() as $row) {
    $statusMap[$row['name']] = (int)$row['id'];
}

// Handle booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim($_POST['customer_name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $hotelId = (int)($_POST['hotel_id'] ?? 0);
    $reservationTime = $_POST['reservation_time'] ?? '';
    
    $guests = trim($_POST['guests'] ?? '2 adults, 1 room');

    if (!$customerName || !$contact || !$hotelId || !$reservationTime) {
        flash('error', 'All fields are required.');
        redirect('/staff/index.php');
    }

    // Create or reuse customer
    $stmt = $pdo->prepare("INSERT INTO customers (name, contact) VALUES (?, ?)");
    $stmt->execute([$customerName, $contact]);
    $customerId = (int)$pdo->lastInsertId();

    $pdo->prepare("INSERT INTO reservations (customer_id, hotel_id, user_id, reservation_time, status_id) VALUES (?, ?, ?, ?, ?)")
        ->execute([$customerId, $hotelId, current_user()['id'], $reservationTime, $statusMap['pending'] ?? 1]);

    log_action($pdo, current_user()['id'], "created reservation for {$customerName}");
    flash('message', 'Reservation submitted and awaiting approval.');
    redirect('/staff/index.php');
}

// Fetch current user reservations
$stmt = $pdo->prepare("
    SELECT r.id, r.reservation_time, r.created_at, h.name AS hotel_name,
           s.name AS status_name
    FROM reservations r
    JOIN hotels h ON r.hotel_id = h.id
    JOIN reservation_status s ON r.status_id = s.id
    WHERE r.user_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([current_user()['id']]);
$reservations = $stmt->fetchAll();

// Get filter/search params
$guestsFilter = $_GET['guests'] ?? '2 adults, 1 room';
$priceMin = isset($_GET['price_min']) ? (int)$_GET['price_min'] : 0;
$priceMax = isset($_GET['price_max']) ? (int)$_GET['price_max'] : 50000;
$sortBy = $_GET['sort'] ?? 'default';
$breakfastIncluded = isset($_GET['breakfast']) && $_GET['breakfast'] == '1';
$budgetHotel = isset($_GET['budget']) && $_GET['budget'] == '1';

// Default nights (since dates are removed)
$nights = 3;

// Filter hotels (no destination filtering)
$filteredHotels = $hotels;

// Room images - Replace with your actual image URLs
// Standard Room: Modern room with mint green walls and large bed
$standardRoomImage = 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=600&h=400&fit=crop';
// Deluxe Room: Mid-century modern with wooden slatted wall and blue accents
$deluxeRoomImage = 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=600&h=400&fit=crop';
// Premium Room: Twin beds hotel room with warm beige/cream tones, cozy decor, and sailboat artwork
// Place your premium room image file in assets/images/ and name it premium-room-twin-beds.jpg
// If the file exists, use it; otherwise, use the fallback URL
$premiumRoomImagePath = __DIR__ . '/../assets/images/premium-room-twin-beds.jpg';
if (file_exists($premiumRoomImagePath)) {
    $premiumRoomImage = BASE_PATH . '/assets/images/premium-room-twin-beds.jpg';
} else {
    // Fallback to a working twin beds hotel room image (warm beige/cream tones, cozy decor)
    // Using a reliable image URL for twin beds hotel room
    $premiumRoomImage = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&h=400&fit=crop';
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Reservation - Book Your Stay</title>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/style.css">
</head>
<body class="customer-layout">
    <!-- Top Header -->
    <header class="customer-header">
<div class="header-content">
            <span class="user-name"><?php $user = current_user(); echo safe_output($user['name'] ?? ''); ?></span>
            <div class="header-actions">
                <a href="<?php echo BASE_PATH; ?>/auth/logout.php" class="logout-link" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #ffffff; color: #4A90E2; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: 500; border: 1px solid #4A90E2; transition: all 0.2s ease; cursor: pointer;"
                   onmouseover="this.style.background='#f8f9fa'; this.style.borderColor='#357ABD'; this.style.color='#357ABD';"
                   onmouseout="this.style.background='#ffffff'; this.style.borderColor='#4A90E2'; this.style.color='#4A90E2';"
                   onmousedown="this.style.background='#e9ecef';"
                   onmouseup="this.style.background='#f8f9fa';">Logout</a>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <div class="customer-main">
        <!-- Left Sidebar - Search & Filters -->
        <aside class="customer-sidebar">
            <div class="search-section">
                <div class="search-header">
                    <span class="back-icon">←</span>
                    <h3>Your search</h3>
                </div>
                <form method="get" class="search-form">
                    <div class="search-field">
                        <span class="field-icon">👤</span>
                        <input type="text" name="guests" placeholder="Guests" value="<?php echo safe_output($guestsFilter); ?>">
                    </div>
                    <button type="submit" class="btn-search">Search</button>
                </form>
            </div>

            <div class="filters-section">
                <div class="filter-header">
                    <h4>Popular filters</h4>
                    <a href="<?php echo BASE_PATH; ?>/staff/index.php" class="reset-link">Reset</a>
                </div>
                <form method="get" id="filterForm">
                    <?php if ($guestsFilter): ?><input type="hidden" name="guests" value="<?php echo safe_output($guestsFilter); ?>"><?php endif; ?>
                    <div class="filter-checkboxes">
                        <label><input type="checkbox" name="budget" value="1" <?php echo $budgetHotel ? 'checked' : ''; ?> onchange="this.form.submit()"> Budget hotel</label>
                        <label><input type="checkbox" name="breakfast" value="1" <?php echo $breakfastIncluded ? 'checked' : ''; ?> onchange="this.form.submit()"> Breakfast included</label>
                    </div>
                    <input type="hidden" name="price_min" id="price_min" value="<?php echo $priceMin; ?>">
                    <input type="hidden" name="price_max" id="price_max" value="<?php echo $priceMax; ?>">
                </form>
            </div>

            <div class="filters-section">
                <h4>Price per night</h4>
                <div class="price-range">
                    <input type="range" min="0" max="5000" value="<?php echo min(5000, max(0, $priceMax / $nights)); ?>" class="price-slider" id="priceSlider">
                    <div class="price-display">₱0 - ₱<span id="priceDisplay"><?php echo number_format($priceMax / $nights); ?></span> per night</div>
                </div>
            </div>
        </aside>

        <!-- Right Content - Hotel Listings -->
        <main class="customer-content">
            <?php if ($message): ?><div class="flash success"><?php echo safe_output($message); ?></div><?php endif; ?>
            <?php if ($error): ?><div class="flash error"><?php echo safe_output($error); ?></div><?php endif; ?>

            <div class="results-header">
                <div class="results-info">
                    <p><?php echo count($filteredHotels); ?> filtered results</p>
                    <h2>Available Hotels</h2>
                </div>
                <form method="get" style="display: inline;">
                    <?php if ($guestsFilter): ?><input type="hidden" name="guests" value="<?php echo safe_output($guestsFilter); ?>"><?php endif; ?>
                    <select name="sort" class="sort-select" onchange="this.form.submit()">
                        <option value="default" <?php echo $sortBy == 'default' ? 'selected' : ''; ?>>Sort by</option>
                        <option value="price_low" <?php echo $sortBy == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_high" <?php echo $sortBy == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="rating" <?php echo $sortBy == 'rating' ? 'selected' : ''; ?>>Rating</option>
                    </select>
                </form>
            </div>

            <div class="hotel-listings">
                <?php 
                // Prepare hotel data with prices for sorting
                $hotelsWithData = [];
                foreach ($filteredHotels as $index => $hotel):
                    // Generate mock data for demo
                    $distance = [0.4, 0.6, 2.0][$index % 3] ?? 1.0;
                    $rating = [9.6, 8.3, 9.5][$index % 3] ?? 8.5;
                    $reviews = [1920, 792, 2000][$index % 3] ?? 1000;
                    $isHotDeal = $index % 2 === 0;
                    $isPopular = $index % 3 === 0;
                    
                    // Room and bed information
                    $roomTypes = ['Standard Room', 'Deluxe Room', 'Premium Room'];
                    $bedTypes = ['King Size Bed', 'Queen Size Bed', 'Twin Beds'];
                    $availableRooms = [5, 8, 3][$index % 3] ?? 5;
                    $availableBeds = [10, 16, 6][$index % 3] ?? 10;
                    $roomType = $roomTypes[$index % 3];
                    $bedType = $bedTypes[$index % 3];
                    
                    // Calculate price based on room type and availability
                    // Base price per room per night: Standard ₱1500, Deluxe ₱2000, Premium ₱3000
                    $basePricePerNight = [1500, 2000, 3000][$index % 3] ?? 1500;
                    // Use calculated nights from check-in/check-out dates
                    $calculatedNights = $nights;
                    
                    // Price calculation: base price × nights
                    // If more rooms available, slight discount (5% per extra room above 3)
                    $discount = 0;
                    if ($availableRooms > 3) {
                        $discount = min(20, ($availableRooms - 3) * 5); // Max 20% discount
                    }
                    $pricePerNight = $basePricePerNight * (1 - $discount / 100);
                    $price = round($pricePerNight * $calculatedNights);
                    
                    // Apply price filter
                    if ($price < $priceMin || $price > $priceMax) {
                        continue;
                    }
                    
                    $hotelsWithData[] = [
                        'hotel' => $hotel,
                        'index' => $index,
                        'distance' => $distance,
                        'rating' => $rating,
                        'reviews' => $reviews,
                        'isHotDeal' => $isHotDeal,
                        'isPopular' => $isPopular,
                        'roomType' => $roomType,
                        'bedType' => $bedType,
                        'availableRooms' => $availableRooms,
                        'availableBeds' => $availableBeds,
                        'price' => $price,
                        'pricePerNight' => $pricePerNight,
                        'nights' => $calculatedNights
                    ];
                endforeach;
                
                // Sort hotels
                if ($sortBy == 'price_low') {
                    usort($hotelsWithData, function($a, $b) { return $a['price'] - $b['price']; });
                } elseif ($sortBy == 'price_high') {
                    usort($hotelsWithData, function($a, $b) { return $b['price'] - $a['price']; });
                } elseif ($sortBy == 'rating') {
                    usort($hotelsWithData, function($a, $b) { return $b['rating'] <=> $a['rating']; });
                }
                
                foreach ($hotelsWithData as $hotelData):
                    $hotel = $hotelData['hotel'];
                    $index = $hotelData['index'];
                    $distance = $hotelData['distance'];
                    $rating = $hotelData['rating'];
                    $reviews = $hotelData['reviews'];
                    $isHotDeal = $hotelData['isHotDeal'];
                    $isPopular = $hotelData['isPopular'];
                    $roomType = $hotelData['roomType'];
                    $bedType = $hotelData['bedType'];
                    $availableRooms = $hotelData['availableRooms'];
                    $availableBeds = $hotelData['availableBeds'];
                    $price = $hotelData['price'];
                    $pricePerNight = $hotelData['pricePerNight'];
                    $calculatedNights = $hotelData['nights'];
                ?>
                <div class="hotel-card">
                    <div class="hotel-image">
                        <?php 
                        $roomImageIndex = $index % 3;
                        if ($roomImageIndex == 0): // Standard Room
                            $roomImage = $standardRoomImage;
                        elseif ($roomImageIndex == 1): // Deluxe Room
                            $roomImage = $deluxeRoomImage;
                        else: // Premium Room
                            $roomImage = $premiumRoomImage;
                        endif;
                        ?>
                        <img src="<?php echo $roomImage; ?>" alt="<?php echo safe_output($roomType); ?>">
                    </div>
                    <div class="hotel-details">
                        <div class="hotel-header">
                            <h3><?php echo safe_output($roomType); ?></h3>
                            <div class="hotel-rating">
                                <span class="rating-badge excellent"><?php echo $rating; ?></span>
                                <span class="reviews"><?php echo number_format($reviews); ?> reviews</span>
                            </div>
                        </div>
                        <div class="hotel-features">
                            <span>Free cancellation</span>
                            <span>Breakfast included</span>
                        </div>
                        <div class="hotel-room">
                            <p><strong>Available Rooms: <?php echo $availableRooms; ?> rooms</strong></p>
                            <p><strong>Available Beds: <?php echo $availableBeds; ?> beds</strong></p>
                            <p><?php echo $bedType; ?> • 1x bathroom</p>
                        </div>
                        <div class="hotel-tags">
                            <?php if ($isHotDeal): ?><span class="tag hot-deal">#Hot deal</span><?php endif; ?>
                            <?php if ($isPopular): ?><span class="tag popular">#Popular</span><?php endif; ?>
                        </div>
                        <div class="hotel-footer">
                            <div class="hotel-price">
                                <span class="price">₱<?php echo number_format($price); ?></span>
                                <span class="price-note"><?php echo $calculatedNights; ?> night<?php echo $calculatedNights > 1 ? 's' : ''; ?>, <?php echo safe_output($guestsFilter); ?></span>
                            </div>
                            <button class="btn-booking" onclick="openBookingModal(<?php echo $hotel['id']; ?>, '<?php echo safe_output($roomType); ?>', <?php echo $price; ?>)">See booking options</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($hotelsWithData)): ?>
                    <div class="no-results">
                        <p>No hotels found matching your criteria. Try adjusting your filters.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- User Reservations Section -->
            <?php if (!empty($reservations)): ?>
            <div class="reservations-section">
                <h2>Your Reservations</h2>
                <div class="reservations-list">
                    <?php foreach ($reservations as $reservation): ?>
                    <div class="reservation-card">
                        <div class="reservation-info">
                            <h4><?php echo safe_output($reservation['hotel_name']); ?></h4>
                            <p><strong>Reservation Date:</strong> <?php echo date('M d, Y h:i A', strtotime($reservation['reservation_time'])); ?></p>
                            <p><strong>Created:</strong> <?php echo date('M d, Y', strtotime($reservation['created_at'])); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="status-badge status-<?php echo strtolower($reservation['status_name']); ?>">
                                    <?php echo safe_output($reservation['status_name']); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Booking Modal -->
    <div id="bookingModal" class="booking-modal-overlay">
        <div class="booking-modal">
            <div class="modal-header">
                <h3>Complete Your Reservation</h3>
                <button class="modal-close" onclick="closeBookingModal()">×</button>
            </div>
            <form method="post" class="booking-form">
                <input type="hidden" name="hotel_id" id="modal_hotel_id">
                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" name="customer_name" required>
                </div>
                <div class="form-group">
                    <label>Contact</label>
                    <input type="text" name="contact" required>
                </div>
                
                <div class="form-group">
                    <label>Reservation Date & Time</label>
                    <input type="datetime-local" name="reservation_time" id="modal_reservation_time" value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Guests</label>
                    <input type="text" name="guests" id="modal_guests" value="<?php echo safe_output($guestsFilter); ?>" required>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeBookingModal()">Cancel</button>
                    <button type="submit" class="btn-confirm">Confirm Reservation</button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?php echo BASE_PATH; ?>/assets/js/app.js"></script>
    <script>
        function openBookingModal(hotelId, hotelName, price) {
            document.getElementById('modal_hotel_id').value = hotelId;
            // Pre-fill guests from search form
            const guests = document.querySelector('input[name="guests"]')?.value || '<?php echo $guestsFilter; ?>';
            if (document.getElementById('modal_guests')) document.getElementById('modal_guests').value = guests;
            document.getElementById('bookingModal').style.display = 'flex';
        }
        function closeBookingModal() {
            document.getElementById('bookingModal').style.display = 'none';
        }
        // Close modal on overlay click
        document.getElementById('bookingModal').addEventListener('click', function(e) {
            if (e.target === this) closeBookingModal();
        });
        
        // Price slider functionality
        const priceSlider = document.getElementById('priceSlider');
        const priceDisplay = document.getElementById('priceDisplay');
        const priceMinInput = document.getElementById('price_min');
        const priceMaxInput = document.getElementById('price_max');
        const filterForm = document.getElementById('filterForm');
        const nights = <?php echo $nights; ?>;
        
        if (priceSlider && priceDisplay) {
            priceSlider.addEventListener('input', function() {
                const maxPrice = parseInt(this.value);
                priceDisplay.textContent = maxPrice.toLocaleString();
                priceMaxInput.value = maxPrice * nights;
            });
            
            // Submit form when slider is released
            priceSlider.addEventListener('change', function() {
                filterForm.submit();
            });
        }
    </script>
</body>
</html>
