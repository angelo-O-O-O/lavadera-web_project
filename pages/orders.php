<?php
// pages/orders.php
session_start();

if (!isset($_SESSION['user_id']) || (isset($_SESSION['role']) && $_SESSION['role'] !== 'customer')) {
    header('Location: ../login.php');
    exit;
}

require __DIR__ . '/../config/dbcon.php';

$userId = $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'Customer';

// Fetch customer orders
$ordersStmt = $conn->prepare("
    SELECT o.*, s.service_name 
    FROM laundry_orders o 
    JOIN services s ON o.service_id = s.service_id 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
");
$ordersStmt->bind_param("i", $userId);
$ordersStmt->execute();
$orders = $ordersStmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Orders | Lavadera</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #111827; }
        .navbar-custom { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 14px 32px; }
        .nav-link { color: #475569; font-weight: 500; text-decoration: none; padding: 6px 12px; border-radius: 6px; }
        .nav-link:hover, .nav-link.active { color: #1e4f8f; background: #f1f5f9; font-weight: 600; }
        .content-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); padding: 24px; }
        .table tbody tr { transition: background-color 0.1s ease-in-out; }
        .table tbody tr:hover { background-color: #fcfdfe; }
    </style>
</head>
<body>

    <!-- Top Navbar -->
    <header class="navbar-custom d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <div class="bg-primary text-white fw-bold rounded d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">L</div>
            <span class="fw-bold fs-5 text-dark">Lavadera</span>
        </div>
        <nav class="d-flex align-items-center gap-2">
            <a href="home.php" class="nav-link">Dashboard</a>
            <a href="orders.php" class="nav-link active">My Orders</a>
            <a href="profile.php" class="nav-link">Profile Settings</a>
            <a href="../logout.php" class="btn btn-outline-dark btn-sm rounded-pill px-3 ms-2 fw-semibold">Sign out</a>
        </nav>
    </header>

    <main class="container py-4" style="max-width: 1050px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">My Orders</h3>
                <p class="text-muted small mb-0">Track the real-time status and history of your laundry bookings.</p>
            </div>
            <a href="book.php" class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold">
                + New Booking
            </a>
        </div>

        <div class="content-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr class="text-muted small text-uppercase border-bottom">
                            <th class="py-3">Tracking No</th>
                            <th class="py-3">Service</th>
                            <th class="py-3">Weight</th>
                            <th class="py-3">Total Amount</th>
                            <th class="py-3">Status</th>
                            <th class="py-3 text-end">Date Booked</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orders) > 0): ?>
                            <?php foreach ($orders as $ord): ?>
                                <?php 
                                    $st = $ord['order_status'];
                                    $badgeStyle = 'bg-light text-secondary border';
                                    if ($st === 'Completed' || $st === 'Delivered') $badgeStyle = 'bg-success bg-opacity-10 text-success border border-success border-opacity-25';
                                    elseif ($st === 'Washing' || $st === 'Drying') $badgeStyle = 'bg-info bg-opacity-10 text-info border border-info border-opacity-25';
                                    elseif ($st === 'Ready for Pickup') $badgeStyle = 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25';
                                    elseif ($st === 'Pending' || $st === 'Confirmed') $badgeStyle = 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25';
                                ?>
                                <tr>
                                    <td class="fw-bold text-primary">#<?= htmlspecialchars($ord['tracking_no']) ?></td>
                                    <td>
                                        <div class="fw-medium text-dark"><?= htmlspecialchars($ord['service_name']) ?></div>
                                        <?php if (!empty($ord['machine_assigned'])): ?>
                                            <div class="text-muted small"><?= htmlspecialchars($ord['machine_assigned']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= number_format($ord['weight'] ?? 1.0, 2) ?> kg</td>
                                    <td class="fw-bold text-dark">₱<?= number_format($ord['total_price'], 2) ?></td>
                                    <td>
                                        <span class="badge <?= $badgeStyle ?> px-2 py-1">
                                            <?= htmlspecialchars($st) ?>
                                        </span>
                                    </td>
                                    <td class="text-end text-muted small"><?= date('M d, Y h:i A', strtotime($ord['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No laundry orders found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>