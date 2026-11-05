<?php
// admin/customers.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require __DIR__ . '/../config/dbcon.php';

$adminName = $_SESSION['full_name'] ?? 'Administrator';

// Handle Form Actions (Edit Customer & Delete Customer)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionType = $_POST['action_type'] ?? '';
    $userId = intval($_POST['user_id'] ?? 0);

    try {
        if ($actionType === 'edit_customer') {
            $fullName = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $phone = trim($_POST['phone']);
            $address = trim($_POST['address']);

            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE user_id = ? AND role = 'customer'");
            $stmt->bind_param("ssssi", $fullName, $email, $phone, $address, $userId);
            $stmt->execute();

            $_SESSION['success_msg'] = "Customer details updated successfully!";
        } 
        elseif ($actionType === 'delete_customer') {
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'customer'");
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            $_SESSION['success_msg'] = "Customer record deleted successfully.";
        }

        header("Location: customers.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error_msg'] = "Action failed: " . $e->getMessage();
        header("Location: customers.php");
        exit();
    }
}

// Fetch Customers and Stats
$customers = [];
$totalCustomers = 0;
$activeCustomers = 0;
$totalRevenue = 0.00;
$errorMsg = $_SESSION['error_msg'] ?? '';
unset($_SESSION['error_msg']);

try {
    // Stats queries
    $statQuery = $conn->query("
        SELECT 
            COUNT(*) as total_cust,
            SUM(CASE WHEN is_active = 1 OR is_active IS NULL THEN 1 ELSE 0 END) as active_cust
        FROM users 
        WHERE role = 'customer'
    ");
    if ($statQuery) {
        $stats = $statQuery->fetch_assoc();
        $totalCustomers = $stats['total_cust'] ?? 0;
        $activeCustomers = $stats['active_cust'] ?? 0;
    }

    $revQuery = $conn->query("SELECT SUM(total_price) AS total FROM laundry_orders WHERE order_status = 'Completed'");
    if ($revQuery) {
        $totalRevenue = $revQuery->fetch_assoc()['total'] ?? 0.00;
    }

    // Fetch customer list with order metrics
    $custQuery = $conn->query("
        SELECT u.*, 
               COUNT(o.order_id) as total_orders,
               COALESCE(SUM(CASE WHEN o.order_status = 'Completed' THEN o.total_price ELSE 0 END), 0) as total_spent
        FROM users u
        LEFT JOIN laundry_orders o ON u.user_id = o.user_id
        WHERE u.role = 'customer'
        GROUP BY u.user_id
        ORDER BY u.user_id DESC
    ");
    if ($custQuery) {
        $customers = $custQuery->fetch_all(MYSQLI_ASSOC);
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
    <title>Customer Management | SmartSpin Admin</title>
    
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .badge-active { background-color: #d4edda; color: #155724; padding: 5px 12px; border-radius: 50px; font-weight: 600; font-size: 0.75rem; display: inline-block; }
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
                <li><a href="orders.php"><i class="bi bi-basket-fill"></i> <span>Laundry Orders</span></a></li>
                <li><a href="customers.php" class="active"><i class="bi bi-people-fill"></i> <span>Customers</span></a></li>
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
                    <h3 class="fw-bold text-dark mb-1">Customer Management</h3>
                    <p class="text-muted small mb-0">View and manage registered customer records and order activities.</p>
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

            <!-- Summary Metric Cards -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-4">
                    <div class="card h-100 mb-0">
                        <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.75rem;">Total Customers</span>
                        <h2 class="fw-bold text-primary mt-2 mb-0"><?= number_format($totalCustomers) ?></h2>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card h-100 mb-0">
                        <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.75rem;">Active Customers</span>
                        <h2 class="fw-bold text-success mt-2 mb-0"><?= number_format($activeCustomers) ?></h2>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card h-100 mb-0">
                        <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.75rem;">Total Revenue</span>
                        <h2 class="fw-bold text-dark mt-2 mb-0">₱<?= number_format($totalRevenue, 2) ?></h2>
                    </div>
                </div>
            </div>

            <!-- Customers Table Card Container -->
            <div class="card p-0 overflow-hidden">
                <div class="table-container">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Customer Name</th>
                                <th scope="col">Contact Information</th>
                                <th scope="col">Total Orders</th>
                                <th scope="col">Total Spent</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($customers)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <div class="bg-light text-muted rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                                            <i class="bi bi-people"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark">No registered customers</h5>
                                        <p class="text-muted small mb-0">Customers who sign up will appear here.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($customers as $cust): 
                                    $initial = strtoupper(substr($cust['full_name'] ?? 'C', 0, 1));
                                    $ordersCount = intval($cust['total_orders']);
                                    $ordersText = $ordersCount === 1 ? '1 order' : $ordersCount . ' orders';
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width: 40px; height: 40px;">
                                                <?= $initial ?>
                                            </div>
                                            <div>
                                                <strong class="text-dark d-block"><?= htmlspecialchars($cust['full_name']) ?></strong>
                                                <small class="text-muted" style="font-size: 0.75rem;">Joined <?= date('M d, Y', strtotime($cust['created_at'] ?? 'now')) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-dark fw-semibold"><?= htmlspecialchars($cust['email']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($cust['phone'] ?? 'No phone number') ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-secondary border"><?= $ordersText ?></span></td>
                                    <td class="fw-bold text-dark">₱<?= number_format($cust['total_spent'], 2) ?></td>
                                    <td><span class="badge-active"><i class="bi bi-dot fs-6 me-1"></i>Active</span></td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            <!-- Edit Button -->
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-none fw-semibold" data-bs-toggle="modal" data-bs-target="#editCustomerModal<?= $cust['user_id'] ?>">
                                                <i class="bi bi-pencil me-1"></i> Edit
                                            </button>
                                            <!-- Delete Button -->
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 shadow-none fw-semibold" data-bs-toggle="modal" data-bs-target="#deleteCustomerModal<?= $cust['user_id'] ?>">
                                                <i class="bi bi-trash me-1"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- EDIT CUSTOMER MODAL -->
                                <div class="modal fade" id="editCustomerModal<?= $cust['user_id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 rounded-4 shadow-lg p-4">
                                            <div class="modal-header border-0 pb-0">
                                                <h5 class="modal-title fw-bold">Edit Customer Details</h5>
                                                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body pt-3 text-start">
                                                <form action="customers.php" method="POST">
                                                    <input type="hidden" name="user_id" value="<?= $cust['user_id'] ?>">
                                                    <input type="hidden" name="action_type" value="edit_customer">
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted small fw-bold">FULL NAME</label>
                                                        <input type="text" class="form-control rounded-3 p-3 shadow-none" name="full_name" value="<?= htmlspecialchars($cust['full_name']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted small fw-bold">EMAIL ADDRESS</label>
                                                        <input type="email" class="form-control rounded-3 p-3 shadow-none" name="email" value="<?= htmlspecialchars($cust['email']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted small fw-bold">CONTACT NUMBER</label>
                                                        <input type="text" class="form-control rounded-3 p-3 shadow-none" name="phone" value="<?= htmlspecialchars($cust['phone'] ?? '') ?>">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label text-muted small fw-bold">ADDRESS</label>
                                                        <textarea class="form-control rounded-3 p-3 shadow-none" name="address" rows="2"><?= htmlspecialchars($cust['address'] ?? '') ?></textarea>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary w-100 py-2.5 rounded-3 fw-semibold" style="background-color: var(--primary-navy);">
                                                        Save Changes
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- DELETE CUSTOMER MODAL -->
                                <div class="modal fade" id="deleteCustomerModal<?= $cust['user_id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content border-0 rounded-4 shadow-lg p-4">
                                            <div class="modal-header border-0 pb-0">
                                                <h5 class="modal-title fw-bold text-danger">Delete Customer</h5>
                                                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body pt-3 text-start">
                                                <p class="text-muted small mb-4">Are you sure you want to delete <strong><?= htmlspecialchars($cust['full_name']) ?></strong>? This action cannot be undone.</p>
                                                <form action="customers.php" method="POST">
                                                    <input type="hidden" name="user_id" value="<?= $cust['user_id'] ?>">
                                                    <input type="hidden" name="action_type" value="delete_customer">
                                                    <button type="submit" class="btn btn-danger w-100 py-2.5 rounded-3 fw-semibold">
                                                        Confirm Delete
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