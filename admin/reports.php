<?php
// admin/reports.php
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
$completedOrdersCount = 0;
$cancelledOrdersCount = 0;
$popularServices = [];
$chartLabels = [];
$chartData = [];
$tableSales = [];

try {
    // 1. Summary Counts
    $revQ = $conn->query("SELECT SUM(total_price) AS total FROM laundry_orders WHERE order_status = 'Completed' $dateCondition");
    if ($revQ) {
        $totalRevenue = $revQ->fetch_assoc()['total'] ?? 0.00;
    }

    $compQ = $conn->query("SELECT COUNT(*) AS total FROM laundry_orders WHERE order_status = 'Completed' $dateCondition");
    if ($compQ) {
        $completedOrdersCount = $compQ->fetch_assoc()['total'] ?? 0;
    }

    $cancQ = $conn->query("SELECT COUNT(*) AS total FROM laundry_orders WHERE order_status = 'Cancelled' $dateCondition");
    if ($cancQ) {
        $cancelledOrdersCount = $cancQ->fetch_assoc()['total'] ?? 0;
    }

    // 2. Most Popular Services
    $servQ = $conn->query("
        SELECT s.service_name, COUNT(o.order_id) as bookings, SUM(o.total_price) as revenue
        FROM laundry_orders o
        JOIN services s ON o.service_id = s.service_id
        WHERE o.order_status = 'Completed' $dateCondition
        GROUP BY s.service_id
        ORDER BY bookings DESC
        LIMIT 5
    ");
    if ($servQ) {
        $popularServices = $servQ->fetch_all(MYSQLI_ASSOC);
    }

    // 3. Dynamic Chart & Table Data based on filter
    if ($filter === 'daily') {
        $graphQ = $conn->query("
            SELECT HOUR(created_at) as timeframe, COUNT(*) as orders_count, SUM(total_price) as sales
            FROM laundry_orders 
            WHERE order_status = 'Completed' AND DATE(created_at) = CURDATE()
            GROUP BY HOUR(created_at)
            ORDER BY timeframe ASC
        ");
        $tempData = [];
        while($row = $graphQ->fetch_assoc()) {
            $label = date("g A", strtotime(sprintf("%02d:00:00", $row['timeframe'])));
            $tempData[$label] = $row;
        }
        for($i = 8; $i <= 20; $i++) {
            $label = date("g A", strtotime("$i:00"));
            $chartLabels[] = $label;
            $chartData[] = floatval($tempData[$label]['sales'] ?? 0);
            $tableSales[] = [
                'period' => $label,
                'orders' => $tempData[$label]['orders_count'] ?? 0,
                'sales' => $tempData[$label]['sales'] ?? 0.00
            ];
        }
    } elseif ($filter === 'weekly') {
        $graphQ = $conn->query("
            SELECT DAYNAME(created_at) as timeframe, COUNT(*) as orders_count, SUM(total_price) as sales
            FROM laundry_orders 
            WHERE order_status = 'Completed' AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)
            GROUP BY DATE(created_at), DAYNAME(created_at)
            ORDER BY created_at ASC
        ");
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $tempData = [];
        while($row = $graphQ->fetch_assoc()) {
            $tempData[$row['timeframe']] = $row;
        }
        foreach($days as $day) {
            $chartLabels[] = $day;
            $chartData[] = floatval($tempData[$day]['sales'] ?? 0);
            $tableSales[] = [
                'period' => $day,
                'orders' => $tempData[$day]['orders_count'] ?? 0,
                'sales' => $tempData[$day]['sales'] ?? 0.00
            ];
        }
    } elseif ($filter === 'yearly') {
        $graphQ = $conn->query("
            SELECT MONTHNAME(created_at) as timeframe, COUNT(*) as orders_count, SUM(total_price) as sales
            FROM laundry_orders 
            WHERE order_status = 'Completed' AND YEAR(created_at) = YEAR(CURDATE())
            GROUP BY MONTH(created_at), MONTHNAME(created_at)
            ORDER BY MONTH(created_at) ASC
        ");
        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        $tempData = [];
        while($row = $graphQ->fetch_assoc()) {
            $tempData[$row['timeframe']] = $row;
        }
        foreach($months as $month) {
            $chartLabels[] = substr($month, 0, 3);
            $chartData[] = floatval($tempData[$month]['sales'] ?? 0);
            $tableSales[] = [
                'period' => $month,
                'orders' => $tempData[$month]['orders_count'] ?? 0,
                'sales' => $tempData[$month]['sales'] ?? 0.00
            ];
        }
    } else {
        // Monthly Default
        $graphQ = $conn->query("
            SELECT CONCAT('Week ', WEEK(created_at, 1) - WEEK(DATE_SUB(created_at, INTERVAL DAYOFMONTH(created_at)-1 DAY), 1) + 1) as timeframe, COUNT(*) as orders_count, SUM(total_price) as sales
            FROM laundry_orders 
            WHERE order_status = 'Completed' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
            GROUP BY timeframe
            ORDER BY created_at ASC
        ");
        $tempData = [];
        while($row = $graphQ->fetch_assoc()) {
            $tempData[$row['timeframe']] = $row;
        }
        $weeks = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
        foreach($weeks as $wk) {
            $chartLabels[] = $wk;
            $chartData[] = floatval($tempData[$wk]['sales'] ?? 0);
            $tableSales[] = [
                'period' => $wk,
                'orders' => $tempData[$wk]['orders_count'] ?? 0,
                'sales' => $tempData[$wk]['sales'] ?? 0.00
            ];
        }
    }

} catch (Exception $e) {
    $errorMsg = "Reports error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales & Financial Reports | SmartSpin Admin</title>
    
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.2/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
                <li><a href="dashboard.php"><i class="bi bi-grid-1x2-fill"></i> <span>Dashboard</span></a></li>
                <li><a href="orders.php"><i class="bi bi-basket-fill"></i> <span>Laundry Orders</span></a></li>
                <li><a href="customers.php"><i class="bi bi-people-fill"></i> <span>Customers</span></a></li>
                <li><a href="services.php"><i class="bi bi-tags-fill"></i> <span>Services & Pricing</span></a></li>
                <li><a href="machines.php"><i class="bi bi-cpu-fill"></i> <span>Washing Machines</span></a></li>
                <li><a href="reports.php" class="active"><i class="bi bi-bar-chart-fill"></i> <span>Reports & Sales</span></a></li>
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
            
            <!-- Page Header with Filter Dropdown -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Sales & Financial Reports</h3>
                    <p class="text-muted small mb-0">Comprehensive analytics, revenue breakdowns, and business summaries.</p>
                </div>
                <form method="GET" action="reports.php" class="d-flex align-items-center gap-2">
                    <label for="filter" class="text-muted small fw-bold text-nowrap">Report Range:</label>
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
                <div class="col-12 col-md-4">
                    <div class="card h-100 mb-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.75rem;"><?= ucfirst($filter) ?> Revenue</span>
                                <h3 class="fw-bold text-success mt-2 mb-0">₱<?= number_format($totalRevenue, 2) ?></h3>
                            </div>
                            <div class="bg-success bg-opacity-10 text-success rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="bi bi-wallet2 fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card h-100 mb-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.75rem;">Completed Orders</span>
                                <h3 class="fw-bold text-primary mt-2 mb-0"><?= number_format($completedOrdersCount) ?></h3>
                            </div>
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="bi bi-check-circle fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card h-100 mb-0">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted text-uppercase fw-bold small" style="font-size: 0.75rem;">Cancelled Orders</span>
                                <h3 class="fw-bold text-danger mt-2 mb-0"><?= number_format($cancelledOrdersCount) ?></h3>
                            </div>
                            <div class="bg-danger bg-opacity-10 text-danger rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                <i class="bi bi-x-circle fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Popular Services & Accurate Bar Chart Section -->
            <div class="row g-4 mb-4">
                <!-- Most Popular Services -->
                <div class="col-12 col-lg-5">
                    <div class="card h-100 mb-0">
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-tags text-primary me-2"></i> Most Popular Services</h5>
                        <?php if(empty($popularServices)): ?>
                            <div class="text-center py-5 text-muted small">No service data available for this period.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr class="text-muted small text-uppercase">
                                            <th>Service Name</th>
                                            <th class="text-center">Bookings</th>
                                            <th class="text-end">Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($popularServices as $serv): 
                                            $bCount = intval($serv['bookings']);
                                            $bText = $bCount === 1 ? '1 order' : $bCount . ' orders';
                                        ?>
                                        <tr>
                                            <td><strong class="text-dark"><?= htmlspecialchars($serv['service_name']) ?></strong></td>
                                            <td class="text-center"><span class="badge bg-light text-secondary border"><?= $bText ?></span></td>
                                            <td class="text-end fw-bold text-dark">₱<?= number_format($serv['revenue'], 2) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Accurate Sales Bar Chart Card -->
                <div class="col-12 col-lg-7">
                    <div class="card h-100 mb-0">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-bar-chart-fill text-primary me-2"></i> Sales Trend Overview</h5>
                            <span class="badge bg-light text-secondary border"><?= strtoupper($filter) ?> REPORT</span>
                        </div>
                        <div style="position: relative; height: 260px; width: 100%;">
                            <canvas id="salesBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Sales Breakdown Table -->
            <div class="card p-0 overflow-hidden">
                <div class="p-4 border-bottom">
                    <h5 class="fw-bold text-dark mb-0"><?= ucfirst($filter) ?> Sales Breakdown</h5>
                </div>
                <div class="table-container">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Time Period / Date</th>
                                <th scope="col">Completed Orders</th>
                                <th scope="col" class="text-end">Sales Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($tableSales)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">No sales records found for this timeframe.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($tableSales as $row): ?>
                                <tr>
                                    <td><strong class="text-dark"><?= htmlspecialchars($row['period']) ?></strong></td>
                                    <td><span class="badge bg-light text-secondary border"><?= $row['orders'] ?> order(s)</span></td>
                                    <td class="text-end fw-bold text-success">₱<?= number_format($row['sales'], 2) ?></td>
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

            // Render Accurate Bar Chart
            const ctx = document.getElementById('salesBarChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($chartLabels) ?>,
                    datasets: [{
                        label: 'Sales (₱)',
                        data: <?= json_encode($chartData) ?>,
                        backgroundColor: '#1e4f8f',
                        borderColor: '#153a6a',
                        borderWidth: 1,
                        borderRadius: 6,
                        barThickness: 'flex',
                        maxBarThickness: 40
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