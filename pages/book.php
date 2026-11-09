<?php
// pages/book.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../login.php');
    exit;
}

require __DIR__ . '/../config/dbcon.php';

$userId = $_SESSION['user_id'];
$successMsg = "";
$errorMsg = "";

$selectedServiceId = $_GET['service_id'] ?? '';

// Kunin ang lahat ng active services na galing sa admin
$services = [];
$servQuery = $conn->query("SELECT * FROM services WHERE status = 'active'");
if ($servQuery) {
    $services = $servQuery->fetch_all(MYSQLI_ASSOC);
}

// Handle Laundry Booking Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceId = intval($_POST['service_id']);
    $weight = floatval($_POST['weight'] ?? 1.0);
    $notes = trim($_POST['notes'] ?? '');
    $pickupDateOnly = trim($_POST['pickup_date'] ?? '');
    $pickupTimeOnly = trim($_POST['pickup_time'] ?? '');

    $pickupDate = $pickupDateOnly . ' ' . $pickupTimeOnly . ':00';

    if ($serviceId > 0 && $weight > 0 && !empty($pickupDateOnly) && !empty($pickupTimeOnly)) {
        $priceStmt = $conn->prepare("SELECT price FROM services WHERE service_id = ? AND status = 'active'");
        $priceStmt->bind_param("i", $serviceId);
        $priceStmt->execute();
        $serviceData = $priceStmt->get_result()->fetch_assoc();

        if ($serviceData) {
            $pricePerKg = $serviceData['price'];
            $totalPrice = $pricePerKg * $weight;
            
            $trackingNo = 'LAV-' . strtoupper(substr(uniqid(), -6));
            $status = 'Pending';

            $insertStmt = $conn->prepare("
                INSERT INTO laundry_orders (user_id, service_id, weight, tracking_no, total_price, notes, pickup_date, order_status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $insertStmt->bind_param("iidsdsss", $userId, $serviceId, $weight, $trackingNo, $totalPrice, $notes, $pickupDate, $status);

            if ($insertStmt->execute()) {
                $successMsg = "Laundry booked successfully! Tracking No: #" . $trackingNo . " | Total: ₱" . number_format($totalPrice, 2);
            } else {
                $errorMsg = "Failed to submit booking. Please try again.";
            }
        } else {
            $errorMsg = "Ang napiling serbisyo ay hindi na available o inactive.";
        }
    } else {
        $errorMsg = "Mangyaring punan ang lahat ng kinakailangang impormasyon.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Book Laundry | Lavadera</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #111827; }
        .navbar-custom { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 14px 32px; }
        .nav-link { color: #475569; font-weight: 500; text-decoration: none; padding: 6px 12px; border-radius: 6px; }
        .nav-link:hover, .nav-link.active { color: #1e4f8f; background: #f1f5f9; font-weight: 600; }
        .content-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); padding: 32px; }
        
        /* I-hide ang default time dashes bago mag-focus o mamili ang user para mas malinis tingnan */
        input[type="time"]::-webkit-datetime-edit-fields-wrapper {
            display: flex;
        }
    </style>
</head>
<body>

    <header class="navbar-custom d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-primary text-white fw-bold rounded d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">L</div>
            <span class="fw-bold fs-5 text-dark">Lavadera</span>
        </div>
        <nav class="d-flex align-items-center gap-3">
            <a href="home.php" class="nav-link">Dashboard</a>
            <a href="orders.php" class="nav-link">My Orders</a>
            <a href="profile.php" class="nav-link">Profile Settings</a>
            <a href="../logout.php" class="btn btn-outline-dark btn-sm rounded-pill px-3 ms-2 fw-semibold">Sign out</a>
        </nav>
    </header>

    <main class="container py-4" style="max-width: 800px;">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">Book Laundry Service</h3>
                <p class="text-muted small mb-0">Fill in your laundry details. Price will auto-calculate based on weight.</p>
            </div>
            <a href="home.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold shadow-none">← Back to Dashboard</a>
        </div>

        <?php if (!empty($successMsg)): ?>
            <div id="alertMessage" class="alert alert-success alert-dismissible fade show rounded-3" role="alert">
                <?= htmlspecialchars($successMsg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
            <div id="alertMessage" class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                <?= htmlspecialchars($errorMsg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <form method="POST" action="book.php">
                <div class="mb-3">
                    <label for="service_id" class="form-label fw-semibold small text-muted">Select Laundry Service</label>
                    <select class="form-select shadow-none" id="service_id" name="service_id" required onchange="calculateTotal()">
                        <option value="" disabled selected data-price="0">Choose a service...</option>
                        <?php foreach ($services as $serv): ?>
                            <option value="<?= $serv['service_id'] ?>" data-price="<?= $serv['price'] ?>" <?= ($selectedServiceId == $serv['service_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($serv['service_name']) ?> (₱<?= number_format($serv['price'], 2) ?> / kg)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="weight" class="form-label fw-semibold small text-muted">Weight (kg)</label>
                    <input type="number" step="0.5" min="1" class="form-control shadow-none" id="weight" name="weight" value="1.0" required oninput="calculateTotal()">
                    <small class="text-muted" style="font-size: 0.75rem;">Minimum of 1 kg.</small>
                </div>

                <div class="mb-3 p-3 bg-light rounded-3 border d-flex justify-content-between align-items-center">
                    <span class="fw-semibold text-muted small">Estimated Total Amount:</span>
                    <span class="fw-bold fs-6 text-dark" id="displayTotal">₱0.00</span>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="pickup_date" class="form-label fw-semibold small text-muted">Preferred Pickup Date</label>
                        <input type="date" class="form-control shadow-none" id="pickup_date" name="pickup_date" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="pickup_time" class="form-label fw-semibold small text-muted">Preferred Pickup Time</label>
                        <!-- Ginagamit ang text type muna para malinis ang placeholder, tapos magiging time picker kapag clinick -->
                        <input type="text" class="form-control shadow-none" id="pickup_time" name="pickup_time" placeholder="Select time" onfocus="this.type='time'" onblur="if(!this.value)this.type='text'" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="notes" class="form-label fw-semibold small text-muted">Special Instructions / Notes (Optional)</label>
                    <textarea class="form-control shadow-none" id="notes" name="notes" rows="3" placeholder="E.g., Separate whites from colored clothes..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary px-4 fw-semibold rounded-pill shadow-none" style="background-color: #1e4f8f; border-color: #1e4f8f;">Confirm Booking</button>
            </form>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function calculateTotal() {
            const serviceSelect = document.getElementById('service_id');
            const weightInput = document.getElementById('weight');
            const displayTotal = document.getElementById('displayTotal');

            const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
            const pricePerKg = parseFloat(selectedOption.getAttribute('data-price')) || 0;
            const weight = parseFloat(weightInput.value) || 0;

            const total = pricePerKg * weight;
            displayTotal.innerText = '₱' + total.toFixed(2);
        }

        window.onload = calculateTotal;

        setTimeout(function() {
            const alertEl = document.getElementById('alertMessage');
            if (alertEl) {
                let bsAlert = new bootstrap.Alert(alertEl);
                bsAlert.close();
            }
        }, 3000);
    </script>
</body>
</html>