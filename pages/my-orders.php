<?php
// pages/my-orders.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require '../config/dbcon.php';

$userId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'];

// Fetch customer orders
$stmt = $conn->prepare("
    SELECT o.tracking_no, o.weight_load, o.total_price, o.order_status, o.created_at, s.service_name 
    FROM laundry_orders o 
    JOIN services s ON o.service_id = s.service_id 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Orders | Lavadera Laundry Shop</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f4f8fb; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .navbar-custom { padding: 1.5rem 2rem; }
        .smooth-card {
            background-color: #ffffff; border-radius: 16px; padding: 1.5rem;
            border: none; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03); margin-bottom: 1rem;
        }
        .status-badge { padding: 6px 14px; border-radius: 50px; font-weight: 600; font-size: 0.8rem; }
        .status-Pending { background-color: #fff3cd; color: #856404; }
        .status-Washing { background-color: #cce5ff; color: #004085; }
        .status-Completed { background-color: #d4edda; color: #155724; }
    </style>
</head>
<body>

    <nav class="navbar navbar-custom d-flex justify-content-between align-items-center">
        <a href="home.php" class="text-decoration-none d-flex align-items-center">
            <span class="fs-5 fw-bold" style="color: #1e4f8f;"><i class="bi bi-droplet-half me-1"></i> Lavadera</span>
        </a>
        <div>
            <a href="home.php" class="btn btn-outline-secondary btn-sm rounded-pill px-4">Dashboard</a>
        </div>
    </nav>

    <main class="container py-4">
        <h3 class="fw-bold mb-4" style="color: #1a202c;">My Orders & Tracking</h3>
        
        <?php if(empty($orders)): ?>
            <div class="text-center py-5">
                <i class="bi bi-basket text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-muted">You have no laundry orders yet.</h5>
                <a href="home.php" class="btn btn-primary mt-2">Book Now</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach($orders as $order): 
                    $date = date('M d, Y h:i A', strtotime($order['created_at']));
                    $statusClass = 'status-' . str_replace(' ', '', $order['order_status']);
                ?>
                <div class="col-12 col-md-6 mb-3">
                    <div class="smooth-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted small">Tracking No: <strong><?= $order['tracking_no'] ?></strong></span>
                            <span class="status-badge <?= $statusClass ?>"><?= $order['order_status'] ?></span>
                        </div>
                        <h5 class="fw-bold mb-1" style="color: #273b7a;"><?= htmlspecialchars($order['service_name']) ?></h5>
                        <p class="text-muted small mb-3"><i class="bi bi-calendar3 me-1"></i> <?= $date ?></p>
                        <div class="d-flex justify-content-between border-top pt-3">
                            <span>Weight: <?= $order['weight_load'] ?> kg</span>
                            <span class="fw-bold">Total: ₱<?= number_format($order['total_price'], 2) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>