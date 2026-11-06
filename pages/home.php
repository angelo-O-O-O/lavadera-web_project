<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../login.php');
    exit;
}

require __DIR__ . '/../config/dbcon.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['full_name'] ?? 'Customer';

// Fetch Active Orders Count
$activeOrdersCount = 0;
$actQuery = $conn->prepare("SELECT COUNT(*) as total FROM laundry_orders WHERE user_id = ? AND LOWER(order_status) != 'completed' AND LOWER(order_status) != 'cancelled'");
$actQuery->bind_param("i", $userId);
$actQuery->execute();
$activeOrdersCount = $actQuery->get_result()->fetch_assoc()['total'] ?? 0;

// Fetch Completed Orders Count
$completedOrdersCount = 0;
$compQuery = $conn->prepare("SELECT COUNT(*) as total FROM laundry_orders WHERE user_id = ? AND LOWER(order_status) = 'completed'");
$compQuery->bind_param("i", $userId);
$compQuery->execute();
$completedOrdersCount = $compQuery->get_result()->fetch_assoc()['total'] ?? 0;

// Fetch Latest Active/Current Order for Tracking
$currentOrder = null;
$currQuery = $conn->prepare("SELECT * FROM laundry_orders WHERE user_id = ? AND LOWER(order_status) != 'completed' AND LOWER(order_status) != 'cancelled' ORDER BY created_at DESC LIMIT 1");
$currQuery->bind_param("i", $userId);
$currQuery->execute();
$currentOrder = $currQuery->get_result()->fetch_assoc();

// Fetch Available Services
$services = [];
$servQuery = $conn->query("SELECT * FROM services WHERE status = 'active' LIMIT 3");
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
        .welcome-banner { background: linear-gradient(135deg, #1e4f8f 0%, #0f2c59 100%); color: #fff; border-radius: 16px; padding: 32px; margin-bottom: 24px; }
        .stat-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .service-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between; }
        .progress-track { height: 6px; background: #e2e8f0; border-radius: 99px; position: relative; margin: 16px 0; }
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
            <a href="dashboard.php" class="nav-link active">Dashboard</a>
            <a href="orders.php" class="nav-link">My Orders</a>
            <a href="profile.php" class="nav-link">Profile Settings</a>
            <a href="../logout.php" class="btn btn-outline-dark btn-sm rounded-pill px-3 ms-2 fw-semibold">Sign out</a>
        </nav>
    </header>

    <main class="container py-4" style="max-width: 1100px;">
        
        <!-- Welcome Banner -->
        <div class="welcome-banner d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="text-uppercase small fw-bold tracking-wider" style="opacity: 0.8; font-size: 0.7rem;">A FRESH START, EVERY TIME</span>
                <h1 class="fw-bold fs-2 mb-1">Welcome back, <?= htmlspecialchars($userName) ?>!</h1>
                <p class="mb-0 text-white-50 small">Book a wash, track your orders and get your time back.</p>
            </div>
            <a href="book.php" class="btn btn-light fw-bold px-4 py-2 rounded-pill shadow-sm" style="color: #1e4f8f;">Book Laundry →</a>
        </div>

        <!-- Metric Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Active orders</span>
                    <h3 class="fw-bold fs-2 mt-2 mb-0 text-dark"><?= $activeOrdersCount ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Completed orders</span>
                    <h3 class="fw-bold fs-2 mt-2 mb-0 text-dark"><?= $completedOrdersCount ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <span class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem;">Next pickup / delivery</span>
                    <h5 class="fw-bold mt-2 mb-0 text-secondary"><?= $currentOrder ? 'Scheduled' : 'None scheduled' ?></h5>
                </div>
            </div>
        </div>

        <!-- Current Order Progress Section -->
        <?php if ($currentOrder): ?>
        <div class="stat-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-dark mb-0">Current order · #<?= htmlspecialchars($currentOrder['tracking_no']) ?></h6>
                <a href="order-details.php?id=<?= $currentOrder['order_id'] ?>" class="text-decoration-none fw-semibold small text-primary">View details</a>
            </div>
            <div class="progress-track"></div>
            <div class="d-flex justify-content-between text-muted" style="font-size: 0.75rem; font-weight: 500;">
                <span class="text-primary fw-bold">Order placed</span>
                <span>Picked up</span>
                <span>Washing</span>
                <span>Ready</span>
                <span>Delivered</span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Available Services Grid -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark mb-0">Available services</h5>
            <span class="badge bg-light text-secondary border px-2 py-1" style="font-size: 0.75rem;">Per-kg calculation</span>
        </div>

        <div class="row g-3">
            <?php if (empty($services)): ?>
                <div class="col-12 text-center text-muted py-4">No active services available at the moment.</div>
            <?php else: ?>
                <?php foreach ($services as $serv): ?>
                <div class="col-md-4">
                    <div class="service-card h-100">
                        <div>
                            <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($serv['service_name']) ?></h6>
                            <p class="text-muted small mb-3" style="font-size: 0.85rem;"><?= htmlspecialchars($serv['description'] ?? 'Standard professional laundry care.') ?></p>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="fw-bold fs-5 text-dark">₱<?= number_format($serv['price'], 2) ?></span>
                                <span class="badge bg-light text-muted border" style="font-size: 0.7rem;"><?= htmlspecialchars($serv['pricing_unit'] ?? 'Fixed') ?></span>
                            </div>
                            <a href="book.php?service_id=<?= $serv['service_id'] ?>" class="btn btn-primary w-100 fw-semibold rounded-3 shadow-none" style="background-color: #1e4f8f; border-color: #1e4f8f;">Select</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>