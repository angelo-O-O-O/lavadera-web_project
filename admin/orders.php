<?php
// admin/orders.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require __DIR__ . '/../config/dbcon.php';

$successMsg = "";
$errorMsg = "";
$adminName = $_SESSION['full_name'] ?? 'Administrator';

// Handle Confirm Order
if (isset($_GET['action']) && $_GET['action'] === 'confirm') {
    $orderId = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE laundry_orders SET order_status = 'Confirmed' WHERE order_id = ?");
    $stmt->bind_param("i", $orderId);
    if ($stmt->execute()) {
        $successMsg = "Order confirmed successfully!";
    } else {
        $errorMsg = "Failed to confirm order.";
    }
}

// Handle Status Update
if (isset($_POST['update_status'])) {
    $orderId = intval($_POST['order_id']);
    $newStatus = trim($_POST['order_status']);

    if ($newStatus === 'Completed' || $newStatus === 'Delivered' || $newStatus === 'Pending') {
        $stmt = $conn->prepare("UPDATE laundry_orders SET order_status = ?, machine_assigned = NULL WHERE order_id = ?");
    } else {
        $stmt = $conn->prepare("UPDATE laundry_orders SET order_status = ? WHERE order_id = ?");
    }
    
    $stmt->bind_param("si", $newStatus, $orderId);
    if ($stmt->execute()) {
        $successMsg = "Order status updated successfully!";
    } else {
        $errorMsg = "Failed to update order status.";
    }
}

// Handle Decline / Cancel Order
if (isset($_GET['action']) && $_GET['action'] === 'decline') {
    $orderId = intval($_GET['id']);

    $ordStmt = $conn->prepare("SELECT total_price, order_status FROM laundry_orders WHERE order_id = ?");
    $ordStmt->bind_param("i", $orderId);
    $ordStmt->execute();
    $orderData = $ordStmt->get_result()->fetch_assoc();

    if ($orderData) {
        $oldStatus = $orderData['order_status'];

        $updateStmt = $conn->prepare("UPDATE laundry_orders SET order_status = 'Cancelled', machine_assigned = NULL WHERE order_id = ?");
        $updateStmt->bind_param("i", $orderId);
        
        if ($updateStmt->execute()) {
            if ($oldStatus === 'Completed' || $oldStatus === 'Delivered') {
                $revStmt = $conn->prepare("UPDATE payments SET status = 'Cancelled' WHERE order_id = ?");
                $revStmt->bind_param("i", $orderId);
                $revStmt->execute();
            }
            $successMsg = "Order successfully cancelled.";
        } else {
            $errorMsg = "Failed to cancel order.";
        }
    }
}

// Handle Machine Assignment
if (isset($_POST['assign_machine'])) {
    $orderId = intval($_POST['order_id']);
    $machineName = trim($_POST['machine_name']);

    $statusToSet = (stripos($machineName, 'dryer') !== false) ? 'Drying' : 'Washing';

    $stmt = $conn->prepare("UPDATE laundry_orders SET machine_assigned = ?, order_status = ? WHERE order_id = ?");
    $stmt->bind_param("ssi", $machineName, $statusToSet, $orderId);
    if ($stmt->execute()) {
        $successMsg = "Successfully assigned to " . htmlspecialchars($machineName) . "!";
    } else {
        $errorMsg = "Failed to assign machine.";
    }
}

// Fetch active washing machines / dryers
$machinesList = [];
$machQuery = $conn->query("SELECT * FROM washing_machines");
if ($machQuery) {
    $machinesList = $machQuery->fetch_all(MYSQLI_ASSOC);
}

// Fetch busy machines
$busyMachines = [];
$busyQuery = $conn->query("SELECT machine_assigned FROM laundry_orders WHERE machine_assigned IS NOT NULL AND order_status NOT IN ('Completed', 'Delivered', 'Cancelled')");
if ($busyQuery) {
    while ($row = $busyQuery->fetch_assoc()) {
        $busyMachines[] = $row['machine_assigned'];
    }
}

// Fetch all orders
$ordersQuery = $conn->query("
    SELECT o.*, u.full_name, u.phone, s.service_name 
    FROM laundry_orders o 
    JOIN users u ON o.user_id = u.user_id 
    JOIN services s ON o.service_id = s.service_id 
    ORDER BY o.created_at DESC
");
$orders = $ordersQuery ? $ordersQuery->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Orders | SmartSpin Admin</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
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
        
        <!-- Sidebar Navigation (Complete with all menu items) -->
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
            <div class="mb-4">
                <h3 class="fw-bold text-dark mb-1">Laundry Orders Management</h3>
                <p class="text-muted small mb-0">Accept customer orders, assign machines, and monitor progress.</p>
            </div>

            <?php if (!empty($successMsg)): ?>
                <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
                    <?= htmlspecialchars($successMsg) ?>
                    <button type="button" class="btn-close btn-sm shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-danger alert-dismissible fade show py-2 small" role="alert">
                    <?= htmlspecialchars($errorMsg) ?>
                    <button type="button" class="btn-close btn-sm shadow-none" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card p-0 overflow-hidden">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr class="text-muted small text-uppercase border-bottom">
                                <th class="py-3 px-4">Tracking No</th>
                                <th class="py-3">Customer</th>
                                <th class="py-3">Service & Weight</th>
                                <th class="py-3">Assigned Machine</th>
                                <th class="py-3">Total</th>
                                <th class="py-3">Status</th>
                                <th class="py-3 text-end px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($orders) > 0): ?>
                                <?php foreach ($orders as $ord): ?>
                                    <tr>
                                        <td class="px-4 fw-bold text-primary">#<?= htmlspecialchars($ord['tracking_no']) ?></td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($ord['full_name']) ?></div>
                                            <div class="text-muted small"><?= htmlspecialchars($ord['phone']) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-medium text-dark"><?= htmlspecialchars($ord['service_name']) ?></div>
                                            <div class="text-muted small"><?= number_format($ord['weight'] ?? 1.0, 2) ?> kg</div>
                                        </td>
                                        <td>
                                            <?php 
                                                $st = $ord['order_status'];
                                            ?>
                                            <?php if (!empty($ord['machine_assigned']) && $st !== 'Completed' && $st !== 'Delivered' && $st !== 'Pending'): ?>
                                                <span class="badge bg-light text-dark border px-2 py-1">
                                                    <?= htmlspecialchars($ord['machine_assigned']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted small">Not assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold text-dark">₱<?= number_format($ord['total_price'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border px-2 py-1">
                                                <?= htmlspecialchars($st) ?>
                                            </span>
                                        </td>
                                        <td class="text-end px-4">
                                            <div class="d-flex justify-content-end gap-2 align-items-center">
                                                <?php if ($st === 'Completed' || $st === 'Delivered'): ?>
                                                    <span class="text-muted small">Finalized / Completed</span>
                                                <?php elseif ($st === 'Pending'): ?>
                                                    <a href="orders.php?action=confirm&id=<?= $ord['order_id'] ?>" class="btn btn-success btn-sm px-3">Confirm</a>
                                                    <button type="button" class="btn btn-outline-danger btn-sm px-3" data-bs-toggle="modal" data-bs-target="#declineModal<?= $ord['order_id'] ?>">Decline</button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-outline-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#assignModal<?= $ord['order_id'] ?>">Assign Machine</button>
                                                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#statusModal<?= $ord['order_id'] ?>">Edit Status</button>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Standard Decline Modal -->
                                            <?php if ($st === 'Pending'): ?>
                                                <div class="modal fade text-start" id="declineModal<?= $ord['order_id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title fs-6 fw-bold">Cancel Order</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Are you sure you want to cancel order #<?= htmlspecialchars($ord['tracking_no']) ?>?
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                                                <a href="orders.php?action=decline&id=<?= $ord['order_id'] ?>" class="btn btn-danger btn-sm">Cancel Order</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Standard Machine Assignment Modal -->
                                            <?php if ($st !== 'Pending' && $st !== 'Completed' && $st !== 'Delivered'): ?>
                                                <div class="modal fade text-start" id="assignModal<?= $ord['order_id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <form method="POST" action="orders.php">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title fs-6 fw-bold">Assign Machine for #<?= htmlspecialchars($ord['tracking_no']) ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="order_id" value="<?= $ord['order_id'] ?>">
                                                                    <div class="mb-3">
                                                                        <label for="machine_name" class="form-label small">Select Machine / Dryer</label>
                                                                        <select class="form-select form-select-sm" name="machine_name" required>
                                                                            <option value="" disabled selected>Choose machine...</option>
                                                                            <?php foreach ($machinesList as $mach): ?>
                                                                                <?php 
                                                                                    $mName = $mach['machine_name'];
                                                                                    $isBusy = in_array($mName, $busyMachines);
                                                                                ?>
                                                                                <option value="<?= htmlspecialchars($mName) ?>" <?= $isBusy ? 'disabled' : '' ?>>
                                                                                    <?= htmlspecialchars($mName) ?> (<?= htmlspecialchars($mach['machine_type'] ?? 'Machine') ?>) <?= $isBusy ? ' - [IN USE]' : '' ?>
                                                                                </option>
                                                                            <?php endforeach; ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                                                    <button type="submit" name="assign_machine" class="btn btn-primary btn-sm">Save</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Standard Status Update Modal -->
                                            <div class="modal fade text-start" id="statusModal<?= $ord['order_id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <form method="POST" action="orders.php">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title fs-6 fw-bold">Update Status for #<?= htmlspecialchars($ord['tracking_no']) ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <input type="hidden" name="order_id" value="<?= $ord['order_id'] ?>">
                                                                <div class="mb-3">
                                                                    <label for="order_status" class="form-label small">New Status</label>
                                                                    <select class="form-select form-select-sm" name="order_status" required>
                                                                        <option value="Pending" <?= ($st === 'Pending') ? 'selected' : '' ?>>Pending</option>
                                                                        <option value="Confirmed" <?= ($st === 'Confirmed') ? 'selected' : '' ?>>Confirmed</option>
                                                                        <option value="Washing" <?= ($st === 'Washing') ? 'selected' : '' ?>>Washing</option>
                                                                        <option value="Drying" <?= ($st === 'Drying') ? 'selected' : '' ?>>Drying</option>
                                                                        <option value="Ready for Pickup" <?= ($st === 'Ready for Pickup') ? 'selected' : '' ?>>Ready for Pickup</option>
                                                                        <option value="Completed" <?= ($st === 'Completed') ? 'selected' : '' ?>>Completed</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                                                <button type="submit" name="update_status" class="btn btn-primary btn-sm">Update</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No laundry orders found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
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