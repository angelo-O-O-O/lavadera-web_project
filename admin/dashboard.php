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

// Date condition based on filter
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

// Metrics Variables
$totalRevenue = 0.00;
$totalOrdersCount = 0;
$pendingOrdersCount = 0;
$registeredCustomersCount = 0;
$chartLabels = [];
$chartData = [];
$recentOrders = [];

try {
    // 1. Revenue Count (Completed/Delivered)
    $revQ = $conn->query("SELECT SUM(total_price) AS total FROM laundry_orders WHERE LOWER(order_status) IN ('completed', 'delivered', 'complete') $dateCondition");
    if ($revQ) {
        $totalRevenue = $revQ->fetch_assoc()['total'] ?? 0.00;
    }

    // 2. Total Orders Count
    $ordQ = $conn->query("SELECT COUNT(*) AS total FROM laundry_orders WHERE 1=1 $dateCondition");
    if ($ordQ) {
        $totalOrdersCount = $ordQ->fetch_assoc()['total'] ?? 0;
    }

    // 3. Pending Orders Count
    $pendQ = $conn->query("SELECT COUNT(*) AS total FROM laundry_orders WHERE LOWER(order_status) = 'pending' $dateCondition");
    if ($pendQ) {
        $pendingOrdersCount = $pendQ->fetch_assoc()['total'] ?? 0;
    }

    // 4. Registered Customers Count
    $custQ = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'customer'");
    if ($custQ) {
        $registeredCustomersCount = $custQ->fetch_assoc()['total'] ?? 0;
    }

    // 5. Chart Data based on filter
    if ($filter === 'daily') {
        $chartLabels = ['Today'];
        $chartData = [floatval($totalRevenue)];
    } elseif ($filter === 'weekly') {
        $graphQ = $conn->query("
            SELECT DAYNAME(created_at) as timeframe, SUM(total_price) as sales
            FROM laundry_orders 
            WHERE LOWER(order_status) IN ('completed', 'delivered', 'complete') AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)
            GROUP BY DATE(created_at), DAYNAME(created_at)
            ORDER BY created_at ASC
        ");
        $tempData = [];
        while($row = $graphQ->fetch_assoc()) {
            $tempData[$row['timeframe']] = floatval($row['sales']);
        }
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        foreach($days as $day) {
            $chartLabels[] = $day;
            $chartData[] = $tempData[$day] ?? 0;
        }
    } elseif ($filter === 'yearly') {
        $graphQ = $conn->query("
            SELECT MONTHNAME(created_at) as timeframe, SUM(total_price) as sales
            FROM laundry_orders 
            WHERE LOWER(order_status) IN ('completed', 'delivered', 'complete') AND YEAR(created_at) = YEAR(CURDATE())
            GROUP BY MONTH(created_at), MONTHNAME(created_at)
            ORDER BY MONTH(created_at) ASC
        ");
        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        $tempData = [];
        while($row = $graphQ->fetch_assoc()) {
            $tempData[$row['timeframe']] = floatval($row['sales']);
        }
        foreach($months as $month) {
            $chartLabels[] = substr($month, 0, 3);
            $chartData[] = $tempData[$month] ?? 0;
        }
    } else {
        // Monthly (Weeks 1-4)
        $graphQ = $conn->query("
            SELECT 
                CASE 
                    WHEN DAY(created_at) BETWEEN 1 AND 7 THEN 'Week 1'
                    WHEN DAY(created_at) BETWEEN 8 AND 14 THEN 'Week 2'
                    WHEN DAY(created_at) BETWEEN 15 AND 21 THEN 'Week 3'
                    ELSE 'Week 4'
                END as timeframe, 
                SUM(total_price) as sales
            FROM laundry_orders 
            WHERE LOWER(order_status) IN ('completed', 'delivered', 'complete') 
              AND MONTH(created_at) = MONTH(CURDATE()) 
              AND YEAR(created_at) = YEAR(CURDATE())
            GROUP BY timeframe
        ");
        $tempData = [];
        while($row = $graphQ->fetch_assoc()) {
            $tempData[$row['timeframe']] = floatval($row['sales']);
        }
        $weeks = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
        foreach($weeks as $wk) {
            $chartLabels[] = $wk;
            $chartData[] = $tempData[$wk] ?? 0;
        }
    }

    // 6. Recent Orders
    $recentQ = $conn->query("
        SELECT o.*, u.full_name, s.service_name 
        FROM laundry_orders o 
        JOIN users u ON o.user_id = u.user_id 
        JOIN services s ON o.service_id = s.service_id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    if ($recentQ) {
        $recentOrders = $recentQ->fetch_all(MYSQLI_ASSOC);
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
            
            <!-- Header with Filter -->
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

            <!-- Summary Metric Cards -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card h-100 mb-0">
                        <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.75rem;"><?= ucfirst($filter) ?> Revenue</span>
                        <h3 class="fw-bold text-success mt-2 mb-0">₱<?= number_format($totalRevenue, 2) ?></h3>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card h-100 mb-0">
                        <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.75rem;">Total Orders</span>
                        <h3 class="fw-bold text-primary mt-2 mb-0"><?= number_format($totalOrdersCount) ?></h3>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card h-100 mb-0">
                        <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.75rem;">Pending Orders</span>
                        <h3 class="fw-bold text-warning mt-2 mb-0"><?= number_format($pendingOrdersCount) ?></h3>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="card h-100 mb-0">
                        <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.75rem;">Registered Customers</span>
                        <h3 class="fw-bold text-dark mt-2 mb-0"><?= number_format($registeredCustomersCount) ?></h3>
                    </div>
                </div>
            </div>

            <!-- Sales Overview Chart Card -->
            <div class="card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">Sales Overview</h5>
                    <span class="badge bg-light text-secondary border"><?= strtoupper($filter) ?> REPORT</span>
                </div>
                <div style="position: relative; height: 260px; width: 100%;">
                    <canvas id="salesOverviewChart"></canvas>
                </div>
            </div>

            <!-- Recent Laundry Orders Table -->
            <div class="card p-0 overflow-hidden">
                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0">Recent Laundry Orders</h5>
                    <a href="orders.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold">View All</a>
                </div>
                <div class="table-container">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th scope="col" class="ps-4">Tracking No</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Service</th>
                                <th scope="col">Total</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end pe-4">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentOrders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No recent orders found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($recentOrders as $ord): ?>
                                <tr>
                                    <td class="ps-4 fw-bold text-primary">#<?= htmlspecialchars($ord['tracking_no']) ?></td>
                                    <td><strong class="text-dark"><?= htmlspecialchars($ord['full_name']) ?></strong></td>
                                    <td><?= htmlspecialchars($ord['service_name']) ?></td>
                                    <td class="fw-bold text-dark">₱<?= number_format($ord['total_price'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-1">
                                            <?= htmlspecialchars($ord['order_status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4 text-muted small"><?= date('M d, Y', strtotime($ord['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- Chart.js & Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Mobile Sidebar Toggle
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarNav = document.getElementById('sidebarNav');
            
            if(sidebarToggle && sidebarNav) {
                sidebarToggle.addEventListener('click', function() {
                    sidebarNav.classList.toggle('show');
                });
            }

            // Render Sales Chart
            const ctx = document.getElementById('salesOverviewChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($chartLabels) ?>,
                    datasets: [{
                        label: 'Sales (₱)',
                        data: <?= json_encode($chartData) ?>,
                        borderColor: '#1e4f8f',
                        backgroundColor: 'rgba(30, 79, 143, 0.08)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
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

        // Auto-refresh dashboard every 5 seconds to sync with customer bookings automatically
        setInterval(function() {
            location.reload();
        }, 5000);
    </script>
</body>
</html>