<?php
// admin/orders.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require __DIR__ . '/../config/dbcon.php';

$adminName = $_SESSION['full_name'] ?? 'Administrator';

// Handle Actions (Update Status or Cancel with Reason)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionType = $_POST['action_type'] ?? '';
    $orderId = intval($_POST['order_id'] ?? 0);

    try {
        if ($actionType === 'update_status') {
            $newStatus = $_POST['order_status'];
            $stmt = $conn->prepare("UPDATE laundry_orders SET order_status = ? WHERE order_id = ?");
            $stmt->bind_param("si", $newStatus, $orderId);
            $stmt->execute();
            $_SESSION['success_msg'] = "Order status successfully updated to {$newStatus}!";
        } 
        elseif ($actionType === 'cancel_order') {
            $reason = trim($_POST['cancel_reason'] ?? 'Declined by admin');
            $stmt = $conn->prepare("UPDATE laundry_orders SET order_status = 'Cancelled', cancel_reason = ? WHERE order_id = ?");
            $stmt->bind_param("si", $reason, $orderId);
            $stmt->execute();
            $_SESSION['success_msg'] = "Order has been declined/cancelled.";
        }

        header("Location: orders.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error_msg'] = "Action failed: " . $e->getMessage();
        header("Location: orders.php");
        exit();
    }
}

// Fetch all orders securely
$orders = [];
$errorMsg = $_SESSION['error_msg'] ?? '';
unset($_SESSION['error_msg']);

try {
    $ordersQuery = $conn->query("
        SELECT o.*, u.full_name, u.email, u.phone, u.address, s.service_name, s.pricing_unit
        FROM laundry_orders o 
        LEFT JOIN users u ON o.user_id = u.user_id 
        LEFT JOIN services s ON o.service_id = s.service_id 
        ORDER BY o.created_at DESC
    ");
    if ($ordersQuery) {
        $orders = $ordersQuery->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    $errorMsg = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Orders | SmartSpin Admin</title>
    
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .status-badge { padding: 6px 12px; border-radius: 50px; font-weight: 600; font-size: 0.75rem; display: inline-block; }
        .status-Pending { background-color: #fff3cd; color: #856404; }
        .status-Confirmed, .status-Washing, .status-Drying, .status-Folding, .status-ReadyforPickup { background-color: #cce5ff; color: #004085; }
        .status-Completed { background-color: #d4edda; color: #155724; }
        .status-Cancelled { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

    <!-- Mobile Topbar -->
    <div class="mobile-topbar">
        <button class="btn btn-outline-secondary border-0 shadow-none" id="sidebarToggle" aria-label="Toggle navigation">
            <i class="bi bi-list fs-4"></i>
        </button>
        <span class="fw-bold text-primary"><i class="bi bi-droplet-half me-1"></i> SmartSpin Admin</span>
        <div style="width: 38px;"></div>
    </div>

    <div class="admin-wrapper">
        
        <!-- Sidebar Navigation -->
        <nav class="sidebar" id="sidebarNav">
            <a href="dashboard.php" class="sidebar-brand">
                <i class="bi bi-droplet-half fs-4 text-primary"></i> <span>SmartSpin Admin</span>
            </a>
            
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="bi bi-grid-1x2-fill"></i> <span>Dashboard</span></a></li>
                <li><a href="orders.php" class="active"><i class="bi bi-basket-fill"></i> <span>Laundry Orders</span></a></li>
                <li><a href="customers.php"><i class="bi bi-people-fill"></i> <span>Customers</span></a></li>
                <li><a href="services.php"><i class="bi bi-tags-fill"></i> <span>Services & Pricing</span></a></li>
                <li><a href="machines.php"><i class="bi bi-cpu-fill"></i> <span>Washing Machines</span></a></li>
                <li><a href="reports.php"><i class="bi bi-bar-chart-fill"></i> <span>Reports & Sales</span></a></li>
                <li><a href="settings.php"><i class="bi bi-gear-fill"></i> <span>Settings</span></a></li>
            </ul>

            <div class="sidebar-footer">
                <div class="d-flex align-items-center gap-2 mb-3 admin-info">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 38px; height: 38px;">
                        <?= substr($adminName, 0, 1) ?>
                    </div>
                    <div class="overflow-hidden">
                        <h6 class="mb-0 text-truncate fw-bold small text-dark"><?= htmlspecialchars($adminName) ?></h6>
                        <small class="text-muted d-block" style="font-size: 0.75rem;">Administrator</small>
                    </div>
                </div>
                <a href="../login.php" class="btn btn-outline-danger w-100 btn-sm rounded-pill d-flex align-items-center justify-content-center gap-1" style="min-height: 38px;">
                    <i class="bi bi-box-arrow-right"></i> <span>Sign out</span>
                </a>
            </div>
        </nav>

        <!-- Main Content Area -->
        <main class="main-content">
            
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Laundry Orders Management</h3>
                    <p class="text-muted small mb-0">Accept or decline customer orders and monitor progress.</p>
                </div>
            </div>

            <!-- Flash Messages -->
            <?php if(isset($_SESSION['success_msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if(!empty($errorMsg)): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMsg) ?>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Orders Table Card Container -->
            <div class="card p-0 overflow-hidden">
                <div class="table-container">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Tracking No</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Service & Weight</th>
                                <th scope="col">Total</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($orders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <div class="bg-light text-muted rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                                            <i class="bi bi-basket"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark">No laundry orders found</h5>
                                        <p class="text-muted small mb-0">Orders placed by customers will appear here.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($orders as $order): 
                                    $statusClass = 'status-' . str_replace(' ', '', $order['order_status']);
                                ?>
                                <tr>
                                    <td><strong class="text-primary"><?= htmlspecialchars($order['tracking_no']) ?></strong></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($order['full_name'] ?? 'Walk-in / Unknown') ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($order['phone'] ?? 'No contact') ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($order['service_name'] ?? 'General Laundry') ?></div>
                                        <small class="text-muted"><?= $order['weight_load'] ?> kg</small>
                                    </td>
                                    <td class="fw-bold text-dark">₱<?= number_format($order['total_price'], 2) ?></td>
                                    <td><span class="status-badge <?= $statusClass ?>"><?= $order['order_status'] ?></span></td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            <?php if($order['order_status'] === 'Pending'): ?>
                                                <!-- Accept Button -->
                                                <form action="orders.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                                    <input type="hidden" name="action_type" value="update_status">
                                                    <input type="hidden" name="order_status" value="Confirmed">
                                                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 shadow-none fw-semibold">
                                                        <i class="bi bi-check-lg me-1"></i> Accept
                                                    </button>
                                                </form>

                                                <!-- Decline Button -->
                                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 shadow-none fw-semibold" data-bs-toggle="modal" data-bs-target="#declineModal<?= $order['order_id'] ?>">
                                                    <i class="bi bi-x-lg me-1"></i> Decline
                                                </button>
                                            <?php else: ?>
                                                <!-- Update Status for Active/Completed Orders -->
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-none fw-semibold" data-bs-toggle="modal" data-bs-target="#statusModal<?= $order['order_id'] ?>">
                                                    <i class="bi bi-arrow-repeat me-1"></i> Update Status
                                                </button>
                                            <?php endif; ?>

                                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-none" data-bs-toggle="modal" data-bs-target="#detailsModal<?= $order['order_id'] ?>">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- 1. VIEW DETAILS MODAL -->
                                <div class="modal fade" id="detailsModal<?= $order['order_id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 rounded-4 shadow-lg p-4">
                                            <div class="modal-header border-0 pb-0">
                                                <h5 class="modal-title fw-bold">Order Details: <?= htmlspecialchars($order['tracking_no']) ?></h5>
                                                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body pt-3 text-start">
                                                <p class="mb-2"><strong>Customer:</strong> <?= htmlspecialchars($order['full_name'] ?? 'Unknown') ?></p>
                                                <p class="mb-2"><strong>Contact:</strong> <?= htmlspecialchars($order['phone'] ?? 'N/A') ?> | <?= htmlspecialchars($order['email'] ?? 'N/A') ?></p>
                                                <p class="mb-2"><strong>Address:</strong> <?= htmlspecialchars($order['address'] ?? 'Not specified') ?></p>
                                                <hr>
                                                <p class="mb-2"><strong>Service:</strong> <?= htmlspecialchars($order['service_name'] ?? 'N/A') ?></p>
                                                <p class="mb-2"><strong>Weight/Load:</strong> <?= $order['weight_load'] ?> kg</p>
                                                <p class="mb-2"><strong>Total Amount:</strong> ₱<?= number_format($order['total_price'], 2) ?></p>
                                                <p class="mb-2"><strong>Payment Status:</strong> <span class="badge bg-secondary"><?= htmlspecialchars($order['payment_status']) ?></span></p>
                                                <p class="mb-2"><strong>Order Date:</strong> <?= date('M d, Y h:i A', strtotime($order['created_at'])) ?></p>
                                                <?php if($order['order_status'] === 'Cancelled' && !empty($order['cancel_reason'])): ?>
                                                    <div class="alert alert-danger mt-3 mb-0 small">
                                                        <strong>Reason:</strong> <?= htmlspecialchars($order['cancel_reason']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. UPDATE STATUS MODAL -->
                                <div class="modal fade" id="statusModal<?= $order['order_id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 rounded-4 shadow-lg p-4">
                                            <div class="modal-header border-0 pb-0">
                                                <h5 class="modal-title fw-bold">Update Status: <?= htmlspecialchars($order['tracking_no']) ?></h5>
                                                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body pt-3 text-start">
                                                <form action="orders.php" method="POST">
                                                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                                    <input type="hidden" name="action_type" value="update_status">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted small fw-bold">NEW ORDER STATUS</label>
                                                        <select class="form-select rounded-3 p-3 shadow-none" name="order_status" required>
                                                            <?php 
                                                            $statuses = ['Pending', 'Confirmed', 'Washing', 'Drying', 'Folding', 'Ready for Pickup', 'Completed'];
                                                            foreach($statuses as $st) {
                                                                $selected = ($order['order_status'] === $st) ? 'selected' : '';
                                                                echo "<option value=\"$st\" $selected>$st</option>";
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-3 fw-semibold" style="background-color: var(--primary-navy);">
                                                        Save New Status
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 3. DECLINE ORDER MODAL -->
                                <div class="modal fade" id="declineModal<?= $order['order_id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 rounded-4 shadow-lg p-4">
                                            <div class="modal-header border-0 pb-0">
                                                <h5 class="modal-title fw-bold text-danger">Decline Order: <?= htmlspecialchars($order['tracking_no']) ?></h5>
                                                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body pt-3 text-start">
                                                <form action="orders.php" method="POST">
                                                    <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                                    <input type="hidden" name="action_type" value="cancel_order">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted small fw-bold">REASON FOR DECLINING</label>
                                                        <textarea class="form-control rounded-3 p-3 shadow-none" name="cancel_reason" rows="3" placeholder="Enter reason (e.g. Fully booked, shop closed)..." required></textarea>
                                                    </div>
                                                    <button type="submit" class="btn btn-danger w-100 py-2.5 rounded-3 fw-semibold">
                                                        Confirm Decline
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarNav = document.getElementById('sidebarNav');
            
            if(sidebarToggle && sidebarNav) {
                sidebarToggle.addEventListener('click', function() {
                    sidebarNav.classList.toggle('show');
                });
            }
        });
    </script>
</body>
</html>