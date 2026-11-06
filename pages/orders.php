<?php
// pages/orders.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../login.php');
    exit;
}

require __DIR__ . '/../config/dbcon.php';

$userId = $_SESSION['user_id'];

// Handle Cancel Request
if (isset($_POST['cancel_order_id'])) {
    $orderIdToCancel = intval($_POST['cancel_order_id']);
    
    // I-update lang ang status kung ang order ay sa customer na ito at naka-Pending pa
    $cancelStmt = $conn->prepare("UPDATE laundry_orders SET order_status = 'Cancelled' WHERE order_id = ? AND user_id = ? AND LOWER(order_status) = 'pending'");
    $cancelStmt->bind_param("ii", $orderIdToCancel, $userId);
    $cancelStmt->execute();
    
    header("Location: orders.php");
    exit;
}

// Fetch all orders for this customer
$orders = [];
$stmt = $conn->prepare("
    SELECT o.*, s.service_name 
    FROM laundry_orders o
    LEFT JOIN services s ON o.service_id = s.service_id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $orders = $result->fetch_all(MYSQLI_ASSOC);
}
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
        .content-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden; }
        .status-badge { padding: 5px 12px; border-radius: 50px; font-weight: 600; font-size: 0.75rem; display: inline-block; }
        .status-Pending { background-color: #fff3cd; color: #856404; }
        .status-Confirmed, .status-Washing, .status-Drying, .status-Folding, .status-ReadyforPickup { background-color: #cce5ff; color: #004085; }
        .status-Completed { background-color: #d4edda; color: #155724; }
        .status-Cancelled { background-color: #f8d7da; color: #721c24; }
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
            <a href="home.php" class="nav-link">Dashboard</a>
            <a href="orders.php" class="nav-link active">My Orders</a>
            <a href="profile.php" class="nav-link">Profile Settings</a>
            <a href="../logout.php" class="btn btn-outline-dark btn-sm rounded-pill px-3 ms-2 fw-semibold">Sign out</a>
        </nav>
    </header>

    <main class="container py-4" style="max-width: 1100px;">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-1">My Laundry Orders</h3>
                <p class="text-muted small mb-0">Track and view history of all your laundry transactions.</p>
            </div>
            <a href="home.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold shadow-none">← Back to Dashboard</a>
        </div>

        <div class="content-card">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead class="bg-light text-uppercase text-muted" style="font-size: 0.75rem;">
                        <tr>
                            <th class="py-3 px-4">Tracking No</th>
                            <th class="py-3 px-4">Service</th>
                            <th class="py-3 px-4">Total Amount</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">You have no laundry orders yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $ord): 
                                $statusClass = 'status-' . str_replace(' ', '', $ord['order_status']);
                                $isPending = (strtolower($ord['order_status']) === 'pending');
                            ?>
                            <tr>
                                <td class="py-3 px-4"><strong class="text-primary">#<?= htmlspecialchars($ord['tracking_no']) ?></strong></td>
                                <td class="py-3 px-4"><?= htmlspecialchars($ord['service_name'] ?? 'Standard Service') ?></td>
                                <td class="py-3 px-4 fw-bold text-dark">₱<?= number_format($ord['total_price'], 2) ?></td>
                                <td class="py-3 px-4"><span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($ord['order_status']) ?></span></td>
                                <td class="py-3 px-4 text-muted small"><?= htmlspecialchars($ord['created_at']) ?></td>
                                <td class="py-3 px-4 text-end">
                                    <?php if ($isPending): ?>
                                        <form method="POST" action="orders.php" onsubmit="return confirm('Sigurado ka bang gusto mong i-cancel ang order na ito?');" style="margin: 0;">
                                            <input type="hidden" name="cancel_order_id" value="<?= $ord['order_id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-semibold shadow-none" style="font-size: 0.75rem;">Cancel</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small" style="font-size: 0.75rem;">N/A</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>