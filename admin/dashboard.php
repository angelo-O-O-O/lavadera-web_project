<?php
// admin/dashboard.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require __DIR__ . '/../config/dbcon.php';

$adminName = $_SESSION['full_name'] ?? 'Administrator';

// Get Filter Option (default to 'monthly')
$filter = $_GET['filter'] ?? 'monthly';

// Date condition based on filter for revenue
$dateCondition = "";
if ($filter === 'daily') {
    $dateCondition = "AND DATE(created_at) = CURDATE()";
} elseif ($filter === 'weekly') {
    $dateCondition = "AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($filter === 'monthly') {
    $dateCondition = "AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
} elseif ($filter === 'yearly') {
    $dateCondition = "AND YEAR(created_at) = YEAR(CURDATE())";
}

// Fetch Dashboard Metrics
$totalOrders = 0;
$pendingOrders = 0;
$activeCustomers = 0;
$filteredRevenue = 0.00;
$recentOrders = [];

// Chart Data Arrays
$chartLabels = [];
$chartData = [];

try {
    // Total Orders count
    $ordQuery = $conn->query("SELECT COUNT(*) as total FROM laundry_orders");
    if ($ordQuery) {
        $totalOrders = $ordQuery->fetch_assoc()['total'] ?? 0;
    }

    // Pending Orders count
    $pendQuery = $conn->query("SELECT COUNT(*) as total FROM laundry_orders WHERE order_status = 'Pending'");
    if ($pendQuery) {
        $pendingOrders = $pendQuery->fetch_assoc()['total'] ?? 0;
    }

    // Active Customers count
    $custQuery = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
    if ($custQuery) {
        $activeCustomers = $custQuery->fetch_assoc()['total'] ?? 0;
    }

    // Filtered Revenue based on dropdown selection (Pinatibay gamit ang LOWER para laging mabasa kahit anong capitalization)
    $revQuery = $conn->query("SELECT SUM(total_price) AS total FROM laundry_orders WHERE LOWER(order_status) != 'cancelled' $dateCondition");
    if ($revQuery) {
        $filteredRevenue = $revQuery->fetch_assoc()['total'] ?? 0.00;
    }

    // Dynamic Chart Data Query based on selected filter
    if ($filter === 'daily') {
        $chartLabels = ['8 AM', '10 AM', '12 PM', '2 PM', '4 PM', '6 PM', '8 PM'];
        $chartData = array_fill(0, count($chartLabels), 0);

        $graphQ = $conn->query("
            SELECT HOUR(created_at) as timeframe, SUM(total_price) as sales
            FROM laundry_orders 
            WHERE LOWER(order_status) != 'cancelled' AND DATE(created_at) = CURDATE()
            GROUP BY HOUR(created_at)
        ");
        while($row = $graphQ->fetch_assoc()) {
            $h = intval($row['timeframe']);
            if ($h >= 8 && $h <= 20) {
                $index = floor(($h - 8) / 2);
                if (isset($chartData[$index])) {
                    $chartData[$index] = floatval($row['sales']);
                }
            }
        }
    } elseif ($filter === 'weekly') {
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $chartLabels = $days;
        $chartData = array_fill(0, count($days), 0);

        $graphQ = $conn->query("
            SELECT DAYNAME(created_at) as timeframe, SUM(total_price) as sales
            FROM laundry_orders 
            WHERE LOWER(order_status) != 'cancelled' AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)
            GROUP BY DAYNAME(created_at)
        ");
        $dayMap = ['Monday' => 0, 'Tuesday' => 1, 'Wednesday' => 2, 'Thursday' => 3, 'Friday' => 4, 'Saturday' => 5, 'Sunday' => 6];
        while($row = $graphQ->fetch_assoc()) {
            $dName = $row['timeframe'];
            if (isset($dayMap[$dName])) {
                $chartData[$dayMap[$dName]] = floatval($row['sales']);
            }
        }
    } elseif ($filter === 'monthly') {
        // Accurate Monthly breakdown by weeks of the current month
        $chartLabels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
        $chartData = array_fill(0, 4, 0);

        $graphQ = $conn->query("
            SELECT CEIL(DAYOFMONTH(created_at) / 7) as week_num, SUM(total_price) as sales
            FROM laundry_orders 
            WHERE LOWER(order_status) != 'cancelled' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
            GROUP BY week_num
        ");
        while($row = $graphQ->fetch_assoc()) {
            $wNum = intval($row['week_num']);
            $idx = $wNum - 1;
            if ($idx >= 3) { $idx = 3; } // Cap at Week 4 for standard monthly view
            if (isset($chartData[$idx])) {
                $chartData[$idx] = floatval($row['sales']);
            }
        }
    } elseif ($filter === 'yearly') {
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $chartLabels = $months;
        $chartData = array_fill(0, count($months), 0);

        $graphQ = $conn->query("
            SELECT MONTH(created_at) as m_num, SUM(total_price) as sales
            FROM laundry_orders 
            WHERE LOWER(order_status) != 'cancelled' AND YEAR(created_at) = YEAR(CURDATE())
            GROUP BY MONTH(created_at)
        ");
        while($row = $graphQ->fetch_assoc()) {
            $idx = intval($row['m_num']) - 1;
            if (isset($chartData[$idx])) {
                $chartData[$idx] = floatval($row['sales']);
            }
        }
    }

    // Recent Orders List
    $recentQuery = $conn->query("
        SELECT o.*, u.full_name, s.service_name 
        FROM laundry_orders o
        LEFT JOIN users u ON o.user_id = u.user_id
        LEFT JOIN services s ON o.service_id = s.service_id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");
    if ($recentQuery) {
        $recentOrders = $recentQuery->fetch_all(MYSQLI_ASSOC);
    }

} catch (Exception $e) {
    $errorMsg = "Dashboard error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard | SmartSpin Admin</title>
    
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .status-badge { padding: 5px 10px; border-radius: 50px; font-weight: 600; font-size: 0.75rem; display: inline-block; }
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
                <li><a href="dashboard.php" class="active"><i class="bi bi-grid-1x2-fill"></i> <span>Dashboard</span></a></li>
                <li><a href="orders.php"><i class="bi bi-basket-fill"></i> <span>Laundry Orders</span></a></li>
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
            
            <!-- Page Header with Sales Filter Dropdown -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Admin Dashboard</h3>
                    <p class="text-muted small mb-0">Welcome back! Here's an overview of your laundry shop activities.</p>
                </div>
                <form method="GET" action="dashboard.php" class="d-flex align-items-center gap-2">
                    <label for="filter" class="text-muted small fw-bold text-nowrap">View Sales:</label>
                    <select name="filter" id="filter" class="form-select form-select-sm rounded-3 shadow-none bg-white border" onchange="this.form.submit()">
                        <option value="daily" <?= $filter === 'daily' ? 'selected' : '' ?>>Daily</option>
                        <option value="weekly" <?= $filter === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                        <option value="monthly" <?= $filter === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        <option value="yearly" <?= $filter === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                    </select>
                </form>
            </div>

            <!-- Metric Cards -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card h-100 mb-0">
                        <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.75rem;">
                            <?= ucfirst($filter) ?> Revenue
                        </span>
                        <h3 class="fw-bold text-success mt-2 mb-0">₱<?= number_format($filteredRevenue, 2) ?></h3>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card h-100 mb-0">
                        <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.75rem;">Total Orders</span>
                        <h3 class="fw-bold text-primary mt-2 mb-0"><?= number_format($totalOrders) ?></h3>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card h-100 mb-0">
                        <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.75rem;">Pending Orders</span>
                        <h3 class="fw-bold text-warning mt-2 mb-0"><?= number_format($pendingOrders) ?></h3>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card h-100 mb-0">
                        <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.75rem;">Registered Customers</span>
                        <h3 class="fw-bold text-dark mt-2 mb-0"><?= number_format($activeCustomers) ?></h3>
                    </div>
                </div>
            </div>

            <!-- Smooth Curved Line Graph Section -->
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card mb-0">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-dark mb-0"><?= ucfirst($filter) ?> Sales Overview</h5>
                            <span class="badge bg-light text-secondary border"><?= strtoupper($filter) ?> REPORT</span>
                        </div>
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Table Section -->
            <div class="card p-0 overflow-hidden">
                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0">Recent Laundry Orders</h5>
                    <a href="orders.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-none fw-semibold">View All</a>
                </div>
                <div class="table-container">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Tracking No</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Service</th>
                                <th scope="col">Total</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($recentOrders)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No recent orders found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($recentOrders as $order): 
                                    $statusClass = 'status-' . str_replace(' ', '', $order['order_status']);
                                ?>
                                <tr>
                                    <td><strong class="text-primary"><?= htmlspecialchars($order['tracking_no']) ?></strong></td>
                                    <td><?= htmlspecialchars($order['full_name'] ?? 'Walk-in') ?></td>
                                    <td><?= htmlspecialchars($order['service_name'] ?? 'General') ?></td>
                                    <td class="fw-bold">₱<?= number_format($order['total_price'], 2) ?></td>
                                    <td><span class="status-badge <?= $statusClass ?>"><?= $order['order_status'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Sidebar toggle for mobile
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarNav = document.getElementById('sidebarNav');
            
            if(sidebarToggle && sidebarNav) {
                sidebarToggle.addEventListener('click', function() {
                    sidebarNav.classList.toggle('show');
                });
            }

            // Render Smooth Curved Line Chart
            const ctx = document.getElementById('salesChart').getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, 'rgba(30, 79, 143, 0.35)');
            gradient.addColorStop(1, 'rgba(30, 79, 143, 0.00)');

            const salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($chartLabels) ?>,
                    datasets: [{
                        label: 'Sales (₱)',
                        data: <?= json_encode($chartData) ?>,
                        backgroundColor: gradient,
                        borderColor: '#1e4f8f',
                        borderWidth: 3,
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#1e4f8f',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f0f4fd' },
                            ticks: { 
                                font: { family: 'Inter' },
                                precision: 0,
                                callback: function(value) {
                                    return '₱' + value;
                                }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { family: 'Inter' } }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>