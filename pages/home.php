<?php
// pages/home.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../login.php');
    exit;
}

require __DIR__ . '/../config/dbcon.php';

$userId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'Customer';

// 1. Bilangin ang Active Orders (Hindi kasama ang Completed, Delivered, at Cancelled)
$activeStmt = $conn->prepare("SELECT COUNT(*) as total FROM laundry_orders WHERE user_id = ? AND order_status NOT IN ('Completed', 'Delivered', 'Cancelled')");
$activeStmt->bind_param("i", $userId);
$activeStmt->execute();
$activeCount = $activeStmt->get_result()->fetch_assoc()['total'] ?? 0;

// 2. Bilangin ang Completed Orders (Sinama ang Completed at Delivered)
$completedStmt = $conn->prepare("SELECT COUNT(*) as total FROM laundry_orders WHERE user_id = ? AND order_status IN ('Completed', 'Delivered')");
$completedStmt->bind_param("i", $userId);
$completedStmt->execute();
$completedCount = $completedStmt->get_result()->fetch_assoc()['total'] ?? 0;

// 3. Kunin ang pinakabagong Active/Scheduled order
$currentOrderStmt = $conn->prepare("
    SELECT o.*, s.service_name 
    FROM laundry_orders o 
    JOIN services s ON o.service_id = s.service_id 
    WHERE o.user_id = ? AND o.order_status NOT IN ('Completed', 'Delivered', 'Cancelled') 
    ORDER BY o.created_at DESC LIMIT 1
");
$currentOrderStmt->bind_param("i", $userId);
$currentOrderStmt->execute();
$currentOrder = $currentOrderStmt->get_result()->fetch_assoc();

// 4. Kunin ang lahat ng active services
$services = [];
$servQuery = $conn->query("SELECT * FROM services WHERE status = 'active'");
if ($servQuery) {
    $services = $servQuery->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | Lavadera</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #111827; }
        .navbar-custom { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 14px 32px; }
        .nav-link { color: #475569; font-weight: 500; text-decoration: none; padding: 6px 12px; border-radius: 6px; }
        .nav-link:hover, .nav-link.active { color: #1e4f8f; background: #f1f5f9; font-weight: 600; }
        .hero-banner { background: linear-gradient(135deg, #1e4f8f 0%, #0f2c59 100%); border-radius: 16px; padding: 36px 40px; color: #fff; }
        .stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .service-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; transition: transform 0.2s; }
        .service-card:hover { transform: translateY(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        
        .text-black-num { color: #000000 !important; }
        .btn-black-outline { color: #111827; border-color: #111827; }
        .btn-black-outline:hover { background-color: #111827; color: #fff; border-color: #111827; }
        .text-dark-status { color: #111827 !important; font-weight: 700; }
    </style>
</head>
<body>

    <!-- Top Navbar -->
    <header class="navbar-custom d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-primary text-white fw-bold rounded d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">L</div>
            <span class="fw-bold fs-5 text-dark">Lavadera</span>
        </div>
        <nav class="d-flex align-items-center gap-3">
            <a href="home.php" class="nav-link active">Dashboard</a>
            <a href="orders.php" class="nav-link">My Orders</a>
            <a href="profile.php" class="nav-link">Profile Settings</a>
            <a href="../logout.php" class="btn btn-outline-dark btn-sm rounded-pill px-3 ms-2 fw-semibold">Sign out</a>
        </nav>
    </header>

    <main class="container py-4" style="max-width: 1100px;">
        
        <!-- Welcome Hero Banner -->
        <div class="hero-banner d-flex justify-content-between align-items-center mb-4">
            <div>
                <span class="text-uppercase small fw-semibold tracking-wide text-white-50">A fresh start, every time</span>
                <h1 class="fw-bold display-6 mb-2">Welcome back, <?= htmlspecialchars($fullName) ?>!</h1>
                <p class="mb-0 text-white-50 small">Book a wash, track your orders and get your time back.</p>
            </div>
            <a href="book.php" class="btn btn-light text-primary fw-semibold rounded-pill px-4 py-2 shadow-sm">Book Laundry →</a>
        </div>

        <!-- Stat Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <span class="text-muted small fw-semibold text-uppercase">Active Orders</span>
                    <h2 class="fw-bold text-black-num mt-2 mb-0"><?= $activeCount ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <span class="text-muted small fw-semibold text-uppercase">Completed Orders</span>
                    <h2 class="fw-bold text-black-num mt-2 mb-0"><?= $completedCount ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <span class="text-muted small fw-semibold text-uppercase">Next Pickup / Delivery</span>
                    <h5 class="fw-bold text-black-num mt-2 mb-0">
                        <?php if ($currentOrder && !empty($currentOrder['pickup_date'])): ?>
                            <?= date('M d, Y h:i A', strtotime($currentOrder['pickup_date'])) ?>
                        <?php else: ?>
                            No Schedule
                        <?php endif; ?>
                    </h5>
                </div>
            </div>
        </div>

        <!-- Current Order Tracker Section -->
        <?php if ($currentOrder): ?>
            <div class="stat-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fw-bold text-dark">Current order · #<?= htmlspecialchars($currentOrder['tracking_no']) ?></span>
                    <a href="orders.php" class="text-decoration-none small fw-semibold text-primary">View details</a>
                </div>
                
                <?php 
                    $status = $currentOrder['order_status'];
                    // Naayos ang mapping para sa mga tamang status ng admin workflow
                    $p1 = in_array($status, ['Pending', 'Confirmed', 'Washing', 'Drying', 'Ready for Pickup']) ? 'text-dark-status' : 'text-muted';
                    $p2 = in_array($status, ['Confirmed', 'Washing', 'Drying', 'Ready for Pickup']) ? 'text-dark-status' : 'text-muted';
                    $p3 = in_array($status, ['Washing', 'Drying', 'Ready for Pickup']) ? 'text-dark-status' : 'text-muted';
                    $p4 = in_array($status, ['Ready for Pickup']) ? 'text-dark-status' : 'text-muted';
                    $p5 = in_array($status, ['Delivered']) ? 'text-dark-status' : 'text-muted';
                ?>

                <div class="d-flex justify-content-between text-muted small border-top pt-3">
                    <span class="<?= $p1 ?>">Order placed</span>
                    <span class="<?= $p2 ?>">Confirmed</span>
                    <span class="<?= $p3 ?>">Washing / Drying</span>
                    <span class="<?= $p4 ?>">Ready</span>
                    <span class="<?= $p5 ?>">Delivered</span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Available Services Display -->
        <h4 class="fw-bold text-dark mb-3">Available services</h4>
        <div class="row g-3">
            <?php foreach ($services as $serv): ?>
                <div class="col-md-4">
                    <div class="service-card h-100 d-flex flex-column justify-content-between">
                        <div>
                            <h5 class="fw-bold text-dark"><?= htmlspecialchars($serv['service_name']) ?></h5>
                            <p class="text-muted small mb-3"><?= htmlspecialchars($serv['description'] ?? 'Professional laundry care service.') ?></p>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                            <span class="fw-bold text-black-num fs-5">₱<?= number_format($serv['price'], 2) ?> <small class="text-muted fs-6">/kg</small></span>
                            <a href="book.php?service_id=<?= $serv['service_id'] ?>" class="btn btn-black-outline btn-sm rounded-pill px-3 fw-semibold">Select</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>